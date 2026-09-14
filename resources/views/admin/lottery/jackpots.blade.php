@extends('admin.layouts.app')

@section('content')
<div class="space-y-6">
    <div class="flex justify-between items-center">
        <h1 class="text-2xl font-bold text-gold">💎 Jackpot Pools</h1>
        <a href="{{ route('admin.lottery.index') }}" class="text-gold-400 text-sm">← Back</a>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
        @foreach($pools as $pool)
            <div class="admin-card p-5 relative">
                @if($pool->must_drop)
                    <span class="absolute top-3 right-3 bg-red-500/30 text-red-300 px-3 py-1 rounded-full text-xs animate-pulse">MUST DROP</span>
                @endif
                <h3 class="text-lg font-bold text-gold mb-1">{{ ucfirst($pool->tier) }} Tier</h3>
                <p class="text-xs text-gold-400/60 mb-3">{{ $pool->frequency_hz }} Hz</p>

                <p class="text-3xl font-bold text-gold mb-4">KES {{ number_format($pool->current_pool, 0) }}</p>

                <form method="POST" action="{{ route('admin.lottery.jackpots.update', $pool) }}" class="space-y-3">
                    @csrf @method('PUT')
                    <div class="grid grid-cols-3 gap-2">
                        <div>
                            <label class="text-xs text-gold-400">Seed</label>
                            <input type="number" name="seed_amount" value="{{ $pool->seed_amount }}" class="input-golden w-full text-xs" step="0.01">
                        </div>
                        <div>
                            <label class="text-xs text-gold-400">Ceiling</label>
                            <input type="number" name="ceiling_amount" value="{{ $pool->ceiling_amount }}" class="input-golden w-full text-xs" step="0.01">
                        </div>
                        <div>
                            <label class="text-xs text-gold-400">Contrib %</label>
                            <input type="number" name="contribution_rate" value="{{ $pool->contribution_rate }}" class="input-golden w-full text-xs" step="0.01">
                        </div>
                    </div>
                    <label class="flex items-center gap-2">
                        <input type="checkbox" name="is_active" value="1" {{ $pool->is_active ? 'checked' : '' }}>
                        <span class="text-xs text-gold-400">Active</span>
                    </label>
                    <button class="btn-golden w-full text-sm py-2">Save</button>
                </form>

                <div class="flex gap-2 mt-3">
                    <form method="POST" action="{{ route('admin.lottery.jackpots.force-drop', $pool) }}" class="flex-1">
                        @csrf
                        <button class="btn-outline-silver w-full text-xs py-2">Force Drop Next Spin</button>
                    </form>
                    <form method="POST" action="{{ route('admin.lottery.jackpots.reset', $pool) }}" class="flex-1"
                          onsubmit="return confirm('Reset pool to seed?')">
                        @csrf
                        <button class="btn-outline-silver w-full text-xs py-2">Reset to Seed</button>
                    </form>
                </div>
            </div>
        @endforeach
    </div>

    {{-- RECENT WINS --}}
    <div class="admin-card p-5">
        <h3 class="text-lg font-bold text-gold mb-3">Recent Jackpot Wins</h3>
        <table class="w-full text-sm">
            <thead class="border-b border-gold/30 text-gold-400 text-xs uppercase">
                <tr>
                    <th class="text-left p-2">Time</th>
                    <th class="text-left p-2">User</th>
                    <th class="text-left p-2">Tier</th>
                    <th class="text-right p-2">Amount</th>
                    <th class="text-center p-2">Paid</th>
                </tr>
            </thead>
            <tbody>
                @forelse($recent as $w)
                    <tr class="border-b border-gold/10">
                        <td class="p-2 text-ivory/60">{{ $w->created_at->diffForHumans() }}</td>
                        <td class="p-2">{{ $w->user?->name ?? '—' }}</td>
                        <td class="p-2 uppercase text-xs">{{ $w->tier }}</td>
                        <td class="p-2 text-right text-green-400 font-bold">KES {{ number_format($w->amount_won, 0) }}</td>
                        <td class="p-2 text-center">{{ $w->paid ? '✅' : '⏳' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="p-4 text-center text-ivory/50">No wins yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
