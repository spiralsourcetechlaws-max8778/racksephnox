<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InvestmentPlan;
use Illuminate\Http\Request;

class InvestmentPlanController extends Controller
{
    public function index() { $plans = InvestmentPlan::orderBy('min_amount')->paginate(15); return view('admin.plans.index', compact('plans')); }
    public function create() { return view('admin.plans.create'); }
    public function store(Request $request) {
        $data = $request->validate(['name'=>'required|string','description'=>'nullable|string','min_amount'=>'required|numeric','max_amount'=>'required|numeric','daily_interest_rate'=>'required|numeric','duration_days'=>'required|integer','early_withdrawal_penalty'=>'nullable|numeric','max_reinvestment_cycles'=>'nullable|integer']);
        $data['is_active'] = $request->boolean('is_active'); $data['allow_auto_reinvest'] = $request->boolean('allow_auto_reinvest'); $data['allow_early_withdrawal'] = $request->boolean('allow_early_withdrawal'); $data['is_infinite'] = $request->boolean('is_infinite');
        InvestmentPlan::create($data); return redirect()->route('admin.plans.index')->with('success', 'Plan created.');
    }
    public function show(InvestmentPlan $plan) { return redirect()->route('admin.plans.edit', $plan); }
    public function edit(InvestmentPlan $plan) { return view('admin.plans.edit', compact('plan')); }
    public function update(Request $request, InvestmentPlan $plan) {
        $data = $request->validate(['name'=>'required|string','description'=>'nullable|string','min_amount'=>'required|numeric','max_amount'=>'required|numeric','daily_interest_rate'=>'required|numeric','duration_days'=>'required|integer','early_withdrawal_penalty'=>'nullable|numeric','max_reinvestment_cycles'=>'nullable|integer']);
        $data['is_active'] = $request->boolean('is_active'); $data['allow_auto_reinvest'] = $request->boolean('allow_auto_reinvest'); $data['allow_early_withdrawal'] = $request->boolean('allow_early_withdrawal'); $data['is_infinite'] = $request->boolean('is_infinite');
        $plan->update($data); return redirect()->route('admin.plans.index')->with('success', 'Plan updated.');
    }
    public function destroy(InvestmentPlan $plan) { $plan->delete(); return back()->with('success', 'Plan deleted.'); }
    public function export() { return back(); }
}
