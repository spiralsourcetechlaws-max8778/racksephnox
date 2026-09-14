<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Loan;
use App\Models\LoanProduct;
use App\Services\Loans\LoanService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoanController extends Controller
{
    public function __construct(protected LoanService $service) {}

    public function index(Request $request)
    {
        $query = Loan::with(['user', 'product'])->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $loans = $query->paginate(25);
        $products = LoanProduct::orderBy('name')->get();

        return view('admin.loans.index', compact('loans', 'products'));
    }

    public function show(Loan $loan)
    {
        $loan->load(['user', 'product', 'repayments', 'guarantors.user', 'collaterals', 'approver']);
        return view('admin.loans.show', compact('loan'));
    }

    public function approve(Loan $loan)
    {
        try {
            $this->service->approve($loan, Auth::user());
            return back()->with('success', 'Loan approved.');
        } catch (\Throwable $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function reject(Request $request, Loan $loan)
    {
        $data = $request->validate(['reason' => 'required|string|max:500']);
        try {
            $this->service->reject($loan, Auth::user(), $data['reason']);
            return back()->with('success', 'Loan rejected.');
        } catch (\Throwable $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function disburse(Loan $loan)
    {
        try {
            $this->service->disburse($loan);
            return back()->with('success', 'Loan disbursed to user wallet.');
        } catch (\Throwable $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }
}
