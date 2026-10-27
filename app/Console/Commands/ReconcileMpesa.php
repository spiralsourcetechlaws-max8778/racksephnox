<?php

namespace App\Console\Commands;

use App\Models\MpesaTransaction;
use App\Services\Mpesa\CallbackHandler;
use App\Services\Mpesa\StkPush;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ReconcileMpesa extends Command
{
    protected $signature = 'mpesa:reconcile';
    protected $description = 'Query Safaricom for stuck STK transactions and reconcile';

    public function handle(StkPush $stk, CallbackHandler $handler): int
    {
        $cutoff = now()->subMinutes(5);
        $stuck = MpesaTransaction::where('status', MpesaTransaction::STATUS_PENDING)
            ->where('transaction_type', 'stk_push')
            ->where('created_at', '<', $cutoff)
            ->where('created_at', '>', now()->subHours(24))
            ->limit(50)
            ->get();

        if ($stuck->isEmpty()) {
            $this->info('No stuck STK transactions to reconcile.');
            return self::SUCCESS;
        }

        $this->info("Reconciling {$stuck->count()} stuck STK transaction(s)...");

        $fixed = 0;
        foreach ($stuck as $tx) {
            try {
                $result = $stk->query($tx->transaction_id);

                if (($result['ResultCode'] ?? null) === '0') {
                    // Safaricom says it succeeded — credit manually
                    $handler->handleStkPush([
                        'Body' => [
                            'stkCallback' => [
                                'CheckoutRequestID' => $tx->transaction_id,
                                'ResultCode' => 0,
                                'ResultDesc' => 'Reconciled from query',
                                'CallbackMetadata' => [
                                    'Item' => [
                                        ['Name' => 'Amount', 'Value' => $tx->amount],
                                    ],
                                ],
                            ],
                        ],
                    ]);
                    $fixed++;
                    $this->line("  ✓ TX {$tx->id} marked complete via query");
                } elseif (in_array((string) ($result['ResultCode'] ?? ''), ['1032', '1037', '1'])) {
                    // Canceled by user / timeout — mark failed
                    $tx->update(['status' => MpesaTransaction::STATUS_FAILED, 'description' => 'Reconciled: failed']);
                    $fixed++;
                    $this->line("  ✓ TX {$tx->id} marked failed");
                }
            } catch (\Throwable $e) {
                Log::warning('Reconcile failed for TX', ['id' => $tx->id, 'error' => $e->getMessage()]);
            }
        }

        $this->info("✅ Reconciled {$fixed} of {$stuck->count()}");
        return self::SUCCESS;
    }
}
