@extends('layouts.app')

@section('content')
<div class="py-8">
    <div class="max-w-2xl mx-auto px-4 sm:px-6">

        <a href="{{ route('lottery.index') }}" class="text-gold-400 text-sm">
            <i class="fas fa-arrow-left"></i> Back to Lottery
        </a>

        <h1 class="text-3xl font-bold golden-title mt-4 mb-2">🛡️ Responsible Gaming</h1>
        <p class="text-gold-400 text-sm mb-8">Your wellbeing is the highest priority.</p>

        <div class="card-golden p-6 mb-6">
            <h2 class="text-lg font-bold text-gold mb-3">Loss Limits</h2>
            <form method="POST" action="{{ route('lottery.responsible.update') }}">
                @csrf
                <div class="space-y-3">
                    <div>
                        <label class="text-xs text-gold-400">Daily Loss Cap (KES)</label>
                        <input type="number" name="daily_loss_cap" class="input-golden w-full"
                               value="{{ $rg->daily_loss_cap }}" min="0" step="100">
                    </div>
                    <div>
                        <label class="text-xs text-gold-400">Weekly Loss Cap (KES)</label>
                        <input type="number" name="weekly_loss_cap" class="input-golden w-full"
                               value="{{ $rg->weekly_loss_cap }}" min="0" step="100">
                    </div>
                    <div>
                        <label class="text-xs text-gold-400">Monthly Loss Cap (KES)</label>
                        <input type="number" name="monthly_loss_cap" class="input-golden w-full"
                               value="{{ $rg->monthly_loss_cap }}" min="0" step="100">
                    </div>
                    <button class="btn-golden w-full">Save Limits</button>
                </div>
            </form>
        </div>

        <div class="card-golden p-6 mb-6">
            <h2 class="text-lg font-bold text-gold mb-3">Cool-Down</h2>
            <p class="text-xs text-ivory/70 mb-3">
                Take a break. During cool-down, you cannot place bets.
            </p>
            <form method="POST" action="{{ route('lottery.responsible.cool-down') }}" class="flex gap-2">
                @csrf
                <select name="hours" class="input-golden flex-1">
                    <option value="1">1 hour</option>
                    <option value="6">6 hours</option>
                    <option value="24">24 hours</option>
                    <option value="72">3 days</option>
                    <option value="168">7 days</option>
                </select>
                <button class="btn-golden px-4">Activate</button>
            </form>
        </div>

        <div class="card-golden p-6 border-red-500/40">
            <h2 class="text-lg font-bold text-red-400 mb-3">Self-Exclusion</h2>
            <p class="text-xs text-ivory/70 mb-3">
                Permanently or temporarily exclude yourself. Cannot be reversed early.
            </p>
            <form method="POST" action="{{ route('lottery.responsible.self-exclude') }}"
                  onsubmit="return confirm('Are you certain? This cannot be undone.')">
                @csrf
                <select name="days" class="input-golden w-full mb-2">
                    <option value="1">24 hours</option>
                    <option value="7">7 days</option>
                    <option value="30">30 days</option>
                    <option value="180">6 months</option>
                    <option value="365">1 year</option>
                </select>
                <input type="text" name="reason" class="input-golden w-full mb-2" placeholder="Reason (optional)">
                <button class="btn-golden bg-red-500/80 hover:bg-red-500/100 w-full">Self-Exclude</button>
            </form>
        </div>

        @if($rg->is_self_excluded)
            <div class="mt-6 p-4 bg-red-500/20 border border-red-500/40 rounded-xl">
                <p class="text-red-300 text-sm font-bold">
                    ⚠️ You are self-excluded until {{ $rg->self_exclusion_until->format('M d, Y H:i') }}
                </p>
            </div>
        @endif
    </div>
</div>
@endsection
