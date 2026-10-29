<?php

namespace App\Services\Mpesa;

use App\Models\MpesaTransaction;
use App\Models\User;
use App\Models\WithdrawalRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CallbackHandler
{
    public function handleStkPush(array $callbackData): void
    {
        Log::info('M-Pesa STK callback received', ['body' => $callbackData]);

        $stkCallback       = $callbackData['Body']['stkCallback'] ?? [];
        $checkoutRequestId = $stkCallback['CheckoutRequestID'] ?? null;
        $resultCode        = (int) ($stkCallback['ResultCode'] ?? 1);
        $resultDesc        = $stkCallback['ResultDesc'] ?? 'Unknown';

        if (!$checkoutRequestId) return;

        DB::transaction(function () use ($checkoutRequestId, $resultCode, $resultDesc, $stkCallback, $callbackData) {
            $transaction = MpesaTransaction::where('transaction_id', $checkoutRequestId)->lockForUpdate()->first();
            if (!$transaction || in_array($transaction->status, [MpesaTransaction::STATUS_COMPLETED, MpesaTransaction::STATUS_FAILED])) return;

            if ($resultCode !== 0) {
                $transaction->update([
                    'status'            => MpesaTransaction::STATUS_FAILED,
                    'raw_callback_data' => $callbackData,
                    'description'       => 'STK failed: ' . $resultDesc,
                ]);
                return;
            }

            $receipt = $this->extractMetadata($stkCallback, 'MpesaReceiptNumber');
            $amount  = (float) ($this->extractMetadata($stkCallback, 'Amount') ?: $transaction->amount);

            $user = User::find($transaction->user_id);
            if ($user && $user->wallet) {
                $user->wallet->credit($amount, 'M-Pesa deposit · ' . ($receipt ?? $checkoutRequestId), 'deposit');
            }

            $transaction->update([
                'status'               => MpesaTransaction::STATUS_COMPLETED,
                'mpesa_receipt_number' => $receipt,
                'amount'               => $amount,
                'raw_callback_data'    => $callbackData,
            ]);
        });
    }

    public function handleB2CResult(array $callbackData): void
    {
        Log::info('M-Pesa B2C result received', ['body' => $callbackData]);

        $result         = $callbackData['Result'] ?? [];
        $conversationId = $result['ConversationID'] ?? null;
        $originatorId   = $result['OriginatorConversationID'] ?? null;
        $resultCode     = (int) ($result['ResultCode'] ?? 1);
        $resultDesc     = $result['ResultDesc'] ?? 'Unknown';
        $transactionId  = $result['TransactionID'] ?? null;

        if (!$conversationId && !$originatorId) return;

        DB::transaction(function () use ($conversationId, $originatorId, $resultCode, $resultDesc, $transactionId, $callbackData) {
            $transaction = MpesaTransaction::where(function ($q) use ($conversationId, $originatorId) {
                if ($conversationId) $q->orWhere('transaction_id', $conversationId);
                if ($originatorId)   $q->orWhere('transaction_id', $originatorId);
            })->lockForUpdate()->first();

            if (!$transaction || in_array($transaction->status, [MpesaTransaction::STATUS_COMPLETED, MpesaTransaction::STATUS_FAILED])) return;

            if ($resultCode === 0) {
                $transaction->update([
                    'status'               => MpesaTransaction::STATUS_COMPLETED,
                    'mpesa_receipt_number' => $transactionId,
                    'raw_callback_data'    => $callbackData,
                ]);
                $this->completeLinkedWithdrawal($transaction);
            } else {
                $transaction->update([
                    'status'            => MpesaTransaction::STATUS_FAILED,
                    'raw_callback_data' => $callbackData,
                    'description'       => 'B2C failed: ' . $resultDesc,
                ]);
                $this->refundLinkedWithdrawal($transaction, $resultDesc);
            }
        });
    }

    public function handleB2CTimeout(array $callbackData): void
    {
        $conversationId = $callbackData['Result']['ConversationID'] ?? null;
        if (!$conversationId) return;

        DB::transaction(function () use ($conversationId, $callbackData) {
            $transaction = MpesaTransaction::where('transaction_id', $conversationId)->lockForUpdate()->first();
            if (!$transaction || $transaction->status !== MpesaTransaction::STATUS_PENDING) return;

            $transaction->update([
                'status'            => MpesaTransaction::STATUS_FAILED,
                'raw_callback_data' => $callbackData,
                'description'       => 'B2C timeout',
            ]);
            $this->refundLinkedWithdrawal($transaction, 'B2C timeout');
        });
    }

    protected function completeLinkedWithdrawal(MpesaTransaction $transaction): void
    {
        $withdrawal = WithdrawalRequest::where('user_id', $transaction->user_id)
            ->whereIn('status', ['pending', 'processing'])
            ->where('amount', $transaction->amount)
            ->latest()->first();

        if ($withdrawal) {
            $withdrawal->update(['status' => 'completed', 'admin_notes' => 'B2C completed · ' . $transaction->mpesa_receipt_number]);
        }
    }

    protected function refundLinkedWithdrawal(MpesaTransaction $transaction, string $reason): void
    {
        $withdrawal = WithdrawalRequest::where('user_id', $transaction->user_id)
            ->whereIn('status', ['pending', 'processing'])
            ->where('amount', $transaction->amount)
            ->latest()->first();

        $refundAmount = $transaction->amount; // Fallback
        if ($withdrawal) {
            $withdrawal->update(['status' => 'failed', 'admin_notes' => 'B2C failed: ' . substr($reason, 0, 200)]);
            $refundAmount = $withdrawal->amount + $withdrawal->fee; // REFUND FEE + AMOUNT
        }

        $user = User::find($transaction->user_id);
        if ($user && $user->wallet) {
            $user->wallet->credit($refundAmount, 'Refund: M-Pesa B2C failed · ' . substr($reason, 0, 100), 'withdrawal_refund');
        }
    }

    protected function extractMetadata(array $stkCallback, string $key): ?string
    {
        foreach ($stkCallback['CallbackMetadata']['Item'] ?? [] as $item) {
            if (($item['Name'] ?? '') === $key) return isset($item['Value']) ? (string) $item['Value'] : null;
        }
        return null;
    }
}
