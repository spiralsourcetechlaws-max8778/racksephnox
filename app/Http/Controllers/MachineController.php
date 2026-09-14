<?php

namespace App\Http\Controllers;

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

    /* ---------- INDEX ---------- */
    public function index()
    {
        $machines = $this->service->getActiveMachines();

        $stats = [
            'machine_count' => $machines->count(),
            'vip_levels'    => 3,
            'total_roi'     => 88,
            'cycle_days'    => MachineService::CYCLE_DAYS,
            'phi'           => 1.61803398875,
            'lambda'        => 1.27201964951,
            'pi'            => 3.14159265359,
            'e'             => 2.71828182846,
            'frequency'     => 888,
        ];

        $user = Auth::user();
        $userStats = $user
            ? $this->service->getDashboardStats($user)
            : ['total_invested' => 0, 'total_profit' => 0, 'active_count' => 0, 'completed_count' => 0, 'projected_profit' => 0];

        return view('machines.index', compact('machines', 'stats', 'userStats'));
    }

    /* ---------- SHOW ---------- */
    public function show(string $code)
    {
        $machine = $this->service->getMachineByCode($code);
        $vips    = $this->service->getVipTiers($machine);

        $userInvestments = Auth::check()
            ? MachineInvestment::forUser(Auth::id())
                ->where('machine_id', $machine->id)
                ->latest()
                ->get()
            : collect();

        return view('machines.show', compact('machine', 'vips', 'userInvestments'));
    }

    /* ---------- INVEST ---------- */
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

            return redirect()
                ->route('machines.my-investments')
                ->with('success', "Investment of KES " . number_format($investment->amount, 2)
                    . " in {$machine->name} VIP {$investment->vip_level} created. "
                    . "Daily profit: KES " . number_format($investment->daily_profit, 2));
        } catch (\Throwable $e) {
            return back()->withErrors(['error' => $e->getMessage()])->withInput();
        }
    }

    /* ---------- MY INVESTMENTS ---------- */
    public function myInvestments()
    {
        $investments = MachineInvestment::with('machine')
            ->forUser(Auth::id())
            ->latest()
            ->get();

        $stats = $this->service->getDashboardStats(Auth::user());

        return view('machines.my-investments', compact('investments', 'stats'));
    }

    /* ---------- EARLY WITHDRAW ---------- */
    public function earlyWithdraw(MachineInvestment $investment)
    {
        if ($investment->user_id !== Auth::id()) {
            abort(403);
        }

        try {
            $payout = $this->service->earlyWithdraw($investment);
            return back()->with('success', 'Early withdrawal complete. Payout: KES ' . number_format($payout, 2) . ' (20% penalty applied).');
        } catch (\Throwable $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /* ---------- STATUS (JSON) ---------- */
    public function status(MachineInvestment $investment)
    {
        if ($investment->user_id !== Auth::id() && !Auth::user()->is_admin) {
            abort(403);
        }

        return response()->json([
            'id'                     => $investment->id,
            'machine'                => $investment->machine->name ?? null,
            'vip_level'              => $investment->vip_level,
            'amount'                 => (float) $investment->amount,
            'profit_credited'        => (float) $investment->profit_credited,
            'daily_profit'           => (float) $investment->daily_profit,
            'total_projected_profit' => (float) $investment->total_projected_profit,
            'status'                 => $investment->status,
            'days_elapsed'           => $investment->days_elapsed,
            'days_remaining'         => $investment->days_remaining,
            'progress_percent'       => $investment->progress_percent,
            'current_value'          => $investment->current_value,
            'projected_total'        => $investment->projected_total,
        ]);
    }
}
