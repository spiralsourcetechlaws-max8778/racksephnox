@extends('admin.layouts.app')

@section('content')
<div class="space-y-6">
    <div class="flex justify-between items-center">
        <h1 class="text-2xl font-bold text-gold">🎰 Lottery Management</h1>
        <div class="flex gap-2">
            <a href="{{ route('admin.lottery.analytics') }}" class="btn-outline-silver text-sm px-4 py-2">Analytics</a>
            <a href="{{ route('admin.lottery.jackpots') }}" class="btn-outline-silver text-sm px-4 py-2">Jackpots</a>
            <a href="{{ route('admin.lottery.symbols') }}" class="btn-outline-silver text-sm px-4 py-2">Symbols</a>
            <a href="{{ route('admin.lottery.export') }}" class="btn-golden text-sm px-4 py-2">Export CSV</a>
        </div>
    </div>

    {{-- STATS --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="admin-card p-4"><p class="text-xs text-gold-400 uppercase">Total Games</p><p class="text-2xl font-bold text-gold">{{ $stats['total_games'] }}</p></div>
        <div class="admin-card p-4"><p class="text-xs text-gold-400 uppercase">Active</p><p class="text-2xl font-bold text-green-400">{{ $stats['active_games'] }}</p></div>
        <div class="admin-card p-4"><p class="text-xs text-gold-400 uppercase">Spins Today</p><p class="text-2xl font-bold text-gold">{{ number_format($stats['spins_today']) }}</p></div>
        <div class="admin-card p-4"><p class="text-xs text-gold-400 uppercase">Actual RTP</p><p class="text-2xl font-bold text-green-400">{{ $stats['actual_rtp'] }}%</p></div>
    </div>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="admin-card p-4"><p class="text-xs text-gold-400 uppercase">Total Spins</p><p class="text-xl font-bold text-gold">{{ number_format($stats['total_spins']) }}</p></div>
        <div class="admin-card p-4"><p class="text-xs text-gold-400 uppercase">Total Wagered</p><p class="text-xl font-bold text-gold">KES {{ number_format($stats['total_wagered'], 0) }}</p></div>
        <div class="admin-card p-4"><p class="text-xs text-gold-400 uppercase">Total Payouts</p><p class="text-xl font-bold text-green-400">KES {{ number_format($stats['total_payouts'], 0) }}</p></div>
        <div class="admin-card p-4"><p class="text-xs text-gold-400 uppercase">Net Revenue</p><p class="text-xl font-bold text-gold">KES {{ number_format($stats['total_wagered'] - $stats['total_payouts'], 0) }}</p></div>
    </div>

    {{-- GAMES TABLE --}}
    <div class="admin-card p-5 overflow-x-auto">
        <h3 class="text-lg font-bold text-gold mb-3">Games</h3>
        <table class="w-full text-sm">
            <thead class="border-b border-gold/30 text-gold-400 text-xs uppercase">
                <tr>
                    <th class="text-left p-2">Name</th>
                    <th class="text-right p-2">Ticket</th>
                    <th class="text-right p-2">RTP</th>
                    <th class="text-left p-2">Volatility</th>
                    <th class="text-left p-2">Status</th>
                    <th class="text-right p-2">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($games as $game)
                    <tr class="border-b border-gold/10 hover:bg-gold/5">
                        <td class="p-2">{{ $game->name }}</td>
                        <td class="p-2 text-right">{{ number_format($game->ticket_price, 0) }}</td>
                        <td class="p-2 text-right text-green-400">{{ $game->base_rtp }}%</td>
                        <td class="p-2 uppercase text-xs">{{ $game->volatility }}</td>
                        <td class="p-2">
                            <span class="{{ $game->is_active ? 'text-green-400' : 'text-red-400' }}">
                                {{ $game->is_active ? 'Active' : 'Disabled' }}
                            </span>
                        </td>
                        <td class="p-2 text-right">
                            <a href="{{ route('admin.lottery.edit', $game) }}" class="text-gold-400 text-xs hover:text-gold">Edit</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
