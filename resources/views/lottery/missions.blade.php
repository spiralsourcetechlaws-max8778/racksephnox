@extends('layouts.app')

@section('content')
<div class="py-8">
    <div class="max-w-4xl mx-auto px-4 sm:px-6">

        <a href="{{ route('lottery.index') }}" class="text-gold-400 text-sm">
            <i class="fas fa-arrow-left"></i> Back to Lottery
        </a>

        <h1 class="text-3xl font-bold golden-title mt-4 mb-6">Daily Missions</h1>

        @if(session('success'))
            <div class="bg-green-500/20 border border-green-500/40 rounded-xl p-4 mb-4 text-green-300">
                {{ session('success') }}
            </div>
        @endif
        @if($errors->any())
            <div class="bg-red-500/20 border border-red-500/40 rounded-xl p-4 mb-4 text-red-300">
                {{ $errors->first() }}
            </div>
        @endif

        <div class="space-y-4">
            @forelse($missions as $row)
                @php $mission = $row['mission']; $um = $row['user_mission']; @endphp
                <div class="card-golden p-5">
                    <div class="flex justify-between items-start mb-3">
                        <div>
                            <h3 class="font-bold text-gold">{{ $mission->name }}</h3>
                            <p class="text-xs text-gold-400/70">{{ $mission->description }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm text-gold-400">Reward</p>
                            <p class="font-bold text-green-400">KES {{ number_format($mission->reward_amount, 0) }}</p>
                        </div>
                    </div>

                    <div class="flex justify-between items-center text-xs text-ivory/70 mb-2">
                        <span>Progress: {{ $row['progress'] }} / {{ $row['target'] }}</span>
                        <span>{{ $row['progress_percent'] }}%</span>
                    </div>

                    <div class="w-full bg-gray-700 rounded-full h-2 mb-3">
                        <div class="bg-gradient-to-r from-gold-400 to-green-400 h-2 rounded-full"
                             style="width: {{ $row['progress_percent'] }}%"></div>
                    </div>

                    <div class="flex justify-end">
                        @if($row['claimed'])
                            <span class="text-xs text-green-400 font-bold">✅ Claimed</span>
                        @elseif($row['completed'])
                            <form method="POST" action="{{ route('lottery.missions.claim', $um) }}">
                                @csrf
                                <button class="btn-golden text-sm px-6 py-2">Claim Reward</button>
                            </form>
                        @else
                            <span class="text-xs text-gold-400/60">In progress…</span>
                        @endif
                    </div>
                </div>
            @empty
                <div class="card-golden p-10 text-center">
                    <p class="text-ivory/60">No missions active today.</p>
                </div>
            @endforelse
        </div>

    </div>
</div>
@endsection
