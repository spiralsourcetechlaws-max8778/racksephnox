@extends('layouts.app')

@section('content')
<div class="py-8">
    <div class="max-w-5xl mx-auto px-4 sm:px-6">

        <a href="{{ route('lottery.index') }}" class="text-gold-400 text-sm">
            <i class="fas fa-arrow-left"></i> Back to Lottery
        </a>

        <h1 class="text-3xl font-bold golden-title mt-4 mb-2 text-center">🏅 Achievements</h1>
        <p class="text-gold-400 text-center text-sm mb-8">
            {{ collect($progress)->where('earned', true)->count() }} / {{ count($progress) }} unlocked
        </p>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($progress as $p)
                @php
                    $a = $p['achievement'];
                    $icon = $a->icon ?? '🏆';
                    $isEmoji = preg_match('/[\x{1F300}-\x{1FAFF}\x{2600}-\x{27BF}]/u', $icon);
                @endphp
                <div class="card-golden p-4 {{ $p['earned'] ? 'ring-2 ring-gold/50' : 'opacity-70' }}">
                    <div class="flex items-start gap-3">
                        <div class="w-12 h-12 rounded-full bg-gold/20 flex items-center justify-center flex-shrink-0 text-2xl">
                            @if($isEmoji)
                                <span>{{ $icon }}</span>
                            @else
                                <i class="fas {{ $icon }} text-gold"></i>
                            @endif
                        </div>
                        <div class="flex-1">
                            <h3 class="font-bold text-gold">{{ $a->name }}</h3>
                            <p class="text-xs text-ivory/60">{{ $a->description }}</p>
                            @if($p['earned'])
                                <p class="text-xs text-green-400 mt-1">✅ Unlocked</p>
                            @else
                                <p class="text-xs text-gold-400/70 mt-1">
                                    {{ $p['current'] }} / {{ $p['target'] }} ({{ $p['progress_percent'] }}%)
                                </p>
                                <div class="w-full bg-gray-700 rounded-full h-1 mt-1">
                                    <div class="bg-gold h-1 rounded-full" style="width: {{ $p['progress_percent'] }}%"></div>
                                </div>
                            @endif
                        </div>
                    </div>
                    <div class="text-right mt-2">
                        <span class="text-xs bg-gold/20 text-gold px-2 py-0.5 rounded-full">
                            +KES {{ number_format($a->reward_amount, 0) }}
                        </span>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
@endsection
