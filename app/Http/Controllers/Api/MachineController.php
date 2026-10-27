<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Machine;
use App\Models\MachineInvestment;
use App\Services\MachineService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MachineController extends Controller
{
    protected MachineService $service;

    public function __construct(MachineService $service)
    {
        $this->service = $service;
    }

    public function index()
    {
        $machines = $this->service->getActiveMachines()->map(fn($m) => $this->formatMachine($m));
        return response()->json(['success' => true, 'data' => $machines]);
    }

    public function publicList()
    {
        $machines = $this->service->getActiveMachines()->map(fn($m) => [
            'code'          => $m->code,
            'name'          => $m->name,
            'risk_profile'  => $m->risk_profile,
            'duration_days' => $m->duration_days,
            'growth_rate'   => $m->growth_rate,
            'vip_amounts'   => $m->getVIPAmounts(),
        ]);
        return response()->json(['success' => true, 'data' => $machines]);
    }

    public function show($code)
    {
        $machine = Machine::where('code', $code)->where('is_active', true)->firstOrFail();
        return response()->json(['success' => true, 'data' => $this->formatMachine($machine, true)]);
    }

    public function invest(Request $request, Machine $machine)
    {
        $validated = $request->validate([
            'vip_level' => 'required|integer|min:1|max:3',
            'amount'    => 'required|numeric|min:1',
        ]);

        try {
            $investment = $this->service->invest(
                Auth::user(),
                $machine,
                (int) $validated['vip_level'],
                (float) $validated['amount']
            );

            return response()->json([
                'success' => true,
                'message' => 'Investment created successfully',
                'data'    => [
                    'investment_id'    => $investment->id,
                    'machine'          => $investment->machine->name,
                    'vip_level'        => $investment->vip_level,
                    'amount'           => (float) $investment->amount,
                    'daily_profit'     => (float) $investment->daily_profit,
                    'total_profit'     => (float) ($investment->total_projected_profit ?? 0),
                    'start_date'       => $investment->start_date?->toIso8601String(),
                    'end_date'         => $investment->end_date?->toIso8601String(),
                    'status'           => $investment->status,
                ],
            ], 201);
        } catch (\RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            \Log::error('API machine invest failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json(['success' => false, 'message' => 'Investment failed. Please try again.'], 500);
        }
    }

    protected function formatMachine(Machine $machine, bool $includeDetails = false): array
    {
        $data = [
            'code'          => $machine->code,
            'name'          => $machine->name,
            'description'   => $machine->description,
            'risk_profile'  => $machine->risk_profile,
            'duration_days' => $machine->duration_days,
            'growth_rate'   => $machine->growth_rate,
            'vip_amounts'   => $machine->getVIPAmounts(),
            'vip_tiers'     => array_values($machine->getVIPDetails()),
            'statistics'    => $machine->getStatistics(),
        ];

        if ($includeDetails && Auth::user()) {
            $active = MachineInvestment::where('user_id', Auth::id())
                ->where('machine_id', $machine->id)
                ->where('status', 'active')
                ->first();

            $data['user_investment'] = $active ? [
                'id'        => $active->id,
                'amount'    => (float) $active->amount,
                'vip_level' => $active->vip_level,
                'status'    => $active->status,
            ] : null;
        }

        return $data;
    }
}
