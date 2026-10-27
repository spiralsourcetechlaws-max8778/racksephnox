<?php

namespace App\Services\Mpesa;

use App\Models\MpesaTransaction;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class B2C extends MpesaClient
{
    /**
     * Send a B2C withdrawal. Creates a pending MpesaTransaction, returns
     * the Safaricom response. The wallet debit MUST be done by the caller
     * BEFORE calling this, so that if B2C fails we can refund.
     */
    public function send(User $user, string $phone, float $amount, string $reference, string $remarks = 'Withdrawal'): array
    {
        $initiatorName = $this->config['initiator_name'] ?? null;
        $initiatorPwd  = $this->config['initiator_password'] ?? null;
        $b2cShortcode  = $this->config['b2c_shortcode'] ?? null;
        $resultUrl     = $this->config['callback_urls']['b2c_result'] ?? null;
        $timeoutUrl    = $this->config['callback_urls']['b2c_timeout'] ?? null;

        if (!$initiatorName || !$initiatorPwd) {
            throw new \RuntimeException('B2C initiator credentials not configured');
        }
        if (!$b2cShortcode) {
            throw new \RuntimeException('B2C shortcode not configured');
        }
        if (!$resultUrl || !$timeoutUrl) {
            throw new \RuntimeException('B2C callback URLs not configured');
        }

        $data = [
            'InitiatorName'      => $initiatorName,
            'SecurityCredential' => $initiatorPwd,
            'CommandID'          => 'BusinessPayment',
            'Amount'             => (int) round($amount),
            'PartyA'             => $b2cShortcode,
            'PartyB'             => $phone,
            'Remarks'            => $remarks,
            'QueueTimeOutURL'    => $timeoutUrl,
            'ResultURL'          => $resultUrl,
            'Occasion'           => $reference,
        ];

        $response = $this->post('b2c', $data);

        $conversationId = $response['ConversationID'] ?? null;
        $originatorId   = $response['OriginatorConversationID'] ?? null;

        if (!$conversationId && !$originatorId) {
            Log::error('B2C returned no ConversationID', $response);
            throw new \RuntimeException('M-Pesa B2C request was rejected');
        }

        MpesaTransaction::create([
            'user_id'          => $user->id,
            'transaction_type' => 'b2c',
            'transaction_id'   => $conversationId ?? $originatorId,
            'amount'           => $amount,
            'phone'            => $phone,
            'reference'        => $reference,
            'description'      => 'M-Pesa withdrawal via B2C',
            'status'           => MpesaTransaction::STATUS_PENDING,
            'raw_callback_data' => $response,
        ]);

        Log::info('M-Pesa B2C initiated', [
            'user_id'         => $user->id,
            'amount'          => $amount,
            'reference'       => $reference,
            'conversation_id' => $conversationId,
        ]);

        return $response;
    }
}
