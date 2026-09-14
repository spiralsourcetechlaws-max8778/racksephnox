@extends('layouts.app')

@section('content')
<div class="py-8">
    <div class="max-w-4xl mx-auto px-4 sm:px-6">

        <a href="{{ route('lottery.index') }}" class="text-gold-400 text-sm">
            <i class="fas fa-arrow-left"></i> Back to Lottery
        </a>

        <h1 class="text-3xl font-bold golden-title mt-4 mb-6 text-center">🏆 Leaderboard</h1>

        <div class="flex justify-center gap-2 mb-6">
            @foreach(['day' => 'Today', 'week' => 'This Week', 'month' => 'This Month'] as $key => $label)
                <a href="{{ route('lottery.leaderboard', $key) }}"
                   class="px-4 py-2 rounded-lg {{ $period === $key ? 'bg-gold/30 text-gold' : 'bg-gold/10 text-gold-400' }}">
                    {{ $label }}
                </a>
            @endforeach
        </div>

        <div class="card-golden p-5">
            @forelse($rows as $i => $row)
                <div class="flex justify-between items-center border-b border-gold/10 py-3">
                    <div class="flex items-center gap-3">
                        <span class="text-2xl">
                            @if($i === 0) 🥇
                            @elseif($i === 1) 🥈
                            @elseif($i === 2) 🥉
                            @else #{{ $i + 1 }}
                            @endif
                        </span>
                        <div>
                            <p class="font-bold text-gold">{{ $row->user?->name ?? 'Player' }}</p>
                            <p class="text-xs text-ivory/50">{{ $row->spins }} spins</p>
                        </div>
                    </div>
                    <p class="text-lg font-bold text-green-400">KES {{ number_format($row->total_win, 0) }}</p>
                </div>
            @empty
                <p class="text-center text-ivory/50 py-8">No spins in this period yet.</p>
            @endforelse
        </div>
    </div>
</div>
@endsection
