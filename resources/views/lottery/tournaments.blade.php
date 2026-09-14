@extends('layouts.app')

@section('content')
<div class="py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-8">
            <h1 class="text-4xl font-bold golden-title">🏆 Tournaments</h1>
            <p class="text-gold-400 mt-2">Compete · Climb · Conquer</p>
        </div>

        {{-- ACTIVE --}}
        <h2 class="text-xl font-bold text-gold mb-4">🔥 Live Now</h2>
        @forelse($active as $t)
            <div class="card-golden p-5 mb-4 group hover:scale-[1.01] transition">
                <div class="flex justify-between items-center">
                    <div>
                        <h3 class="text-lg font-bold text-gold">{{ $t->name }}</h3>
                        <p class="text-xs text-ivory/60">{{ $t->description }}</p>
                        <p class="text-xs text-gold-400/70 mt-1">
                            Ends {{ $t->end_date->diffForHumans() }}
                        </p>
                    </div>
                    <div class="text-right">
                        <p class="text-2xl font-bold text-green-400">KES {{ number_format($t->prize_pool, 0) }}</p>
                        <p class="text-xs text-gold-400">Prize Pool</p>
                        <a href="{{ route('lottery.tournaments.show', $t) }}"
                           class="btn-golden text-sm px-4 py-1 mt-2 inline-block">View →</a>
                    </div>
                </div>
            </div>
        @empty
            <p class="text-ivory/50 mb-8">No live tournaments right now.</p>
        @endforelse

        {{-- UPCOMING --}}
        @if($upcoming->count())
            <h2 class="text-xl font-bold text-gold mt-10 mb-4">🗓 Upcoming</h2>
            <div class="space-y-3">
                @foreach($upcoming as $t)
                    <div class="card-golden p-4 flex justify-between items-center">
                        <div>
                            <p class="font-bold text-gold">{{ $t->name }}</p>
                            <p class="text-xs text-ivory/60">
                                Starts {{ $t->start_date->format('M d, Y H:i') }}
                            </p>
                        </div>
                        <p class="text-gold">KES {{ number_format($t->prize_pool, 0) }}</p>
                    </div>
                @endforeach
            </div>
        @endif

        {{-- PAST --}}
        @if($past->count())
            <h2 class="text-xl font-bold text-gold mt-10 mb-4">📜 Completed</h2>
            <div class="space-y-2">
                @foreach($past as $t)
                    <div class="card-golden p-3 flex justify-between items-center text-sm">
                        <span class="text-ivory/70">{{ $t->name }}</span>
                        <span class="text-gold-400">
                            {{ $t->prize_distributed ? '✅ Distributed' : '⏳ Pending' }}
                        </span>
                        <a href="{{ route('lottery.tournaments.show', $t) }}" class="text-gold-400">View</a>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
@endsection
