@extends('admin.layouts.app')

@section('content')
<div class="space-y-6">
    <div class="flex justify-between items-center">
        <h1 class="text-2xl font-bold text-gold">📊 Lottery Analytics</h1>
        <form method="GET">
            <select name="days" class="input-golden" onchange="this.form.submit()">
                @foreach([7, 14, 30, 60, 90] as $d)
                    <option value="{{ $d }}" @selected($days == $d)>{{ $d }} days</option>
                @endforeach
            </select>
        </form>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="admin-card p-4">
            <p class="text-xs text-gold-400 uppercase">Actual RTP</p>
            <p class="text-3xl font-bold text-green-400">{{ $rtp }}%</p>
        </div>
        <div class="admin-card p-4">
            <p class="text-xs text-gold-400 uppercase">Total Spins</p>
            <p class="text-2xl font-bold text-gold">{{ number_format(array_sum($spins)) }}</p>
        </div>
        <div class="admin-card p-4">
            <p class="text-xs text-gold-400 uppercase">Total Bets</p>
            <p class="text-xl font-bold text-gold">KES {{ number_format(array_sum($bets), 0) }}</p>
        </div>
        <div class="admin-card p-4">
            <p class="text-xs text-gold-400 uppercase">Total Wins</p>
            <p class="text-xl font-bold text-green-400">KES {{ number_format(array_sum($wins), 0) }}</p>
        </div>
    </div>

    <div class="admin-card p-5">
        <h3 class="text-lg font-bold text-gold mb-4">Daily Spins</h3>
        <canvas id="spinsChart" height="100"></canvas>
    </div>

    <div class="admin-card p-5">
        <h3 class="text-lg font-bold text-gold mb-4">Bets vs Wins (KES)</h3>
        <canvas id="moneyChart" height="100"></canvas>
    </div>

    {{-- RTP SIMULATOR --}}
    <div class="admin-card p-5">
        <h3 class="text-lg font-bold text-gold mb-3">RTP Simulator</h3>
        <div class="flex gap-2 items-end">
            <div class="flex-1">
                <label class="text-xs text-gold-400">Game</label>
                <select id="simGame" class="input-golden w-full">
                    @foreach(\App\Models\LotteryGame::all() as $g)
                        <option value="{{ $g->id }}">{{ $g->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-xs text-gold-400">Spins</label>
                <input type="number" id="simSpins" value="10000" min="100" max="100000" class="input-golden w-32">
            </div>
            <button onclick="runSim()" class="btn-golden px-4 py-2">Run</button>
        </div>
        <div id="simResult" class="mt-4 text-sm"></div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const labels = @json($labels);
new Chart(document.getElementById('spinsChart'), {
    type: 'line',
    data: { labels, datasets: [{ label: 'Spins', data: @json($spins), borderColor: '#D4AF37', tension: 0.4, fill: true }] },
    options: { responsive: true, plugins: { legend: { labels: { color: '#D4AF37' } } } }
});

new Chart(document.getElementById('moneyChart'), {
    type: 'bar',
    data: {
        labels,
        datasets: [
            { label: 'Bets (KES)', data: @json($bets), backgroundColor: '#D4AF37' },
            { label: 'Wins (KES)', data: @json($wins), backgroundColor: '#26A69A' }
        ]
    },
    options: { responsive: true, plugins: { legend: { labels: { color: '#D4AF37' } } } }
});

async function runSim() {
    const res = await fetch('{{ route('admin.lottery.rtp-simulate') }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({
            game_id: document.getElementById('simGame').value,
            spins: document.getElementById('simSpins').value
        })
    });
    const data = await res.json();
    document.getElementById('simResult').innerHTML = `
        <div class="grid grid-cols-2 md:grid-cols-5 gap-3 text-center">
            <div><p class="text-xs text-gold-400">Spins</p><p class="font-bold text-gold">${data.spins.toLocaleString()}</p></div>
            <div><p class="text-xs text-gold-400">Total Bet</p><p class="font-bold text-gold">KES ${data.total_bet.toLocaleString()}</p></div>
            <div><p class="text-xs text-gold-400">Total Win</p><p class="font-bold text-green-400">KES ${data.total_win.toLocaleString()}</p></div>
            <div><p class="text-xs text-gold-400">Actual RTP</p><p class="font-bold text-green-400">${data.actual_rtp}%</p></div>
            <div><p class="text-xs text-gold-400">Hit Rate</p><p class="font-bold text-gold">${data.hit_rate}%</p></div>
        </div>
        <p class="text-xs text-ivory/50 mt-3 text-center">Theoretical RTP: ${data.theoretical_rtp}% · Big Wins (≥1,000): ${data.big_wins}</p>
    `;
}
</script>
@endsection
