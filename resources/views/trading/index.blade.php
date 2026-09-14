@extends('layouts.app')

@section('title', 'Bitcoin Trading Console')

@section('content')
<div id="tradingRoot" class="py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        {{-- HEADER --}}
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
            <div>
                <h1 class="text-3xl md:text-4xl font-bold golden-title flex items-center gap-3 flex-wrap">
                    ₿ Bitcoin Trading Console
                    <span id="connBadge"
                          class="text-[10px] px-2 py-1 rounded-full bg-gray-500/30 text-gray-300 font-normal tracking-wider">
                        <span id="connDot" class="inline-block w-1.5 h-1.5 rounded-full bg-gray-400 mr-1"></span>
                        <span id="connText">CONNECTING</span>
                    </span>
                </h1>
                <p class="text-gold-400 text-sm mt-1">
                    Live Market Price:
                    <span id="liveBtcPrice" class="text-gold font-bold">
                        KES {{ number_format($btcPrice ?? 0, 2) }}
                    </span>
                    <span id="priceDelta" class="text-xs ml-2"></span>
                </p>
            </div>
            <div class="flex gap-4">
                <div class="text-right">
                    <p class="text-xs text-gold-400 uppercase tracking-widest">Trading Balance</p>
                    <p class="text-xl md:text-2xl font-bold text-gold">
                        KES {{ number_format($tradingAccount->balance ?? 0, 2) }}
                    </p>
                </div>
                <div class="text-right">
                    <p class="text-xs text-gold-400 uppercase tracking-widest">BTC Balance</p>
                    <p class="text-xl md:text-2xl font-bold text-gold">
                        {{ number_format($tradingAccount->btc_balance ?? 0, 8) }} BTC
                    </p>
                </div>
            </div>
        </div>

        {{-- FLASH --}}
        @if(session('success'))
            <div class="mb-4 bg-green-500/20 border border-green-500/40 rounded-xl p-3 text-green-300 text-sm">
                <i class="fas fa-check-circle mr-1"></i> {{ session('success') }}
            </div>
        @endif
        @if($errors->any())
            <div class="mb-4 bg-red-500/20 border border-red-500/40 rounded-xl p-3 text-red-300 text-sm">
                <i class="fas fa-exclamation-circle mr-1"></i> {{ $errors->first() }}
            </div>
        @endif

        {{-- CHART + ORDER ENTRY --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">

            <div class="lg:col-span-2 card-golden p-5" id="chartPanel">
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-4 gap-3">
                    <h2 class="text-lg font-bold text-gold flex items-center gap-2">
                        📈 Price Chart
                        <span id="countdown" class="text-[10px] text-gold-400/60 font-normal"></span>
                    </h2>
                    <div class="flex flex-wrap gap-1.5 items-center">
                        @foreach(['1m','5m','15m','1h','4h','1d'] as $iv)
                            <button onclick="changeTimeframe('{{ $iv }}')"
                                    data-interval="{{ $iv }}"
                                    class="interval-btn btn-outline-silver text-xs px-3 py-1 transition
                                           {{ $iv === '1h' ? 'active' : '' }}">
                                {{ $iv }}
                            </button>
                        @endforeach
                        <span class="w-px h-5 bg-gold/20 mx-1"></span>
                        <button onclick="toggleFullscreen()" title="Fullscreen (F)" class="btn-outline-silver text-xs px-2 py-1">⛶</button>
                        <button onclick="screenshotChart()" title="Screenshot (S)" class="btn-outline-silver text-xs px-2 py-1">📷</button>
                        <button onclick="resetZoom()" title="Reset (R)" class="btn-outline-silver text-xs px-2 py-1">🔍</button>
                    </div>
                </div>

                <div id="chart" style="height: 420px; width: 100%; position: relative; touch-action: none;"></div>

                <div class="flex justify-between items-center mt-2 gap-2">
                    <div id="chartStatus" class="text-xs text-gold-400 min-h-[18px]">Initialising chart…</div>
                    <div id="crosshairPanel" class="text-[10px] text-gold-400/70 font-mono hidden"></div>
                </div>

                <div class="mt-3 flex flex-wrap gap-2 justify-center">
                    @foreach(['MA','EMA','BOLL','MACD','RSI','KDJ','OBV'] as $ind)
                        <button onclick="toggleIndicator('{{ $ind }}')"
                                data-ind="{{ $ind }}"
                                class="indicator-btn btn-outline-silver text-xs px-3 py-1">{{ $ind }}</button>
                    @endforeach
                </div>

                <p class="text-[10px] text-gold-400/40 text-center mt-2">
                    <kbd class="px-1 bg-gold/10 rounded">1-6</kbd> timeframes ·
                    <kbd class="px-1 bg-gold/10 rounded">F</kbd> fullscreen ·
                    <kbd class="px-1 bg-gold/10 rounded">S</kbd> screenshot ·
                    <kbd class="px-1 bg-gold/10 rounded">R</kbd> reset
                </p>
            </div>

            <div class="card-golden p-5">
                <h2 class="text-lg font-bold text-gold mb-4">⚡ Place Order</h2>

                <div class="flex gap-2 mb-4">
                    <button id="buyTab"  class="flex-1 py-2 rounded-lg font-bold transition-all bg-green-500/25 text-green-400">BUY</button>
                    <button id="sellTab" class="flex-1 py-2 rounded-lg font-bold transition-all bg-red-500/10 text-red-400/60">SELL</button>
                </div>

                <div id="buyForm">
                    <form method="POST" action="{{ route('trading.buy') }}" class="space-y-3">
                        @csrf
                        <div>
                            <label class="text-xs text-gold-400 block mb-1">Amount (BTC)</label>
                            <input type="number" step="0.00000001" min="0.0001" name="amount_btc"
                                   class="input-golden w-full" placeholder="0.00000000" required>
                        </div>
                        <div>
                            <label class="text-xs text-gold-400 block mb-1">Order Type</label>
                            <select name="order_type" class="input-golden w-full order-type-select">
                                <option value="market">Market</option>
                                <option value="limit">Limit</option>
                                <option value="stop">Stop</option>
                            </select>
                        </div>
                        <div class="limit-fields hidden">
                            <label class="text-xs text-gold-400 block mb-1">Limit Price (KES)</label>
                            <input type="number" step="0.01" name="price" class="input-golden w-full" placeholder="0.00">
                        </div>
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label class="text-xs text-gold-400 block mb-1">Take Profit</label>
                                <input type="number" step="0.01" name="take_profit" class="input-golden w-full" placeholder="Optional">
                            </div>
                            <div>
                                <label class="text-xs text-gold-400 block mb-1">Stop Loss</label>
                                <input type="number" step="0.01" name="stop_loss" class="input-golden w-full" placeholder="Optional">
                            </div>
                        </div>
                        <button type="submit" class="btn-golden w-full mt-2 py-3 font-bold">🟢 Buy BTC</button>
                    </form>
                </div>

                <div id="sellForm" class="hidden">
                    <form method="POST" action="{{ route('trading.sell') }}" class="space-y-3">
                        @csrf
                        <div>
                            <label class="text-xs text-gold-400 block mb-1">Amount (BTC)</label>
                            <input type="number" step="0.00000001" min="0.0001" name="amount_btc"
                                   class="input-golden w-full" placeholder="0.00000000" required>
                        </div>
                        <div>
                            <label class="text-xs text-gold-400 block mb-1">Order Type</label>
                            <select name="order_type" class="input-golden w-full order-type-select">
                                <option value="market">Market</option>
                                <option value="limit">Limit</option>
                                <option value="stop">Stop</option>
                            </select>
                        </div>
                        <div class="limit-fields hidden">
                            <label class="text-xs text-gold-400 block mb-1">Limit Price (KES)</label>
                            <input type="number" step="0.01" name="price" class="input-golden w-full" placeholder="0.00">
                        </div>
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label class="text-xs text-gold-400 block mb-1">Take Profit</label>
                                <input type="number" step="0.01" name="take_profit" class="input-golden w-full" placeholder="Optional">
                            </div>
                            <div>
                                <label class="text-xs text-gold-400 block mb-1">Stop Loss</label>
                                <input type="number" step="0.01" name="stop_loss" class="input-golden w-full" placeholder="Optional">
                            </div>
                        </div>
                        <button type="submit" class="btn-golden w-full mt-2 py-3 font-bold bg-gradient-to-r from-red-500 to-rose-600">🔴 Sell BTC</button>
                    </form>
                </div>
            </div>
        </div>

        {{-- ORDER BOOK + OPEN ORDERS --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <div class="card-golden p-5">
                <h3 class="text-lg font-bold text-gold mb-3">📖 Order Book</h3>
                <div id="orderBook" class="h-72 overflow-y-auto text-sm custom-scroll">
                    <p class="text-center text-ivory/50 py-6">Loading order book…</p>
                </div>
            </div>

            <div class="card-golden p-5">
                <div class="flex justify-between items-center mb-3">
                    <h3 class="text-lg font-bold text-gold">⏳ Open Orders</h3>
                    @if($openOrders->count())
                        <span class="text-xs bg-gold/20 text-gold px-2 py-0.5 rounded-full">{{ $openOrders->count() }}</span>
                    @endif
                </div>

                @if($openOrders->count())
                    <div class="space-y-2 max-h-72 overflow-y-auto custom-scroll">
                        @foreach($openOrders as $order)
                            <div class="flex justify-between items-center p-3 bg-gold/5 rounded-lg hover:bg-gold/10 transition">
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2 mb-1">
                                        <span class="text-xs font-bold px-2 py-0.5 rounded-full
                                            {{ $order->side === 'buy' ? 'bg-green-500/20 text-green-400' : 'bg-red-500/20 text-red-400' }}">
                                            {{ strtoupper($order->side) }}
                                        </span>
                                        <span class="text-xs text-gold-400 uppercase">{{ $order->order_type }}</span>
                                    </div>
                                    <p class="text-sm text-ivory">{{ number_format($order->amount_btc, 8) }} BTC</p>
                                    <p class="text-xs text-gold-400/70">@ KES {{ number_format($order->limit_price ?? 0, 2) }}</p>
                                </div>
                                <form action="{{ route('trading.cancel', $order) }}" method="POST"
                                      onsubmit="return confirm('Cancel this order?')">
                                    @csrf
                                    <button class="text-red-400 text-xs hover:text-red-300 px-2 py-1">✕ Cancel</button>
                                </form>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-ivory/40 text-sm text-center py-10">No open orders</p>
                @endif
            </div>
        </div>

        {{-- ORDER HISTORY --}}
        <div class="card-golden p-5">
            <h3 class="text-lg font-bold text-gold mb-4">📋 Recent Completed Orders</h3>

            @if($orderHistory->count())
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="border-b border-gold/30 text-gold-400 text-xs uppercase">
                            <tr>
                                <th class="text-left p-2">Date</th>
                                <th class="text-left p-2">Side</th>
                                <th class="text-right p-2">Amount (BTC)</th>
                                <th class="text-right p-2">Price (KES)</th>
                                <th class="text-right p-2">Total (KES)</th>
                                <th class="text-center p-2">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($orderHistory as $order)
                                <tr class="border-b border-gold/10 hover:bg-gold/5 transition">
                                    <td class="p-2 text-ivory/70 text-xs">{{ $order->created_at->format('M d, H:i') }}</td>
                                    <td class="p-2"><span class="text-xs font-bold {{ $order->side === 'buy' ? 'text-green-400' : 'text-red-400' }}">{{ strtoupper($order->side) }}</span></td>
                                    <td class="p-2 text-right text-ivory">{{ number_format($order->amount_btc, 8) }}</td>
                                    <td class="p-2 text-right text-ivory">{{ number_format($order->price_per_btc ?? $order->limit_price ?? 0, 2) }}</td>
                                    <td class="p-2 text-right text-gold font-semibold">{{ number_format(($order->price_per_btc ?? $order->limit_price ?? 0) * $order->amount_btc, 2) }}</td>
                                    <td class="p-2 text-center"><span class="text-xs px-2 py-0.5 rounded-full bg-green-500/20 text-green-400">✓ Completed</span></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="text-ivory/40 text-sm text-center py-10">No completed orders yet</p>
            @endif
        </div>

        <div class="text-center mt-10 pt-6 border-t border-gold/20">
            <p class="text-xs text-gold-400/60 italic">Φ = 1.61803398875 · λ = 1.27201964951 · π = 3.14159265359 · e = 2.71828182846</p>
            <p class="text-xs text-gold-500/40 mt-1">Racksephnox — Infinite Spiral of Creation · 888 Hz</p>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/klinecharts/dist/klinecharts.min.js"></script>
<script>
(function () {
    'use strict';

    const CONFIG = {
        candleRefreshMs:    5000,
        orderBookRefreshMs: 3000,
        maxRetries:         10,
        backoffBase:        1000,
        backoffMax:         30000,
        visibilityPause:    true,
        timezone:           'Africa/Nairobi',
    };

    const state = {
        chart:            null,
        currentInterval:  '1h',
        candleTimer:      null,
        orderBookTimer:   null,
        countdownTimer:   null,
        candleRetries:    0,
        bookRetries:      0,
        lastPrice:        null,
        activeIndicators: {},
        isFullscreen:     false,
        crosshairOn:      false,
        paused:           false,
    };

    const el = {
        chart:      document.getElementById('chart'),
        status:     document.getElementById('chartStatus'),
        book:       document.getElementById('orderBook'),
        countdown:  document.getElementById('countdown'),
        connBadge:  document.getElementById('connBadge'),
        connDot:    document.getElementById('connDot'),
        connText:   document.getElementById('connText'),
        price:      document.getElementById('liveBtcPrice'),
        priceDelta: document.getElementById('priceDelta'),
        panel:      document.getElementById('chartPanel'),
        crosshair:  document.getElementById('crosshairPanel'),
    };

    const fmtKes = (v, decimals = 2) =>
        'KES ' + Number(v || 0).toLocaleString('en-KE', {
            minimumFractionDigits: decimals, maximumFractionDigits: decimals,
        });

    const setStatus = (msg, isErr = false) => {
        if (!el.status) return;
        el.status.textContent = msg;
        el.status.style.color = isErr ? '#EF5350' : '#D4AF37';
    };

    const setConnection = (mode) => {
        const map = {
            live:         { color: 'bg-green-400',  badge: 'bg-green-500/20 text-green-300',  label: 'LIVE' },
            connecting:   { color: 'bg-yellow-400', badge: 'bg-yellow-500/20 text-yellow-300', label: 'CONNECTING' },
            reconnecting: { color: 'bg-orange-400', badge: 'bg-orange-500/20 text-orange-300', label: 'RECONNECTING' },
            offline:      { color: 'bg-red-500',    badge: 'bg-red-500/20 text-red-300',       label: 'OFFLINE' },
        };
        const s = map[mode] || map.connecting;
        el.connDot?.classList.remove('bg-gray-400','bg-green-400','bg-yellow-400','bg-orange-400','bg-red-500');
        el.connDot?.classList.add(s.color);
        el.connBadge?.classList.remove('bg-gray-500/30','bg-green-500/20','bg-yellow-500/20','bg-orange-500/20','bg-red-500/20');
        el.connBadge?.classList.add(...s.badge.split(' '));
        el.connText && (el.connText.textContent = s.label);
    };

    const toMillis = (raw) => {
        if (raw === null || raw === undefined || raw === '') return null;
        if (typeof raw === 'number') return raw > 1e12 ? Math.floor(raw) : Math.floor(raw * 1000);
        const s = String(raw);
        const iso = s.includes('T') ? s : s.replace(' ', 'T');
        const ms = new Date(iso).getTime();
        return Number.isFinite(ms) ? ms : null;
    };

    const num = (v, d = 0) => {
        const n = parseFloat(v);
        return Number.isFinite(n) ? n : d;
    };

    function initChart() {
        if (!el.chart || typeof klinecharts === 'undefined') {
            setStatus('⚠️ Chart library failed to load', true);
            setConnection('offline');
            return;
        }

        state.chart = klinecharts.init(el.chart, {
            styles: {
                grid: {
                    show: true,
                    horizontal: { color: 'rgba(51,65,85,0.35)' },
                    vertical:   { color: 'rgba(51,65,85,0.35)' },
                },
                candle: {
                    bar: {
                        upColor:   '#26A69A', downColor: '#EF5350',
                        upBorderColor: '#26A69A', downBorderColor: '#EF5350',
                        upWickColor:   '#26A69A', downWickColor:   '#EF5350',
                    },
                    priceMark: {
                        last: { show: true, upColor: '#26A69A', downColor: '#EF5350',
                                text: { color: '#0F172A', size: 11 } },
                        high: { show: false }, low: { show: false },
                    },
                },
                xAxis: {
                    axisLine: { color: 'rgba(212,175,55,0.3)' },
                    tickLine: { color: 'rgba(212,175,55,0.3)' },
                    tickText: { color: '#D4AF37', size: 11 },
                },
                yAxis: {
                    axisLine: { color: 'rgba(212,175,55,0.3)' },
                    tickLine: { color: 'rgba(212,175,55,0.3)' },
                    tickText: { color: '#D4AF37', size: 11 },
                },
                crosshair: {
                    horizontal: { line: { color: '#D4AF37', style: 'dashed' },
                                  text: { backgroundColor: '#D4AF37', color: '#0F172A' } },
                    vertical:   { line: { color: '#D4AF37', style: 'dashed' },
                                  text: { backgroundColor: '#D4AF37', color: '#0F172A' } },
                },
            },
            timezone: CONFIG.timezone,
        });

        try {
            state.chart.subscribeAction('onCrosshairChange', (data) => {
                if (!data || !data.kLineData) {
                    el.crosshair?.classList.add('hidden');
                    state.crosshairOn = false;
                    return;
                }
                const k = data.kLineData;
                const time = new Date(k.timestamp).toLocaleString('en-KE', { timeZone: CONFIG.timezone });
                el.crosshair.innerHTML =
                    `<span class="text-gold">${time}</span> · ` +
                    `O ${num(k.open).toLocaleString()} · ` +
                    `H ${num(k.high).toLocaleString()} · ` +
                    `L ${num(k.low).toLocaleString()} · ` +
                    `C <span class="${k.close >= k.open ? 'text-green-400' : 'text-red-400'}">${num(k.close).toLocaleString()}</span>`;
                el.crosshair.classList.remove('hidden');
                state.crosshairOn = true;
            });
        } catch (e) { /* optional hook — safe to skip */ }

        handleResize();
        window.addEventListener('resize', debounce(handleResize, 120));

        loadCandles();
        startTimers();

        setTimeout(() => addIndicator('MA', false, [{ calcParams: [7, 25, 99] }]), 400);
    }

    let resizeRAF = null;
    function handleResize() {
        if (resizeRAF) cancelAnimationFrame(resizeRAF);
        resizeRAF = requestAnimationFrame(() => {
            if (!state.chart || !el.chart) return;
            const w = el.chart.clientWidth;
            const h = state.isFullscreen ? window.innerHeight - 120 : 420;
            state.chart.resize(w, h);
        });
    }

    async function loadCandles() {
        try {
            setConnection(state.candleRetries ? 'reconnecting' : 'connecting');

            const controller = new AbortController();
            const timeout = setTimeout(() => controller.abort(), 8000);

            const res = await fetch(`/trading/candles/${state.currentInterval}`, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
                signal: controller.signal,
                cache: 'no-store',
            });
            clearTimeout(timeout);

            if (!res.ok) throw new Error(`HTTP ${res.status}`);

            const raw = await res.json();
            if (!Array.isArray(raw) || raw.length === 0) {
                setStatus(`⚠️ No candle data for ${state.currentInterval}`, true);
                return;
            }

            const mapped = raw.map(c => {
                const ts = toMillis(c.time ?? c.open_time);
                if (ts === null) return null;
                return {
                    timestamp: ts,
                    open:   num(c.open),
                    high:   num(c.high),
                    low:    num(c.low),
                    close:  num(c.close),
                    volume: num(c.volume, 0),
                };
            }).filter(Boolean).sort((a, b) => a.timestamp - b.timestamp);

            if (!mapped.length) throw new Error('No valid candles');

            state.chart.applyNewData(mapped);
            state.candleRetries = 0;
            setConnection('live');
            setStatus(`✅ ${mapped.length} candles · ${state.currentInterval} · ${new Date().toLocaleTimeString('en-KE')}`);

            const last = mapped[mapped.length - 1].close;
            updatePrice(last);

        } catch (e) {
            state.candleRetries++;
            const backoff = Math.min(CONFIG.backoffMax, CONFIG.backoffBase * 2 ** Math.min(state.candleRetries - 1, 5));
            setStatus(`⚠️ Retry ${state.candleRetries}/${CONFIG.maxRetries} in ${Math.round(backoff/1000)}s — ${e.message}`, true);
            setConnection(state.candleRetries >= CONFIG.maxRetries ? 'offline' : 'reconnecting');
            setTimeout(loadCandles, backoff);
        }
    }

    function updatePrice(newPrice) {
        if (!el.price || !newPrice) return;
        const prev = state.lastPrice;
        el.price.textContent = fmtKes(newPrice);

        if (prev && prev !== newPrice) {
            const delta = newPrice - prev;
            const pct = ((delta / prev) * 100).toFixed(2);
            const up = delta > 0;

            el.price.style.color = up ? '#26A69A' : '#EF5350';
            el.price.style.transform = 'scale(1.05)';
            setTimeout(() => {
                el.price.style.color = '#D4AF37';
                el.price.style.transform = 'scale(1)';
            }, 800);

            if (el.priceDelta) {
                el.priceDelta.textContent = `${up ? '▲' : '▼'} ${Math.abs(delta).toLocaleString()} (${pct}%)`;
                el.priceDelta.style.color = up ? '#26A69A' : '#EF5350';
            }
        }
        state.lastPrice = newPrice;
    }

    function startCountdown() {
        if (state.countdownTimer) clearInterval(state.countdownTimer);
        const cycleMs = CONFIG.candleRefreshMs;
        let elapsed = 0;
        const tickMs = 250;
        state.countdownTimer = setInterval(() => {
            elapsed += tickMs;
            const remaining = Math.max(0, cycleMs - elapsed);
            if (el.countdown) el.countdown.textContent = `· refresh in ${(remaining / 1000).toFixed(1)}s`;
            if (elapsed >= cycleMs) elapsed = 0;
        }, tickMs);
    }

    function startTimers() {
        stopTimers();
        if (state.paused) return;
        state.candleTimer    = setInterval(loadCandles, CONFIG.candleRefreshMs);
        state.orderBookTimer = setInterval(loadOrderBook, CONFIG.orderBookRefreshMs);
        startCountdown();
    }

    function stopTimers() {
        if (state.candleTimer)    { clearInterval(state.candleTimer);    state.candleTimer = null; }
        if (state.orderBookTimer) { clearInterval(state.orderBookTimer); state.orderBookTimer = null; }
        if (state.countdownTimer) { clearInterval(state.countdownTimer); state.countdownTimer = null; }
        if (el.countdown) el.countdown.textContent = '';
    }

    document.addEventListener('visibilitychange', () => {
        if (!CONFIG.visibilityPause) return;
        if (document.hidden) {
            state.paused = true;
            stopTimers();
            setConnection('offline');
            setStatus('Paused — tab in background', false);
        } else {
            state.paused = false;
            setConnection('connecting');
            loadCandles();
            loadOrderBook();
            startTimers();
        }
    });

    window.changeTimeframe = function (interval) {
        if (!interval) return;
        state.currentInterval = interval;
        document.querySelectorAll('.interval-btn').forEach(btn => {
            btn.classList.toggle('active', btn.dataset.interval === interval);
        });
        state.candleRetries = 0;
        loadCandles();
        if (state.candleTimer) {
            clearInterval(state.candleTimer);
            state.candleTimer = setInterval(loadCandles, CONFIG.candleRefreshMs);
        }
        if (state.countdownTimer) startCountdown();
    };

    window.toggleIndicator = function (name) {
        if (!state.chart) return;
        const btn = document.querySelector(`.indicator-btn[data-ind="${name}"]`);
        const exists = state.activeIndicators[name];

        if (exists) {
            state.chart.removeIndicator('candle_pane', exists);
            delete state.activeIndicators[name];
            btn?.classList.remove('active');
            return;
        }

        try {
            const id = state.chart.createIndicator(name, false, { id: 'candle_pane' }, defaultParams(name));
            if (id) {
                state.activeIndicators[name] = id;
                btn?.classList.add('active');
            }
        } catch (e) { console.warn('[Indicator]', name, e.message); }
    };

    function defaultParams(name) {
        const map = {
            MA:   [{ calcParams: [5, 10, 30, 60] }],
            EMA:  [{ calcParams: [6, 12, 20] }],
            BOLL: [{ calcParams: [20, 2] }],
            MACD: [{ calcParams: [12, 26, 9] }],
            RSI:  [{ calcParams: [6, 12, 24] }],
            KDJ:  [{ calcParams: [9, 3, 3] }],
            OBV:  [{ calcParams: [30] }],
        };
        return map[name] || [{}];
    }

    function addIndicator(name, toggle, params) {
        if (!state.chart) return;
        if (state.activeIndicators[name]) return;
        try {
            const id = state.chart.createIndicator(name, false, { id: 'candle_pane' }, params || defaultParams(name));
            if (id) {
                state.activeIndicators[name] = id;
                document.querySelector(`.indicator-btn[data-ind="${name}"]`)?.classList.add('active');
            }
        } catch (e) { console.warn(e); }
    }

    async function loadOrderBook() {
        if (!el.book) return;
        try {
            const res = await fetch('/trading/order-book', {
                headers: { 'Accept': 'application/json' },
                credentials: 'same-origin',
                cache: 'no-store',
            });
            if (!res.ok) throw new Error(`HTTP ${res.status}`);

            const data = await res.json();
            const asks = (data.asks || []).slice(0, 10);
            const bids = (data.bids || []).slice(0, 10);

            let html = `<div class="flex justify-between text-gold-400 text-xs border-b border-gold/20 pb-1 mb-2 font-semibold uppercase">
                <span>Price (KES)</span><span>Amount (BTC)</span></div>`;

            if (!asks.length && !bids.length) {
                html += `<p class="text-center text-ivory/40 text-xs py-6">No orders in the book</p>`;
            } else {
                asks.forEach(a => {
                    html += `<div class="flex justify-between text-red-400 py-1 border-b border-red-500/10">
                        <span>${num(a.price).toLocaleString()}</span>
                        <span>${num(a.amount).toFixed(6)}</span></div>`;
                });
                if (asks.length && bids.length) html += `<div class="h-1 my-1"></div>`;
                bids.forEach(b => {
                    html += `<div class="flex justify-between text-green-400 py-1 border-b border-green-500/10">
                        <span>${num(b.price).toLocaleString()}</span>
                        <span>${num(b.amount).toFixed(6)}</span></div>`;
                });
            }

            el.book.innerHTML = html;
            state.bookRetries = 0;
        } catch (e) {
            state.bookRetries++;
            const backoff = Math.min(CONFIG.backoffMax, CONFIG.backoffBase * 2 ** Math.min(state.bookRetries - 1, 5));
            setTimeout(loadOrderBook, backoff);
        }
    }

    window.toggleFullscreen = function () {
        if (!state.isFullscreen) {
            el.panel?.classList.add('fixed','inset-0','z-50','p-6','overflow-auto');
            el.panel?.classList.remove('card-golden');
            state.isFullscreen = true;
        } else {
            el.panel?.classList.remove('fixed','inset-0','z-50','p-6','overflow-auto');
            el.panel?.classList.add('card-golden');
            state.isFullscreen = false;
        }
        handleResize();
    };

    window.screenshotChart = function () {
        if (!state.chart) return;
        try {
            const url = state.chart.getConvertPictureUrl(true, 'png');
            const a = document.createElement('a');
            a.href = url;
            a.download = `racksephnox-chart-${Date.now()}.png`;
            a.click();
        } catch (e) { console.warn('Screenshot failed', e); }
    };

    window.resetZoom = function () {
        if (!state.chart) return;
        loadCandles();
    };

    document.addEventListener('keydown', (e) => {
        if (['INPUT','TEXTAREA','SELECT'].includes(document.activeElement?.tagName)) return;
        if (e.ctrlKey || e.metaKey || e.altKey) return;
        const map = { '1':'1m', '2':'5m', '3':'15m', '4':'1h', '5':'4h', '6':'1d' };
        if (map[e.key]) { changeTimeframe(map[e.key]); return; }
        const k = e.key.toLowerCase();
        if (k === 'f') toggleFullscreen();
        if (k === 's') screenshotChart();
        if (k === 'r') resetZoom();
    });

    function bindTabs() {
        const buyTab  = document.getElementById('buyTab');
        const sellTab = document.getElementById('sellTab');
        const buyForm = document.getElementById('buyForm');
        const sellForm= document.getElementById('sellForm');

        buyTab?.addEventListener('click', () => {
            buyForm?.classList.remove('hidden');
            sellForm?.classList.add('hidden');
            buyTab.classList.remove('bg-green-500/10','text-green-400/60');
            buyTab.classList.add('bg-green-500/25','text-green-400');
            sellTab.classList.remove('bg-red-500/25','text-red-400');
            sellTab.classList.add('bg-red-500/10','text-red-400/60');
        });
        sellTab?.addEventListener('click', () => {
            sellForm?.classList.remove('hidden');
            buyForm?.classList.add('hidden');
            sellTab.classList.remove('bg-red-500/10','text-red-400/60');
            sellTab.classList.add('bg-red-500/25','text-red-400');
            buyTab.classList.remove('bg-green-500/25','text-green-400');
            buyTab.classList.add('bg-green-500/10','text-green-400/60');
        });
    }

    function bindOrderTypeSelects() {
        document.querySelectorAll('.order-type-select').forEach(sel => {
            sel.addEventListener('change', function () {
                const form = this.closest('form');
                if (!form) return;
                const show = ['limit','stop'].includes(this.value);
                form.querySelectorAll('.limit-fields').forEach(el => el.classList.toggle('hidden', !show));
            });
        });
    }

    function debounce(fn, ms) {
        let t;
        return (...args) => { clearTimeout(t); t = setTimeout(() => fn(...args), ms); };
    }

    document.addEventListener('DOMContentLoaded', () => {
        setConnection('connecting');
        initChart();
        loadOrderBook();
        bindTabs();
        bindOrderTypeSelects();
    });

    const style = document.createElement('style');
    style.textContent = `
        .interval-btn.active, .indicator-btn.active {
            background: rgba(212, 175, 55, 0.3);
            color: #FFD700;
            border-color: #D4AF37;
            box-shadow: 0 0 8px rgba(212, 175, 55, 0.3);
        }
        #liveBtcPrice { transition: color 0.3s ease, transform 0.3s ease; display: inline-block; }
        .custom-scroll::-webkit-scrollbar { width: 6px; }
        .custom-scroll::-webkit-scrollbar-track { background: rgba(212, 175, 55, 0.05); border-radius: 3px; }
        .custom-scroll::-webkit-scrollbar-thumb { background: rgba(212, 175, 55, 0.3); border-radius: 3px; }
        #chart canvas { border-radius: 0.5rem; }
        #chartPanel.fixed { background: #0a0a0a; }
        @media (prefers-reduced-motion: reduce) { #liveBtcPrice { transition: none !important; } }
    `;
    document.head.appendChild(style);
})();
</script>
@endsection
