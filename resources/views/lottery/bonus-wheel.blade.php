@extends('layouts.app')

@section('content')
<div class="py-8">
    <div class="max-w-3xl mx-auto px-4 sm:px-6">

        <a href="{{ route('lottery.index') }}" class="text-gold-400 text-sm">
            <i class="fas fa-arrow-left"></i> Back to Lottery
        </a>

        <h1 class="text-3xl font-bold golden-title mt-4 mb-2 text-center">🎡 Bonus Wheel</h1>
        <p class="text-gold-400 text-center text-sm mb-8">Spin once every 24 hours</p>

        <div class="card-golden p-8 text-center">
            <div id="wheelResult" class="text-5xl mb-6">🎁</div>

            @if($canSpin)
                <button id="spinWheelBtn" class="btn-golden px-12 py-4 text-xl font-bold">
                    SPIN THE WHEEL
                </button>
            @else
                <p class="text-gold-400 text-lg">Wheel not available right now.</p>
                <p class="text-xs text-ivory/50 mt-2">Requires 5+ spins today and 24h since last wheel.</p>
            @endif

            <div id="wheelMessage" class="hidden mt-6 p-4 rounded-xl text-xl font-bold"></div>
        </div>

        @if($history->count())
            <h2 class="text-lg font-bold text-gold mt-8 mb-3">Recent Wheel Spins</h2>
            <div class="space-y-2">
                @foreach($history as $h)
                    <div class="card-golden p-3 flex justify-between items-center text-sm">
                        <span class="text-ivory/70">{{ $h->created_at->format('M d, H:i') }}</span>
                        <span class="text-gold">
                            {{ $h->reward_type === 'cash' ? 'KES ' . number_format($h->reward_amount, 0) :
                               ($h->reward_type === 'free_spins' ? $h->reward_amount . ' Free Spins' : 'Boost') }}
                        </span>
                    </div>
                @endforeach
            </div>
        @endif

    </div>
</div>

<script>
const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';

document.getElementById('spinWheelBtn')?.addEventListener('click', async function () {
    const btn = this;
    btn.disabled = true;
    btn.textContent = 'Spinning...';

    // animation flicker
    const resultEl = document.getElementById('wheelResult');
    const icons = ['🎁', '💎', '⭐', '💰', '🎰', '🌟', '💵', '🎯'];
    let ticks = 0;
    const interval = setInterval(() => {
        resultEl.textContent = icons[Math.floor(Math.random() * icons.length)];
        if (++ticks > 30) clearInterval(interval);
    }, 60);

    try {
        const res = await fetch('{{ route('lottery.bonus-wheel.spin') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrf,
            },
        });
        const data = await res.json();
        setTimeout(() => {
            clearInterval(interval);
            if (data.success) {
                resultEl.textContent = '🎉';
                const msg = document.getElementById('wheelMessage');
                msg.textContent = data.type === 'cash'
                    ? `You won KES ${data.reward.toLocaleString()}!`
                    : data.type === 'free_spins'
                        ? `You won ${data.reward} free spins!`
                        : 'Lucky boost!';
                msg.className = 'mt-6 p-4 rounded-xl text-xl font-bold bg-green-500/20 text-green-300';
                msg.classList.remove('hidden');
                btn.style.display = 'none';
            } else {
                alert(data.message || 'Failed');
                btn.disabled = false;
                btn.textContent = 'SPIN THE WHEEL';
            }
        }, 1900);
    } catch (e) {
        clearInterval(interval);
        alert('Network error');
        btn.disabled = false;
        btn.textContent = 'SPIN THE WHEEL';
    }
});
</script>
@endsection
