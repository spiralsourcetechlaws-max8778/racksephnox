@extends('admin.layouts.app')

@section('page-title', 'Lottery Configuration')
@section('breadcrumbs')
    <a href="{{ route('admin.dashboard') }}" class="text-gold hover:text-yellow-400">Dashboard</a>
    <span class="mx-1">/</span> Lottery
@endsection

@section('content')
<div class="space-y-4">

    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-xl font-bold text-white">Lottery Configuration</h2>
            <p class="text-sm text-gray-400">Manage games, symbols, jackpots, and view analytics.</p>
        </div>
    </div>

    @if(session('success')) <div class="bg-green-500/20 text-green-400 p-3 rounded">{{ session('success') }}</div> @endif
    @if($errors->any()) <div class="bg-red-500/20 text-red-400 p-3 rounded">{{ $errors->first() }}</div> @endif

    {{-- Quick actions --}}
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3">
        <a href="{{ route('admin.lottery.symbols') }}" class="admin-card p-4 hover:ring-2 hover:ring-gold/50 transition text-center">
            <i class="fas fa-star text-2xl text-gold mb-2"></i>
            <p class="text-sm text-white font-medium">Symbols</p>
            <p class="text-xs text-gray-500">Manage payouts</p>
        </a>
        <a href="{{ route('admin.lottery.jackpots') }}" class="admin-card p-4 hover:ring-2 hover:ring-gold/50 transition text-center">
            <i class="fas fa-trophy text-2xl text-yellow-400 mb-2"></i>
            <p class="text-sm text-white font-medium">Jackpots</p>
            <p class="text-xs text-gray-500">Force drop / reset</p>
        </a>
        <a href="{{ route('admin.lottery.analytics') }}" class="admin-card p-4 hover:ring-2 hover:ring-gold/50 transition text-center">
            <i class="fas fa-chart-bar text-2xl text-blue-400 mb-2"></i>
            <p class="text-sm text-white font-medium">Analytics</p>
            <p class="text-xs text-gray-500">Performance</p>
        </a>
        <a href="{{ route('admin.lottery.rtp-simulate') }}" class="admin-card p-4 hover:ring-2 hover:ring-gold/50 transition text-center">
            <i class="fas fa-flask text-2xl text-purple-400 mb-2"></i>
            <p class="text-sm text-white font-medium">RTP Simulator</p>
            <p class="text-xs text-gray-500">Test payout rates</p>
        </a>
        <a href="{{ route('admin.lottery.export') }}" class="admin-card p-4 hover:ring-2 hover:ring-gold/50 transition text-center">
            <i class="fas fa-download text-2xl text-green-400 mb-2"></i>
            <p class="text-sm text-white font-medium">Export</p>
            <p class="text-xs text-gray-500">Download config</p>
        </a>
        <a href="{{ route('admin.lottery.analytics') }}" class="admin-card p-4 hover:ring-2 hover:ring-gold/50 transition text-center">
            <i class="fas fa-cog text-2xl text-gray-400 mb-2"></i>
            <p class="text-sm text-white font-medium">Settings</p>
            <p class="text-xs text-gray-500">Game rules</p>
        </a>
    </div>

    {{-- Games list --}}
    <div class="admin-card overflow-hidden">
        <div class="p-4 border-b border-gray-800">
            <h3 class="text-lg font-semibold text-gold">Active Games</h3>
        </div>
        <table class="w-full text-sm">
            <thead class="bg-gray-800/50 border-b border-gray-800 text-gray-400 text-xs uppercase">
                <tr>
                    <th class="text-left p-3">Game</th>
                    <th class="text-left p-3 hidden md:table-cell">Slug</th>
                    <th class="text-left p-3">Status</th>
                    <th class="text-right p-3">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($games ?? [] as $game)
                    <tr class="border-b border-gray-800/50 hover:bg-gray-800/30 transition">
                        <td class="p-3 text-white font-medium">{{ $game->name }}</td>
                        <td class="p-3 text-xs text-gray-500 hidden md:table-cell">{{ $game->slug ?? $game->code ?? '—' }}</td>
                        <td class="p-3">
                            <span class="px-2 py-0.5 text-xs rounded {{ $game->is_active ? 'bg-green-500/20 text-green-400' : 'bg-red-500/20 text-red-400' }}">
                                {{ $game->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td class="p-3 text-right">
                            <a href="{{ route('admin.lottery.edit', $game) }}"
                               class="px-2 py-1 bg-blue-600 hover:bg-blue-700 text-white text-xs rounded">
                                Edit
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="p-8 text-center text-gray-500">
                            No games configured. Run the seeder to add default games.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Stats summary --}}
    @if(isset($stats))
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="admin-card p-4">
                <p class="text-xs text-gray-400 uppercase">Total Spins</p>
                <p class="text-2xl font-bold text-gold">{{ number_format($stats['total_spins'] ?? 0) }}</p>
            </div>
            <div class="admin-card p-4">
                <p class="text-xs text-gray-400 uppercase">Total Bets</p>
                <p class="text-2xl font-bold text-green-400">KES {{ number_format($stats['total_bets'] ?? 0, 0) }}</p>
            </div>
            <div class="admin-card p-4">
                <p class="text-xs text-gray-400 uppercase">Total Wins</p>
                <p class="text-2xl font-bold text-red-400">KES {{ number_format($stats['total_wins'] ?? 0, 0) }}</p>
            </div>
            <div class="admin-card p-4">
                <p class="text-xs text-gray-400 uppercase">House Edge</p>
                <p class="text-2xl font-bold text-white">{{ number_format($stats['house_edge'] ?? 0, 2) }}%</p>
            </div>
        </div>
    @endif
</div>
@endsection
