@extends('layouts.app')

@section('content')
<div class="py-8">
    <div class="max-w-4xl mx-auto px-4 sm:px-6">
        <a href="{{ route('lottery.tournaments') }}" class="text-gold-400 text-sm">
            <i class="fas fa-arrow-left"></i> All Tournaments
        </a>

        <div class="card-golden p-6 mt-4 mb-6">
            <h1 class="text-2xl font-bold golden-title">{{ $tournament->name }}</h1>
            <p class="text-ivory/70 mt-2">{{ $tournament->description }}</p>

            <div class="grid grid-cols-3 gap-4 mt-6 text-center">
                <div class="bg-gold/5 rounded p-3">
                    <p class="text-xs text-gold-400">Prize Pool</p>
                    <p class="text-lg font-bold text-green-400">KES {{ number_format($tournament->prize_pool, 0) }}</p>
                </div>
                <div class="bg-gold/5 rounded p-3">
                    <p class="text-xs text-gold-400">Starts</p>
                    <p class="text-sm text-ivory">{{ $tournament->start_date->format('M d, H:i') }}</p>
                </div>
                <div class="bg-gold/5 rounded p-3">
                    <p class="text-xs text-gold-400">Ends</p>
                    <p class="text-sm text-ivory">{{ $tournament->end_date->format('M d, H:i') }}</p>
                </div>
            </div>

            @if($myEntry)
                <div class="mt-6 p-4 bg-gold/10 rounded-xl">
                    <p class="text-gold text-sm">Your Score: <strong>{{ $myEntry->score }}</strong>
                        · Rank <strong>#{{ $myEntry->rank ?? '—' }}</strong></p>
                </div>
            @endif
        </div>

        <h2 class="text-lg font-bold text-gold mb-3">🏅 Leaderboard</h2>
        <div class="card-golden p-4 overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="border-b border-gold/30 text-gold-400">
                    <tr>
                        <th class="text-left p-2">Rank</th>
                        <th class="text-left p-2">Player</th>
                        <th class="text-right p-2">Score</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($leaderboard as $entry)
                        <tr class="border-b border-gold/10">
                            <td class="p-2 text-gold font-bold">#{{ $entry->rank }}</td>
                            <td class="p-2">{{ $entry->user->name ?? 'Player' }}</td>
                            <td class="p-2 text-right">{{ number_format($entry->score) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
