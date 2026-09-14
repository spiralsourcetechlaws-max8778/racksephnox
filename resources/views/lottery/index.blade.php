@extends('layouts.app')

@section('content')
<div class="py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        {{-- ═══════════ HEADER ═══════════ --}}
        <div class="text-center mb-8">
            <h1 class="text-4xl md:text-5xl font-bold golden-title">🎰 Cosmic Lottery</h1>
            <p class="text-gold-400 mt-2 sacred-phrase">
                Provably Fair · 8888 Hz · Golden Ratio Φ
            </p>
        </div>

        {{-- ═══════════ 4-TIER JACKPOT BAR ═══════════ --}}
        @if(isset($jackpots) && $jackpots->count())
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
                @foreach($jackpots as $jp)
                    <div class="card-golden p-4 text-center relative overflow-hidden">
                        <p class="text-xs uppercase tracking-widest text-gold-400">
                            {{ $jp->label }}
                        </p>
                        <p class="text-2xl font-bold text-gold mt-2">
                            KES {{ number_format($jp->current_pool, 0) }}
                        </p>
                        <p class="text-[10px] text-gold-400/60 mt-1">{{ $jp->frequency_hz }} Hz</p>
                        @if($jp->must_drop_soon)
                            <span class="absolute top-1 right-1 text-[10px] bg-red-500/30 text-red-300 px-2 py-0.5 rounded-full animate-pulse">
                                MUST DROP SOON
                            </span>
                        @endif
                        <div class="w-full bg-gray-700 rounded-full h-1 mt-3">
                            <div class="bg-gradient-to-r from-gold-400 to-green-400 h-1 rounded-full"
                                 style="width: {{ $jp->progress_percent }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        @if(!$game)
            <div class="card-golden p-10 text-center">
                <i class="fas fa-dice-d6 text-4xl text-gold/40 mb-4"></i>
                <p class="text-ivory/60">No lottery game is configured yet.</p>
            </div>
        @else
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                {{-- ═══════════ GAME SLOT ═══════════ --}}
                <div class="lg:col-span-2 card-golden p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-xl font-bold text-gold">{{ $game->name }}</h2>
                        <span class="text-xs bg-gold/20 text-gold px-3 py-1 rounded-full">
                            RTP {{ $game->base_rtp }}%
                        </span>
                    </div>

                    {{-- ═══ REEL GRID (emoji symbols) ═══ --}}
                    <div id="reelGrid" class="grid grid-cols-5 gap-2 mb-6 bg-black/40 rounded-xl p-4"></div>

                    {{-- ═══ SPIN CONTROLS ═══ --}}
                    <div class="flex flex-col md:flex-row gap-3 items-center justify-between">
                        <div>
                            <p class="text-xs text-gold-400">Bet</p>
                            <p class="text-lg font-bold text-gold">KES {{ number_format($game->ticket_price, 2) }}</p>
                        </div>

                        <div class="flex gap-3">
                            @if($canFreeSpin)
                                <button id="freeSpinBtn" class="btn-outline-silver px-6 py-3">
                                    🎁 Free Spin
                                </button>
                            @endif

                            <button id="spinBtn"
                                    class="btn-golden px-10 py-3 text-lg font-bold disabled:opacity-50">
                                🎰 SPIN
                            </button>
                        </div>

                        <div class="text-right">
                            <p class="text-xs text-gold-400">Balance</p>
                            <p class="text-lg font-bold text-gold" id="balanceDisplay">
                                KES {{ number_format($balance, 2) }}
                            </p>
                        </div>
                    </div>

                    {{-- ═══ WIN MESSAGE ═══ --}}
                    <div id="winMessage" class="hidden mt-4 p-4 rounded-xl text-center font-bold text-xl"></div>
                </div>

                {{-- ═══════════ SIDE PANEL ═══════════ --}}
                <div class="space-y-5">

                    {{-- Missions --}}
                    <div class="card-golden p-5">
                        <div class="flex justify-between items-center mb-3">
                            <h3 class="text-sm font-bold text-gold uppercase">Missions</h3>
                            <a href="{{ route('lottery.missions') }}" class="text-xs text-gold-400">View all →</a>
                        </div>
                        <p class="text-2xl font-bold text-gold mb-3">
                            {{ $completedMissions }}/{{ $totalMissions }}
                        </p>
                        <div class="w-full bg-gray-700 rounded-full h-2">
                            <div class="bg-gradient-to-r from-gold-400 to-green-400 h-2 rounded-full"
                                 style="width: {{ $totalMissions > 0 ? ($completedMissions / $totalMissions) * 100 : 0 }}%"></div>
                        </div>
                    </div>

                    {{-- Bonus Wheel --}}
                    @if($canSpinBonusWheel)
                        <div class="card-golden p-5 text-center animate-pulse">
                            <p class="text-gold font-bold mb-2">🎡 Bonus Wheel Ready!</p>
                            <a href="{{ route('lottery.bonus-wheel') }}" class="btn-golden w-full text-sm py-2">
                                Spin the Wheel
                            </a>
                        </div>
                    @endif

                    {{-- Tournament --}}
                    @if($activeTournament)
                        <div class="card-golden p-5">
                            <h3 class="text-sm font-bold text-gold uppercase mb-2">🏆 Live Tournament</h3>
                            <p class="text-ivory font-bold">{{ $activeTournament->name }}</p>
                            <p class="text-xs text-gold-400/70 mb-2">
                                Ends {{ $activeTournament->end_date->diffForHumans() }}
                            </p>
                            <p class="text-lg text-green-400 font-bold">
                                Prize Pool: KES {{ number_format($activeTournament->prize_pool, 0) }}
                            </p>
                        </div>
                    @endif

                    {{-- Leaderboard --}}
                    <div class="card-golden p-5">
                        <div class="flex justify-between items-center mb-3">
                            <h3 class="text-sm font-bold text-gold uppercase">Weekly Leaders</h3>
                            <a href="{{ route('lottery.leaderboard') }}" class="text-xs text-gold-400">All →</a>
                        </div>
                        @forelse($leaderboard as $i => $row)
                            <div class="flex justify-between items-center text-sm border-b border-gold/10 py-1">
                                <span class="text-ivory/80">
                                    {{ $i + 1 }}. {{ $row->user?->name ?? 'Player' }}
                                </span>
                                <span class="text-gold font-bold">
                                    KES {{ number_format($row->total_win, 0) }}
                                </span>
                            </div>
                        @empty
                            <p class="text-xs text-ivory/50 text-center py-2">No spins yet this week</p>
                        @endforelse
                    </div>

                    {{-- Recent --}}
                    <div class="card-golden p-5">
                        <div class="flex justify-between items-center mb-3">
                            <h3 class="text-sm font-bold text-gold uppercase">Recent Spins</h3>
                            <a href="{{ route('lottery.history') }}" class="text-xs text-gold-400">All →</a>
                        </div>
                        @forelse($history->take(5) as $spin)
                            <div class="flex justify-between items-center text-xs border-b border-gold/10 py-1">
                                <span class="text-ivory/60">{{ $spin->created_at->format('H:i') }}</span>
                                <span class="{{ $spin->win_amount > 0 ? 'text-green-400' : 'text-red-400' }}">
                                    {{ $spin->win_amount > 0 ? '+' : '' }}{{ number_format($spin->win_amount - $spin->bet_amount, 0) }}
                                </span>
                            </div>
                        @empty
                            <p class="text-xs text-ivory/50 text-center py-2">No spins yet</p>
                        @endforelse
                    </div>

                </div>
            </div>
        @endif

    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════ --}}
{{-- REEL LOGIC — EMOJI SYMBOLS (works everywhere)              --}}
{{-- ═══════════════════════════════════════════════════════════ --}}
<script>
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

/* ───── Emoji map for every possible symbol name ───── */
const SYMBOL_EMOJI = {
    // Divine / high-value
    'seven':   '7️⃣',
    'seven7':  '7️⃣',
    'crown':   '👑',
    'diamond': '💎',
    'gem':     '💎',
    'star':    '⭐',
    'divine':  '🌟',
    // Mid-value
    'bell':    '🔔',
    'coin':    '🪙',
    'coins':   '🪙',
    'clover':  '🍀',
    'horseshoe':'🧲',
    // Low-value / fruit classics
    'cherry':  '🍒',
    'lemon':   '🍋',
    'orange':  '🍊',
    'grape':   '🍇',
    'watermelon':'🍉',
    'apple':   '🍎',
    // Fallback
    'default': '🎰',
};

function emojiFor(name) {
    if (!name) return SYMBOL_EMOJI.default;
    const key = String(name).toLowerCase().replace(/[^a-z0-9]/g, '');
    return SYMBOL_EMOJI[key] || SYMBOL_EMOJI[name] || SYMBOL_EMOJI.default;
}

/* ───── Default layout (before first spin) ───── */
const PLACEHOLDER_LAYOUT = [
    ['🍒','🍋','🪙'],
    ['🔔','💎','🍇'],
    ['⭐','🍊','🍀'],
    ['💎','🍉','🔔'],
    ['🪙','⭐','🍒'],
];

/* ───── Render a full 5×3 grid ───── */
function renderGrid(columns) {
    const grid = document.getElementById('reelGrid');
    if (!grid) return;

    grid.innerHTML = '';

    for (let col = 0; col < 5; col++) {
        const columnEl = document.createElement('div');
        columnEl.className = 'flex flex-col gap-2';

        for (let row = 0; row < 3; row++) {
            const cell = document.createElement('div');
            const isMiddle = row === 1;

            cell.className =
                'reel-cell flex items-center justify-center rounded-lg h-16 text-3xl transition-all duration-200 ' +
                (isMiddle
                    ? 'bg-gold/15 border-2 border-gold shadow-[0_0_12px_rgba(212,175,55,0.35)]'
                    : 'bg-black/40 border border-gold/20');

            // Safely get the symbol — if it's not a string, treat as empty
            const symbolName = (columns[col] && columns[col][row]) ? columns[col][row] : null;
            cell.textContent = symbolName
                ? emojiFor(symbolName)
                : (isMiddle ? '⭐' : '🪙');

            columnEl.appendChild(cell);
        }

        grid.appendChild(columnEl);
    }
}

/* ───── Animate a spin column-by-column ───── */
async function animateSpin(finalLine) {
    // Ensure finalLine is a flat array of 5 symbols
    const flat = Array.isArray(finalLine) ? finalLine.slice(0, 5) : [];
    while (flat.length < 5) flat.push('coin');

    const grid = document.getElementById('reelGrid');
    if (!grid) return;

    // Build initial random board
    const board = [];
    const pool = Object.keys(SYMBOL_EMOJI).filter(k => k !== 'default' && k !== 'seven7');
    for (let col = 0; col < 5; col++) {
        board[col] = [
            pool[Math.floor(Math.random() * pool.length)],
            pool[Math.floor(Math.random() * pool.length)],
            pool[Math.floor(Math.random() * pool.length)],
        ];
    }
    renderGrid(board);

    // Reveal one column at a time, ending on the final symbol in the middle row
    for (let col = 0; col < 5; col++) {
        board[col][1] = flat[col];
        renderGrid(board);
        await new Promise(r => setTimeout(r, 140));
    }
}

/* ───── SPIN button ───── */
document.getElementById('spinBtn')?.addEventListener('click', async function () {
    const btn = this;
    btn.disabled = true;
    btn.textContent = 'Spinning...';
    document.getElementById('winMessage').classList.add('hidden');

    try {
        const res = await fetch('{{ route('lottery.spin') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify({}),
        });
        const data = await res.json();

        if (!data.success) {
            showWin(data.message || 'Spin failed', 'error');
            return;
        }

        await animateSpin(data.spin.symbols);

        const display = document.getElementById('balanceDisplay');
        if (display) display.textContent = 'KES ' + Number(data.balance).toLocaleString();

        if (data.spin.win > 0) {
            showWin(
                `✨ WIN: KES ${Number(data.spin.win).toLocaleString()}` +
                (data.spin.jackpot > 0
                    ? ` + JACKPOT ${String(data.spin.jackpot_tier || '').toUpperCase()}!`
                    : ''),
                'win'
            );
        } else {
            showWin('No win this time — spin again!', 'neutral');
        }
    } catch (e) {
        console.error(e);
        showWin('Network error', 'error');
    } finally {
        btn.disabled = false;
        btn.textContent = '🎰 SPIN';
    }
});

function showWin(message, type) {
    const el = document.getElementById('winMessage');
    if (!el) return;
    el.textContent = message;
    el.className = 'mt-4 p-4 rounded-xl text-center font-bold text-xl ' + (
        type === 'win'   ? 'bg-green-500/20 text-green-300' :
        type === 'error' ? 'bg-red-500/20 text-red-300' :
                           'bg-gold/10 text-gold-400'
    );
    el.classList.remove('hidden');
}

/* ───── FREE SPIN button ───── */
document.getElementById('freeSpinBtn')?.addEventListener('click', async function () {
    this.disabled = true;
    try {
        const res = await fetch('{{ route('lottery.free-spin') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
        });
        const data = await res.json();
        if (data.success) {
            await animateSpin(data.spin.symbols);
            showWin(`🎁 Free Spin: +KES ${Number(data.spin.win).toLocaleString()}`, 'win');
            const display = document.getElementById('balanceDisplay');
            if (display) display.textContent = 'KES ' + Number(data.balance).toLocaleString();
        } else {
            showWin(data.message || 'Not available', 'error');
        }
    } catch (e) {
        showWin('Network error', 'error');
    } finally {
        this.disabled = false;
    }
});

/* ───── Initial board render ───── */
document.addEventListener('DOMContentLoaded', () => {
    renderGrid(PLACEHOLDER_LAYOUT);
});
</script>
@endsection
