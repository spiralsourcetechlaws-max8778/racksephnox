<?php

namespace App\Services\Mpesa;

use App\Models\MpesaTransaction;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class StkPush extends MpesaClient
{
    /**
     * Initiate an STK push for a deposit. The user_id must be passed explicitly
     * so this method is safe in API and queue contexts (auth()->id() is null there).
     */
    public function initiate(User $user, string $phone, float $amount, ?string $reference = null): array
    {
        $reference = $reference ?: 'DEP' . $user->id . now()->format('His');

        $timestamp = now()->format('YmdHis');
        $password  = $this->generateStkPassword($timestamp);

        $callbackUrl = $this->config['callback_urls']['stk'] ?? null;
        if (!$callbackUrl) {
            throw new \RuntimeException('M-Pesa STK callback URL not configured');
        }

        $data = [
            'BusinessShortCode' => $this->config['shortcode'],
            'Password'          => $password,
            'Timestamp'         => $timestamp,
            'TransactionType'   => 'CustomerPayBillOnline',
            'Amount'            => (int) round($amount),
            'PartyA'            => $phone,
            'PartyB'            => $this->config['shortcode'],
            'PhoneNumber'       => $phone,
            'CallBackURL'       => $callbackUrl,
            'AccountReference'  => $reference,
            'TransactionDesc'   => 'Racksephnox deposit',
        ];

        $response = $this->post('stk_push', $data);

        $checkoutRequestId = $response['CheckoutRequestID'] ?? null;
        $merchantRequestId = $response['MerchantRequestID'] ?? null;

        if (!$checkoutRequestId) {
            Log::error('STK push returned no CheckoutRequestID', $response);
            throw new \RuntimeException('M-Pesa did not accept the STK push');
        }

        MpesaTransaction::create([
            'user_id'              => $user->id,
            'transaction_type'     => 'stk_push',
            'transaction_id'       => $checkoutRequestId,
            'amount'               => $amount,
            'phone'                => $phone,
            'reference'            => $reference,
            'description'          => 'M-Pesa deposit via STK push',
            'status'               => MpesaTransaction::STATUS_PENDING,
            'raw_callback_data'    => $response,
        ]);

        Log::info('M-Pesa STK push initiated', [
            'user_id'            => $user->id,
            'amount'             => $amount,
            'reference'          => $reference,
            'checkout_request_id' => $checkoutRequestId,
        ]);

        return $response;
    }

    /**
     * Query the status of an STK push (used by reconciliation job).
     */
    public function query(string $checkoutRequestId): array
    {
        $timestamp = now()->format('YmdHis');

        $data = [
            'BusinessShortCode' => $this->config['shortcode'],
            'Password'          => $this->generateStkPassword($timestamp),
            'Timestamp'         => $timestamp,
            'CheckoutRequestID' => $checkoutRequestId,
        ];

        return $this->post('stk_query', $data);
    }
}
