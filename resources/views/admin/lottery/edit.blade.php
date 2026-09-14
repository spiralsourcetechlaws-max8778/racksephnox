@extends('admin.layouts.app')

@section('content')
<div class="space-y-6">
    <a href="{{ route('admin.lottery.index') }}" class="text-gold-400 text-sm">← Back to Lottery</a>

    <h1 class="text-2xl font-bold text-gold">Edit Game · {{ $lottery->name }}</h1>

    <form method="POST" action="{{ route('admin.lottery.update', $lottery) }}" class="admin-card p-6 space-y-4">
        @csrf @method('PUT')

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="text-xs text-gold-400">Name</label>
                <input type="text" name="name" value="{{ $lottery->name }}" class="input-golden w-full" required>
            </div>
            <div>
                <label class="text-xs text-gold-400">Volatility</label>
                <select name="volatility" class="input-golden w-full">
                    @foreach(['low','medium','high','extreme'] as $v)
                        <option value="{{ $v }}" @selected($lottery->volatility == $v)>{{ ucfirst($v) }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div>
            <label class="text-xs text-gold-400">Description</label>
            <textarea name="description" class="input-golden w-full" rows="2">{{ $lottery->description }}</textarea>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="text-xs text-gold-400">Min Bet</label>
                <input type="number" name="min_bet" value="{{ $lottery->min_bet }}" class="input-golden w-full" step="0.01">
            </div>
            <div>
                <label class="text-xs text-gold-400">Max Bet</label>
                <input type="number" name="max_bet" value="{{ $lottery->max_bet }}" class="input-golden w-full" step="0.01">
            </div>
            <div>
                <label class="text-xs text-gold-400">Ticket Price</label>
                <input type="number" name="ticket_price" value="{{ $lottery->ticket_price }}" class="input-golden w-full" step="0.01">
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="text-xs text-gold-400">Base RTP %</label>
                <input type="number" name="base_rtp" value="{{ $lottery->base_rtp }}" class="input-golden w-full" step="0.01">
            </div>
            <div>
                <label class="text-xs text-gold-400">VIP RTP %</label>
                <input type="number" name="vip_rtp" value="{{ $lottery->vip_rtp }}" class="input-golden w-full" step="0.01">
            </div>
            <div>
                <label class="text-xs text-gold-400">Promo RTP %</label>
                <input type="number" name="promo_rtp" value="{{ $lottery->promo_rtp }}" class="input-golden w-full" step="0.01">
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="text-xs text-gold-400">Progressive Jackpot</label>
                <input type="number" name="progressive_jackpot" value="{{ $lottery->progressive_jackpot }}" class="input-golden w-full" step="0.01">
            </div>
            <div>
                <label class="text-xs text-gold-400">Jackpot Contribution %</label>
                <input type="number" name="jackpot_contribution_rate" value="{{ $lottery->jackpot_contribution_rate }}" class="input-golden w-full" step="0.01">
            </div>
            <div>
                <label class="text-xs text-gold-400">Bonus Buy Price</label>
                <input type="number" name="bonus_buy_price" value="{{ $lottery->bonus_buy_price }}" class="input-golden w-full" step="0.01">
            </div>
        </div>

        <div class="flex gap-6 text-sm">
            <label class="flex items-center gap-2">
                <input type="checkbox" name="is_active" value="1" {{ $lottery->is_active ? 'checked' : '' }}>
                <span class="text-gold-400">Active</span>
            </label>
            <label class="flex items-center gap-2">
                <input type="checkbox" name="enable_free_spins" value="1" {{ $lottery->enable_free_spins ? 'checked' : '' }}>
                <span class="text-gold-400">Free Spins</span>
            </label>
            <label class="flex items-center gap-2">
                <input type="checkbox" name="enable_bonus_buy" value="1" {{ $lottery->enable_bonus_buy ? 'checked' : '' }}>
                <span class="text-gold-400">Bonus Buy</span>
            </label>
        </div>

        <button class="btn-golden w-full py-3">Save Game</button>
    </form>

    @if(session('success'))
        <div class="bg-green-500/20 border border-green-500/40 rounded-xl p-3 text-green-300 text-sm">
            {{ session('success') }}
        </div>
    @endif
</div>
@endsection
