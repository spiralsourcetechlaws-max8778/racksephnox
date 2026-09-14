<?php

namespace App\Http\Controllers;

use App\Models\InvestmentPlan;
use App\Services\Investment\InvestmentManager;
use App\Services\Investment\UnifiedInvestmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InvestmentController extends Controller
{
    protected UnifiedInvestmentService $unified;
    protected InvestmentManager $manager;

    public function __construct(UnifiedInvestmentService $unified, InvestmentManager $manager)
    {
        $this->unified = $unified;
        $this->manager = $manager;
    }

    /* ---------- INDEX ---------- */
    public function index()
    {
        $user        = Auth::user();
        $investments = $this->unified->getAllInvestments($user);
        $stats       = $this->unified->getStats($user);
        $plans       = $this->manager->getActivePlans();

        return view('investments.index', compact('investments', 'stats', 'plans'));
    }

    /* ---------- SHOW ---------- */
    public function show($id)
    {
        $user        = Auth::user();
        $investments = $this->unified->getAllInvestments($user);
        $investment  = $investments->firstWhere('id', (int) $id);

        abort_unless($investment, 404);

        return view('investments.show', compact('investment'));
    }

    /* ---------- STORE (create a plan investment) ---------- */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'plan_id' => 'required|exists:investment_plans,id',
            'amount'  => 'required|numeric|min:1',
        ]);

        try {
            $plan = InvestmentPlan::findOrFail($validated['plan_id']);
            $inv  = $this->manager->invest(Auth::user(), $plan, (float) $validated['amount']);

            return redirect()
                ->route('investments.show', $inv->id)
                ->with('success', "Investment of KES " . number_format($inv->amount, 2)
                    . " created in {$plan->name}.");
        } catch (\Throwable $e) {
            return back()->withErrors(['error' => $e->getMessage()])->withInput();
        }
    }

    /* ---------- REDIRECT legacy route ---------- */
    public function redirectToMachines()
    {
        return redirect()
            ->route('investments.index')
            ->with('info', 'Choose a plan below or invest in the RX Machine Series.');
    }
}
