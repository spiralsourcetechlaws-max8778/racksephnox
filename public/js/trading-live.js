/**
 * Racksephnox Trading Live Bridge
 * - Polls BTC price every 10s
 * - Polls candles every 5s for the active interval
 * - Listens to Reverb channel for real-time updates
 * - Dispatches window events that any chart library can hook into
 */
(function () {
    'use strict';

    const state = {
        interval: window.TRADING_INTERVAL || '1h',
        lastCandleTime: 0,
        lastPrice: parseFloat(window.TRADING_INITIAL_PRICE || '0'),
        paused: false,
    };

    const els = {
        price:      () => document.getElementById('liveBtcPrice'),
        delta:      () => document.getElementById('priceDelta'),
        status:     () => document.getElementById('chartStatus'),
        connBadge:  () => document.getElementById('connBadge'),
        connDot:    () => document.getElementById('connDot'),
        connText:   () => document.getElementById('connText'),
        countdown:  () => document.getElementById('countdown'),
    };

    function setConn(status) {
        const dot = els.connDot();
        const text = els.connText();
        const badge = els.connBadge();
        if (!dot || !text || !badge) return;

        const map = {
            connected: { dot: 'bg-green-400', text: 'LIVE',     cls: 'bg-green-500/20 text-green-400' },
            polling:   { dot: 'bg-yellow-400', text: 'POLLING', cls: 'bg-yellow-500/20 text-yellow-400' },
            offline:   { dot: 'bg-red-400',    text: 'OFFLINE', cls: 'bg-red-500/20 text-red-400' },
            connecting:{ dot: 'bg-gray-400',   text: 'CONNECTING', cls: 'bg-gray-500/30 text-gray-300' },
        };
        const s = map[status] || map.connecting;
        dot.className = 'inline-block w-1.5 h-1.5 rounded-full mr-1 ' + s.dot;
        text.textContent = s.text;
        badge.className = 'text-[10px] px-2 py-1 rounded-full font-normal tracking-wider ' + s.cls;
    }

    function setPrice(price, { animate = true } = {}) {
        const p = parseFloat(price);
        if (!isFinite(p) || p <= 0) return;

        const el = els.price();
        if (el) {
            el.textContent = 'KES ' + p.toLocaleString(undefined, { maximumFractionDigits: 2 });
        }

        if (animate && state.lastPrice > 0 && p !== state.lastPrice) {
            const d = p - state.lastPrice;
            const pct = (d / state.lastPrice) * 100;
            const delta = els.delta();
            if (delta) {
                delta.textContent = (d >= 0 ? '▲ +' : '▼ ') + pct.toFixed(2) + '%';
                delta.className = 'text-xs ml-2 ' + (d >= 0 ? 'text-green-400' : 'text-red-400');
                setTimeout(() => { if (delta.textContent) delta.textContent = ''; }, 4000);
            }
        }
        state.lastPrice = p;
    }

    function setStatus(msg) {
        const s = els.status();
        if (s) s.textContent = msg;
    }

    async function pollPrice() {
        try {
            const res = await fetch('/trading/price', { headers: { Accept: 'application/json' } });
            if (!res.ok) throw new Error('HTTP ' + res.status);
            const data = await res.json();
            const price = data.price_kes || data.price || 0;
            if (price) {
                setPrice(price);
                setConn('polling');
            }
        } catch (e) {
            console.warn('Price poll failed', e.message);
        }
    }

    async function pollCandles(interval) {
        if (state.paused) return;
        try {
            const res = await fetch('/trading/candles/' + interval, {
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });
            if (!res.ok) throw new Error('HTTP ' + res.status);
            const candles = await res.json();
            if (!Array.isArray(candles) || candles.length === 0) {
                setStatus('No candle data for ' + interval);
                return;
            }

            setStatus(candles.length + ' candles · ' + interval);

            const last = candles[candles.length - 1];
            if (last) setPrice(last.close);

            // Dispatch for any chart library to consume
            window.dispatchEvent(new CustomEvent('trading:candles', {
                detail: { interval, candles },
            }));
        } catch (e) {
            setStatus('Chart load failed: ' + e.message);
            console.warn('Candle poll failed', e.message);
        }
    }

    function startPolling() {
        setConn('polling');

        // Initial load
        pollPrice();
        pollCandles(state.interval);

        // Price every 10s
        setInterval(pollPrice, 10_000);

        // Candles every 5s
        setInterval(() => pollCandles(state.interval), 5_000);

        // Countdown display
        let count = 5;
        setInterval(() => {
            count--;
            if (count <= 0) count = 5;
            const c = els.countdown();
            if (c) c.textContent = '⟳ ' + count + 's';
        }, 1000);
    }

    function subscribeReverb() {
        if (!window.Echo) {
            console.info('Echo not available — using HTTP polling only');
            return;
        }

        try {
            window.Echo.channel('trading')
                .listen('.BtcPriceUpdated', (e) => {
                    if (e && e.price_kes) {
                        setPrice(e.price_kes);
                        setConn('connected');
                    }
                });

            window.Echo.channel('trading')
                .listen('.CandleUpdated', (e) => {
                    if (e && e.candle) {
                        window.dispatchEvent(new CustomEvent('trading:candle-updated', { detail: e }));
                    }
                });

            setConn('connected');
        } catch (e) {
            console.warn('Echo subscribe failed', e.message);
        }
    }

    // Public API for external chart code
    window.TradingLive = {
        setInterval(iv) {
            state.interval = iv;
            pollCandles(iv);
        },
        pause() { state.paused = true; },
        resume() { state.paused = false; },
        refresh() {
            pollPrice();
            pollCandles(state.interval);
        },
        state,
    };

    // Kick off
    document.addEventListener('DOMContentLoaded', () => {
        startPolling();
        subscribeReverb();
    });
})();
