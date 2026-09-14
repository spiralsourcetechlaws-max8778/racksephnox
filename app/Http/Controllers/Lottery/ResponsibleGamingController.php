<?php

namespace App\Http\Controllers\Lottery;

use App\Http\Controllers\Controller;
use App\Models\LotteryResponsibleGaming;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ResponsibleGamingController extends Controller
{
    public function index()
    {
        $rg = LotteryResponsibleGaming::firstOrCreate(['user_id' => Auth::id()]);
        return view('lottery.responsible', compact('rg'));
    }

    public function update(Request $request)
    {
        $rg = LotteryResponsibleGaming::firstOrCreate(['user_id' => Auth::id()]);

        $data = $request->validate([
            'daily_loss_cap'   => 'nullable|numeric|min:0',
            'weekly_loss_cap'  => 'nullable|numeric|min:0',
            'monthly_loss_cap' => 'nullable|numeric|min:0',
        ]);

        // Caps can only be raised after 24 hours — but lowered instantly
        foreach (['daily', 'weekly', 'monthly'] as $key) {
            $field = "{$key}_loss_cap";
            $new = $data[$field] ?? null;
            $old = $rg->$field;

            if ($new !== null && $old !== null && $new > $old && $rg->updated_at?->gt(now()->subDay())) {
                return back()->withErrors(['error' => "Raising the {$key} cap requires 24h since last change."]);
            }
        }

        $rg->update($data);
        return back()->with('success', 'Limits updated.');
    }

    public function coolDown(Request $request)
    {
        $data = $request->validate([
            'hours' => 'required|integer|min:1|max:168',
        ]);

        $rg = LotteryResponsibleGaming::firstOrCreate(['user_id' => Auth::id()]);
        $rg->cool_down_until = now()->addHours((int) $data['hours']);
        $rg->save();

        return back()->with('success', 'Cool-down activated.');
    }

    public function selfExclude(Request $request)
    {
        $data = $request->validate([
            'days'   => 'required|integer|min:1|max:365',
            'reason' => 'nullable|string|max:500',
        ]);

        $rg = LotteryResponsibleGaming::firstOrCreate(['user_id' => Auth::id()]);
        $rg->self_exclusion_until  = now()->addDays((int) $data['days']);
        $rg->self_exclusion_reason = $data['reason'] ?? null;
        $rg->save();

        return back()->with('success', 'Self-exclusion activated. You will be logged out.');
    }
}
