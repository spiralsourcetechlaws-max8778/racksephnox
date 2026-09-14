<?php

namespace App\Http\Controllers;

use App\Models\Loan;
use App\Models\LoanProduct;
use App\Services\Loans\LoanCalculator;
use App\Services\Loans\LoanCreditService;
use App\Services\Loans\LoanService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoanController extends Controller
{
    public function __construct(
        protected LoanService $service,
        protected LoanCalculator $calculator,
        protected LoanCreditService $credit
    ) {}

    /* ---------- INDEX ---------- */
    public function index()
    {
        $user     = Auth::user();
        $products = LoanProduct::active()->get();
        $loans    = Loan::with('product')
            ->forUser($user->id)
            ->latest()
            ->get();

        $creditScore = $this->credit->recalculate($user);

        $stats = [
            'total_borrowed'  => (float) $loans->sum('principal'),
            'total_outstanding' => (float) $loans->where('status', 'active')->sum('balance'),
            'active_loans'    => $loans->where('status', 'active')->count(),
            'completed_loans' => $loans->where('status', 'completed')->count(),
            'credit_score'    => $creditScore->score,
            'credit_tier'     => $creditScore->tier,
        ];

        return view('loans.index', compact('products', 'loans', 'stats'));
    }

    /* ---------- APPLY FORM ---------- */
    public function create(LoanProduct $product)
    {
        $user        = Auth::user();
        $creditScore = $this->credit->recalculate($user);

        return view('loans.apply', compact('product', 'creditScore'));
    }

    /* ---------- PREVIEW (AJAX) ---------- */
    public function preview(Request $request, LoanProduct $product)
    {
        $data = $request->validate([
            'amount'   => 'required|numeric|min:1',
            'duration' => 'required|integer|min:1',
            'frequency'=> 'nullable|in:daily,weekly,monthly',
        ]);

        $calc = $this->calculator->calculate(
            $product,
            (float) $data['amount'],
            (int) $data['duration'],
            $data['frequency'] ?? 'monthly'
        );

        return response()->json($calc);
    }

    /* ---------- STORE ---------- */
    public function store(Request $request, LoanProduct $product)
    {
        $data = $request->validate([
            'amount'    => 'required|numeric|min:1',
            'duration'  => 'required|integer|min:1',
            'frequency' => 'nullable|in:daily,weekly,monthly',
            'purpose'   => 'nullable|string|max:500',
        ]);

        try {
            $loan = $this->service->apply(
                Auth::user(),
                $product,
                (float) $data['amount'],
                (int) $data['duration'],
                $data['frequency'] ?? 'monthly',
                $data['purpose'] ?? null
            );

            return redirect()
                ->route('loans.show', $loan->id)
                ->with('success', 'Loan application submitted. Reference: ' . $loan->reference);
        } catch (\Throwable $e) {
            return back()->withErrors(['error' => $e->getMessage()])->withInput();
        }
    }

    /* ---------- SHOW ---------- */
    public function show(Loan $loan)
    {
        abort_unless($loan->user_id === Auth::id(), 403);
        $loan->load(['product', 'repayments', 'guarantors.user', 'collaterals']);

        $calculator = $this->calculator;

        return view('loans.show', compact('loan', 'calculator'));
    }

    /* ---------- REPAY ---------- */
    public function repay(Request $request, Loan $loan)
    {
        abort_unless($loan->user_id === Auth::id(), 403);

        $data = $request->validate([
            'amount' => 'required|numeric|min:1',
        ]);

        try {
            $this->service->repay($loan, (float) $data['amount']);
            return back()->with('success', 'Repayment received. Thank you.');
        } catch (\Throwable $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /* ---------- CANCEL ---------- */
    public function cancel(Loan $loan)
    {
        abort_unless($loan->user_id === Auth::id(), 403);

        try {
            $this->service->cancel($loan);
            return redirect()->route('loans.index')->with('success', 'Loan cancelled.');
        } catch (\Throwable $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }
}
