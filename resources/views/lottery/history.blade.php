@extends('layouts.app')

@section('content')
<div class="py-8">
    <div class="max-w-4xl mx-auto px-4 sm:px-6">

        <a href="{{ route('lottery.index') }}" class="text-gold-400 text-sm">
            <i class="fas fa-arrow-left"></i> Back to Lottery
        </a>

        <h1 class="text-3xl font-bold golden-title mt-4 mb-6">📜 Spin History</h1>

        @php
            $emojiMap = [
                'seven' => '7️⃣', 'crown' => '👑', 'diamond' => '💎', 'gem' => '💎',
                'star' => '⭐', 'bell' => '🔔', 'coin' => '🪙', 'clover' => '🍀',
                'cherry' => '🍒', 'lemon' => '🍋', 'orange' => '🍊', 'grape' => '🍇',
                'watermelon' => '🍉', 'apple' => '🍎', 'divine' => '🌟',
            ];
            $emojiFor = fn ($name) => $emojiMap[strtolower($name)] ?? '🎰';
        @endphp

        <div class="card-golden p-4 overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="border-b border-gold/30 text-gold-400 text-xs uppercase">
                    <tr>
                        <th class="text-left p-2">Date</th>
                        <th class="text-left p-2">Game</th>
                        <th class="text-left p-2">Symbols</th>
                        <th class="text-right p-2">Bet</th>
                        <th class="text-right p-2">Win</th>
                        <th class="text-right p-2">Net</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($spins as $spin)
                        <tr class="border-b border-gold/10 hover:bg-gold/5">
                            <td class="p-2 text-ivory/70">{{ $spin->created_at->format('M d, H:i') }}</td>
                            <td class="p-2">{{ $spin->game->name ?? '—' }}</td>
                            <td class="p-2 text-2xl tracking-wider">
                                @foreach(($spin->symbols ?? []) as $symbol)
                                    <span title="{{ $symbol }}">{{ $emojiFor($symbol) }}</span>
                                @endforeach
                            </td>
                            <td class="p-2 text-right text-ivory/70">{{ number_format($spin->bet_amount, 0) }}</td>
                            <td class="p-2 text-right text-green-400 font-bold">{{ number_format($spin->win_amount, 0) }}</td>
                            <td class="p-2 text-right {{ $spin->net_result >= 0 ? 'text-green-400' : 'text-red-400' }}">
                                {{ $spin->net_result >= 0 ? '+' : '' }}{{ number_format($spin->net_result, 0) }}
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="p-8 text-center text-ivory/50">No spins yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $spins->links() }}</div>
    </div>
</div>
@endsection
