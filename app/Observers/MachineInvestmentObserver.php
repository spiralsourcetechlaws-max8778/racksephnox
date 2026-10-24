<?php

namespace App\Observers;

use App\Models\MachineInvestment;
use App\Services\BonusService;

/**
 * Fires on machine investment creation — credits the RX activation bonus
 * automatically, without touching MachineController.
 */
class MachineInvestmentObserver
{
    public function __construct(protected BonusService $bonuses) {}

    public function created(MachineInvestment $investment): void
    {
        // Only credit for RX-series machines (RX0..RX6)
        $machine = $investment->machine ?? null;
        if (!$machine || !str_starts_with((string) $machine->code, 'RX')) {
            return;
        }

        $user = $investment->user ?? null;
        if (!$user) {
            return;
        }

        $this->bonuses->creditMachineActivationBonus($user, $machine, $investment->id);
    }
}
