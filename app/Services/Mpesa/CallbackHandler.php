<?php

namespace App\Services\Mpesa;

use App\Models\MpesaTransaction;
use App\Models\User;
use App\Models\WithdrawalRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CallbackHandler
{
    /**
     * Handle an STK push callback from Safaricom.
     * Idempotent — Safaricom retries callbacks 3-5×, only the first should credit the wallet.
     */
    public function handleStkPush(array $callbackData): void
    {
        Log::info('M-Pesa STK callback received', ['body' => $callbackData]);

        $stkCallback       = $callbackData['Body']['stkCallback'] ?? [];
        $checkoutRequestId = $stkCallback['CheckoutRequestID'] ?? null;
        $resultCode        = (int) ($stkCallback['ResultCode'] ?? 1);
        $resultDesc        = $stkCallback['ResultDesc'] ?? 'Unknown';

        if (!$checkoutRequestId) {
            Log::error('STK callback: no CheckoutRequestID', ['body' => $callbackData]);
            return;
        }

        // Atomic processing with row lock to prevent concurrent double-credit
        DB::transaction(function () use ($checkoutRequestId, $resultCode, $resultDesc, $stkCallback, $callbackData) {
            $transaction = MpesaTransaction::where('transaction_id', $checkoutRequestId)
                ->lockForUpdate()
                ->first();

            if (!$transaction) {
                Log::error('STK callback: transaction not found', ['checkout_request_id' => $checkoutRequestId]);
                return;
            }

            // Idempotency: already processed?
            if ($transaction->status === MpesaTransaction::STATUS_COMPLETED) {
                Log::info('STK callback: already completed, skipping', ['id' => $transaction->id]);
                return;
            }
            if ($transaction->status === MpesaTransaction::STATUS_FAILED) {
                Log::info('STK callback: already failed, skipping', ['id' => $transaction->id]);
                return;
            }

            if ($resultCode !== 0) {
                $transaction->update([
                    'status'            => MpesaTransaction::STATUS_FAILED,
                    'raw_callback_data' => $callbackData,
                    'description'       => 'STK failed: ' . $resultDesc,
                ]);
                Log::info('STK callback: marked failed', [
                    'id'          => $transaction->id,
                    'result_code' => $resultCode,
                    'reason'      => $resultDesc,
                ]);
                return;
            }

            $receipt = $this->extractMetadata($stkCallback, 'MpesaReceiptNumber');
            $amount  = (float) ($this->extractMetadata($stkCallback, 'Amount') ?: $transaction->amount);

            if (!$receipt) {
                Log::warning('STK callback: no MpesaReceiptNumber in metadata', $stkCallback);
            }

            $user = User::find($transaction->user_id);
            if (!$user || !$user->wallet) {
                Log::error('STK callback: user or wallet missing', ['user_id' => $transaction->user_id]);
                return;
            }

            $wallet = $user->wallet;
            $wallet->credit($amount, 'M-Pesa deposit · ' . ($receipt ?? $checkoutRequestId), 'deposit');

            $transaction->update([
                'status'              => MpesaTransaction::STATUS_COMPLETED,
                'mpesa_receipt_number' => $receipt,
                'amount'              => $amount,
                'raw_callback_data'   => $callbackData,
            ]);

            Log::info('STK callback: wallet credited', [
                'user_id' => $user->id,
                'amount'  => $amount,
                'receipt' => $receipt,
            ]);
        });
    }

    /**
     * Handle the B2C result callback. Called on both success and failure.
     */
    public function handleB2CResult(array $callbackData): void
    {
        Log::info('M-Pesa B2C result received', ['body' => $callbackData]);

        $result = $callbackData['Result'] ?? [];
        $conversationId   = $result['ConversationID'] ?? null;
        $originatorId     = $result['OriginatorConversationID'] ?? null;
        $resultCode       = (int) ($result['ResultCode'] ?? 1);
        $resultDesc       = $result['ResultDesc'] ?? 'Unknown';
        $transactionId    = $result['TransactionID'] ?? null;

        if (!$conversationId && !$originatorId) {
            Log::error('B2C result: no ConversationID', ['body' => $callbackData]);
            return;
        }

        DB::transaction(function () use ($conversationId, $originatorId, $resultCode, $resultDesc, $transactionId, $callbackData) {
            $transaction = MpesaTransaction::where(function ($q) use ($conversationId, $originatorId) {
                if ($conversationId) $q->orWhere('transaction_id', $conversationId);
                if ($originatorId)   $q->orWhere('transaction_id', $originatorId);
            })->lockForUpdate()->first();

            if (!$transaction) {
                Log::error('B2C result: transaction not found', [
                    'conversation_id' => $conversationId,
                    'originator_id'   => $originatorId,
                ]);
                return;
            }

            if (in_array($transaction->status, [MpesaTransaction::STATUS_COMPLETED, MpesaTransaction::STATUS_FAILED])) {
                Log::info('B2C result: already processed', ['id' => $transaction->id]);
                return;
            }

            if ($resultCode === 0) {
                $transaction->update([
                    'status'              => MpesaTransaction::STATUS_COMPLETED,
                    'mpesa_receipt_number' => $transactionId,
                    'raw_callback_data'   => $callbackData,
                ]);

                $this->completeLinkedWithdrawal($transaction);

                Log::info('B2C result: completed', ['id' => $transaction->id, 'receipt' => $transactionId]);
            } else {
                $transaction->update([
                    'status'            => MpesaTransaction::STATUS_FAILED,
                    'raw_callback_data' => $callbackData,
                    'description'       => 'B2C failed: ' . $resultDesc,
                ]);

                $this->refundLinkedWithdrawal($transaction, $resultDesc);

                Log::info('B2C result: failed, refunded', [
                    'id' => $transaction->id,
                    'reason' => $resultDesc,
                ]);
            }
        });
    }

    /**
     * Handle B2C timeout callback. Safaricom notifies us when B2C times out
     * — treat as failed and refund.
     */
    public function handleB2CTimeout(array $callbackData): void
    {
        Log::warning('M-Pesa B2C timeout received', ['body' => $callbackData]);

        $conversationId = $callbackData['Result']['ConversationID'] ?? null;
        if (!$conversationId) return;

        DB::transaction(function () use ($conversationId, $callbackData) {
            $transaction = MpesaTransaction::where('transaction_id', $conversationId)
                ->lockForUpdate()
                ->first();

            if (!$transaction || $transaction->status !== MpesaTransaction::STATUS_PENDING) {
                return;
            }

            $transaction->update([
                'status'            => MpesaTransaction::STATUS_FAILED,
                'raw_callback_data' => $callbackData,
                'description'       => 'B2C timeout',
            ]);

            $this->refundLinkedWithdrawal($transaction, 'B2C timeout');
        });
    }

    /**
     * When B2C succeeds, mark the linked withdrawal request as completed.
     * The wallet was debited when the withdrawal was created.
     */
    protected function completeLinkedWithdrawal(MpesaTransaction $transaction): void
    {
        $withdrawal = WithdrawalRequest::where('user_id', $transaction->user_id)
            ->where('status', 'processing')
            ->where('amount', $transaction->amount)
            ->latest()
            ->first();

        if ($withdrawal) {
            $withdrawal->update([
                'status' => 'completed',
                'admin_notes' => 'B2C completed · ' . $transaction->mpesa_receipt_number,
            ]);
        }
    }

    /**
     * When B2C fails, refund the wallet and mark the withdrawal as failed.
     * Wallet was debited when the withdrawal was created, so refund it now.
     */
    protected function refundLinkedWithdrawal(MpesaTransaction $transaction, string $reason): void
    {
        $withdrawal = WithdrawalRequest::where('user_id', $transaction->user_id)
            ->where('status', 'processing')
            ->where('amount', $transaction->amount)
            ->latest()
            ->first();

        if ($withdrawal) {
            $withdrawal->update([
                'status' => 'failed',
                'admin_notes' => 'B2C failed: ' . substr($reason, 0, 200),
            ]);
        }

        $user = User::find($transaction->user_id);
        if ($user && $user->wallet) {
            $user->wallet->credit(
                $transaction->amount,
                'Refund: M-Pesa B2C failed · ' . substr($reason, 0, 100),
                'withdrawal_refund'
            );
        }
    }

    protected function extractMetadata(array $stkCallback, string $key): ?string
    {
        $items = $stkCallback['CallbackMetadata']['Item'] ?? [];
        foreach ($items as $item) {
            if (($item['Name'] ?? '') === $key) {
                return isset($item['Value']) ? (string) $item['Value'] : null;
            }
        }
        return null;
    }
}
