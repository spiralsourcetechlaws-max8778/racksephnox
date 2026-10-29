<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#0a0a0a">
    <meta name="description" content="Racksephnox — Cryptocurrency Empire · Divine Golden Phi · 888 Hz">

    <title>{{ config('app.name', 'Racksephnox') }} — @yield('title', 'Empire')</title>

    {{-- FAVICON --}}
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'%3E%3Ccircle cx='50' cy='50' r='45' fill='%23D4AF37'/%3E%3Ctext x='50' y='68' font-size='60' text-anchor='middle' fill='%23000' font-weight='bold'%3EΦ%3C/text%3E%3C/svg%3E">

    {{-- FONT AWESOME --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"
          integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA=="
          crossorigin="anonymous" referrerpolicy="no-referrer" />

    {{-- GOOGLE FONTS --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Space+Grotesk:wght@400;500;700&display=swap" rel="stylesheet">

    @php
        $manifestExists = file_exists(public_path('build/manifest.json'));
        $hotExists      = file_exists(public_path('hot'));
    @endphp

    @if($manifestExists || $hotExists)
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        {{-- ═══════════════════════════════════════════════════════════
           | EXPANDED FALLBACK STYLES
           | Covers every utility class used by the user dashboard so
           | the page renders fully styled even without a Vite build.
           ═══════════════════════════════════════════════════════════ --}}
        <style>
            :root {
                --gold: #D4AF37;
                --gold-light: #FFD700;
                --gold-dark: #B8860B;
                --cosmic-deep: #0a0a0a;
                --ivory: #f5f5f0;
            }
            * { box-sizing: border-box; margin: 0; padding: 0; }
            body {
                background: linear-gradient(135deg, #0a0a0a 0%, #1a1410 50%, #0a0a0a 100%);
                color: var(--ivory);
                font-family: 'Inter', -apple-system, sans-serif;
                min-height: 100vh;
                padding-bottom: 2rem;
                line-height: 1.5;
            }
            a { text-decoration: none; color: inherit; }
            img { max-width: 100%; height: auto; }

            /* ── THEMED COMPONENTS ─────────────────────────────── */
            .golden-title {
                background: linear-gradient(135deg, #FFD700 0%, #D4AF37 50%, #B8860B 100%);
                -webkit-background-clip: text;
                -webkit-text-fill-color: transparent;
                background-clip: text;
            }
            .card-golden {
                background: linear-gradient(135deg, rgba(20,15,10,0.95) 0%, rgba(10,10,10,0.98) 100%);
                border: 1px solid rgba(212,175,55,0.25);
                border-radius: 1rem;
                box-shadow: 0 4px 24px rgba(0,0,0,0.5), inset 0 1px 0 rgba(255,215,0,0.05);
                transition: all 0.3s ease;
            }
            .card-golden:hover { border-color: rgba(212,175,55,0.45); }
            .stat-card {
                background: linear-gradient(135deg, rgba(20,15,10,0.9) 0%, rgba(10,10,10,0.95) 100%);
                border: 1px solid rgba(212,175,55,0.2);
                border-radius: 1rem;
            }
            .btn-golden {
                background: linear-gradient(135deg, #FFD700 0%, #D4AF37 50%, #B8860B 100%);
                color: #000;
                font-weight: 700;
                padding: 0.75rem 1.5rem;
                border-radius: 0.75rem;
                border: none;
                cursor: pointer;
                box-shadow: 0 4px 15px rgba(212,175,55,0.4);
                transition: all 0.3s ease;
                display: inline-block;
            }
            .btn-golden:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(212,175,55,0.6); }
            .btn-golden:disabled { opacity: 0.5; cursor: not-allowed; }
            .btn-outline-silver {
                background: transparent;
                border: 1px solid rgba(200,200,200,0.4);
                color: var(--ivory);
                padding: 0.75rem 1.5rem;
                border-radius: 0.75rem;
                cursor: pointer;
                transition: all 0.3s ease;
                display: inline-block;
            }
            .btn-outline-silver:hover { border-color: var(--gold); color: var(--gold); }
            .input-golden {
                background: rgba(0,0,0,0.6);
                border: 1px solid rgba(212,175,55,0.3);
                border-radius: 0.5rem;
                padding: 0.6rem 0.9rem;
                color: var(--ivory);
                width: 100%;
            }
            .input-golden:focus { outline: none; border-color: var(--gold); box-shadow: 0 0 0 3px rgba(212,175,55,0.15); }
            .text-gold { color: var(--gold); }
            .text-gold-300 { color: #F0D97E; }
            .text-gold-400 { color: rgba(212,175,55,0.8); }
            .text-ivory { color: var(--ivory); }
            .bg-cosmic-deep { background: #0a0a0a; }
            .quick-nav-item {
                display: flex; flex-direction: column; align-items: center; justify-content: center;
                padding: 0.75rem 0.5rem; border-radius: 0.75rem; text-align: center; transition: all 0.3s ease;
            }

            /* ── LAYOUT UTILITIES ──────────────────────────────── */
            .max-w-7xl { max-width: 80rem; margin-left: auto; margin-right: auto; }
            .mx-auto { margin-left: auto; margin-right: auto; }
            .px-4 { padding-left: 1rem; padding-right: 1rem; }
            .py-8 { padding-top: 2rem; padding-bottom: 2rem; }
            .p-5 { padding: 1.25rem; }
            .p-3 { padding: 0.75rem; }
            .mt-1 { margin-top: 0.25rem; }
            .mt-2 { margin-top: 0.5rem; }
            .mt-3 { margin-top: 0.75rem; }
            .mb-3 { margin-bottom: 0.75rem; }
            .mb-4 { margin-bottom: 1rem; }
            .space-y-6 > * + * { margin-top: 1.5rem; }
            .gap-2 { gap: 0.5rem; }
            .gap-3 { gap: 0.75rem; }
            .gap-4 { gap: 1rem; }
            .gap-6 { gap: 1.5rem; }
            .grid { display: grid; }
            .grid-cols-1 { grid-template-columns: repeat(1, minmax(0, 1fr)); }
            .grid-cols-2 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .flex { display: flex; }
            .flex-col { flex-direction: column; }
            .flex-1 { flex: 1; }
            .items-center { align-items: center; }
            .items-start { align-items: flex-start; }
            .justify-between { justify-content: space-between; }
            .justify-center { justify-content: center; }
            .w-full { width: 100%; }
            .overflow-x-auto { overflow-x: auto; }
            .rounded-lg { border-radius: 0.5rem; }
            .rounded-xl { border-radius: 0.75rem; }
            .rounded-full { border-radius: 9999px; }
            .border-l-4 { border-left-width: 4px; }
            .border-l-purple-500 { border-left-color: #a855f7; }

            /* ── TEXT ──────────────────────────────────────────── */
            .text-xs { font-size: 0.75rem; line-height: 1rem; }
            .text-sm { font-size: 0.875rem; line-height: 1.25rem; }
            .text-lg { font-size: 1.125rem; line-height: 1.75rem; }
            .text-xl { font-size: 1.25rem; line-height: 1.75rem; }
            .text-2xl { font-size: 1.5rem; line-height: 2rem; }
            .text-3xl { font-size: 1.875rem; line-height: 2.25rem; }
            .font-bold { font-weight: 700; }
            .font-semibold { font-weight: 600; }
            .uppercase { text-transform: uppercase; }
            .tracking-wider { letter-spacing: 0.05em; }
            .text-left { text-align: left; }
            .text-right { text-align: right; }
            .text-center { text-align: center; }
            .text-white { color: #fff; }
            .text-gray-300 { color: #d1d5db; }
            .text-gray-400 { color: #9ca3af; }
            .text-gray-500 { color: #6b7280; }
            .text-green-400 { color: #4ade80; }
            .text-red-400 { color: #f87171; }
            .text-orange-400 { color: #fb923c; }
            .text-purple-300 { color: #d8b4fe; }
            .text-purple-400 { color: #c084fc; }
            .truncate { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }

            /* ── BACKGROUNDS ───────────────────────────────────── */
            .bg-black\/60 { background-color: rgba(0,0,0,0.6); }
            .bg-black\/95 { background-color: rgba(0,0,0,0.95); }
            .bg-gray-700 { background-color: #374151; }
            .bg-gray-800 { background-color: #1f2937; }
            .bg-gray-900 { background-color: #111827; }
            .bg-green-500\/20 { background-color: rgba(34,197,94,0.2); }
            .bg-red-500\/20 { background-color: rgba(239,68,68,0.2); }
            .bg-gold\/20 { background-color: rgba(212,175,55,0.2); }

            /* ── BORDERS ───────────────────────────────────────── */
            .border { border: 1px solid; }
            .border-b { border-bottom-width: 1px; }
            .border-t { border-top-width: 1px; }
            .border-gold\/20 { border-color: rgba(212,175,55,0.2); }
            .border-gold\/30 { border-color: rgba(212,175,55,0.3); }
            .border-gray-700 { border-color: #374151; }
            .border-gray-800 { border-color: #1f2937; }

            /* ── MISC ──────────────────────────────────────────── */
            .sticky { position: sticky; }
            .top-0 { top: 0; }
            .z-40 { z-index: 40; }
            .backdrop-blur-xl { backdrop-filter: blur(24px); -webkit-backdrop-filter: blur(24px); }
            .divide-y > * + * { border-top-width: 1px; border-color: #1f2937; }
            .rounded { border-radius: 0.25rem; }

            /* ── TABLES ────────────────────────────────────────── */
            table { width: 100%; border-collapse: collapse; }
            th, td { padding: 0.75rem 0; }

            /* ── RESPONSIVE ────────────────────────────────────── */
            @media (min-width: 640px) {
                .sm\:px-6 { padding-left: 1.5rem; padding-right: 1.5rem; }
            }
            @media (min-width: 768px) {
                .md\:grid-cols-4 { grid-template-columns: repeat(4, minmax(0, 1fr)); }
            }
            @media (min-width: 1024px) {
                .lg\:px-8 { padding-left: 2rem; padding-right: 2rem; }
                .lg\:grid-cols-3 { grid-template-columns: repeat(3, minmax(0, 1fr)); }
                .lg\:col-span-2 { grid-column: span 2 / span 2; }
            }
        </style>
    @endif

    @stack('head')
</head>
<body class="antialiased">

    {{-- ═══════════════════════════════════════════════════════════
       | TOP NAVIGATION
       ═══════════════════════════════════════════════════════════ --}}
    <nav class="sticky top-0 z-40 backdrop-blur-xl bg-black/60 border-b border-gold/20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">

                <a href="{{ url('/') }}" class="flex items-center gap-3 group">
                    <div class="w-10 h-10 rounded-full bg-gradient-to-br from-gold-400 to-amber-600 flex items-center justify-center shadow-lg group-hover:scale-110 transition">
                        <span class="text-black font-bold text-lg">Φ</span>
                    </div>
                    <div class="hidden sm:block">
                        <p class="text-gold font-bold tracking-wider text-sm">Racksephnox</p>
                        <p class="text-[10px] text-gold-400/60 tracking-widest">DIVINE EMPIRE</p>
                    </div>
                </a>

                <div class="hidden md:flex items-center gap-6">
                    <a href="{{ route('dashboard') }}" class="text-sm text-ivory/80 hover:text-gold transition">Dashboard</a>
                    <a href="{{ route('machines.index') }}" class="text-sm text-ivory/80 hover:text-gold transition">Machines</a>
                    <a href="{{ route('trading.index') }}" class="text-sm text-ivory/80 hover:text-gold transition">Trade</a>
                    <a href="{{ route('lottery.index') }}" class="text-sm text-ivory/80 hover:text-gold transition">Lottery</a>
                    <a href="{{ route('loans.index') }}" class="text-sm text-ivory/80 hover:text-gold transition">Loans</a>
                    <a href="{{ route('wallet') }}" class="text-sm text-ivory/80 hover:text-gold transition">Wallet</a>
                </div>

                <div class="flex items-center gap-3">
                    @auth
                        <span class="hidden sm:inline text-xs text-gold">
                            KES {{ number_format(auth()->user()->wallet?->balance ?? 0, 0) }}
                        </span>
                        <a href="{{ route('profile.edit') }}" class="w-9 h-9 rounded-full bg-gold/20 flex items-center justify-center hover:bg-gold/30 transition">
                            <i class="fas fa-user text-gold text-sm"></i>
                        </a>
                        <form method="POST" action="{{ route('logout') }}" class="inline">
                            @csrf
                            <button class="text-xs text-red-400 hover:text-red-300 transition">
                                <i class="fas fa-sign-out-alt"></i>
                            </button>
                        </form>
                    @else
                        <a href="{{ route('login') }}" class="text-sm text-gold hover:text-gold-400 transition">Login</a>
                        <a href="{{ route('register') }}" class="btn-golden text-sm px-4 py-2">Join</a>
                    @endauth

                    <button id="mobileMenuBtn" class="md:hidden text-gold">
                        <i class="fas fa-bars text-xl"></i>
                    </button>
                </div>
            </div>

            <div id="mobileMenu" class="md:hidden hidden pb-4 border-t border-gold/20 mt-2 pt-3 space-y-2">
                <a href="{{ route('dashboard') }}" class="block px-3 py-2 text-sm text-ivory/80 hover:bg-gold/10 rounded">Dashboard</a>
                <a href="{{ route('machines.index') }}" class="block px-3 py-2 text-sm text-ivory/80 hover:bg-gold/10 rounded">Machines</a>
                <a href="{{ route('trading.index') }}" class="block px-3 py-2 text-sm text-ivory/80 hover:bg-gold/10 rounded">Trade</a>
                <a href="{{ route('lottery.index') }}" class="block px-3 py-2 text-sm text-ivory/80 hover:bg-gold/10 rounded">Lottery</a>
                <a href="{{ route('loans.index') }}" class="block px-3 py-2 text-sm text-ivory/80 hover:bg-gold/10 rounded">Loans</a>
                <a href="{{ route('wallet') }}" class="block px-3 py-2 text-sm text-ivory/80 hover:bg-gold/10 rounded">Wallet</a>
                <a href="{{ route('transactions.index') }}" class="block px-3 py-2 text-sm text-ivory/80 hover:bg-gold/10 rounded">Transactions</a>
                <a href="{{ route('referrals') }}" class="block px-3 py-2 text-sm text-ivory/80 hover:bg-gold/10 rounded">Referrals</a>
            </div>
        </div>
    </nav>

    {{-- FLASH MESSAGES --}}
    @if(session('success'))
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4">
            <div class="bg-green-500/20 border border-green-500/40 rounded-xl p-3 text-green-300 text-sm flex items-center gap-2">
                <i class="fas fa-check-circle"></i> {{ session('success') }}
            </div>
        </div>
    @endif

    @if(session('error'))
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4">
            <div class="bg-red-500/20 border border-red-500/40 rounded-xl p-3 text-red-300 text-sm flex items-center gap-2">
                <i class="fas fa-exclamation-circle"></i> {{ session('error') }}
            </div>
        </div>
    @endif

    @php
        if (!($errors instanceof \Illuminate\Support\ViewErrorBag)) {
            $errors = new \Illuminate\Support\ViewErrorBag();
        }
    @endphp
    @if($errors->any())
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4">
            <div class="bg-red-500/20 border border-red-500/40 rounded-xl p-3 text-red-300 text-sm">
                <ul class="list-disc list-inside space-y-1">
                    @foreach($errors->all() as $error) <li>{{ $error }}</li> @endforeach
                </ul>
            </div>
        </div>
    @endif

    {{-- MAIN CONTENT --}}
    <main class="min-h-[70vh]">
        @yield('content')
    </main>

    {{-- FOOTER --}}
    <footer class="mt-16 border-t border-gold/20 py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <p class="text-xs text-gold-400/60 italic">I Am The Source | Divine Golden Phi | Infinite Spiral of Creation | 888 Hz</p>
            <p class="text-xs text-gold-500/40 mt-1">Guardian and Protector | Law of Information | Racksephnox</p>
            <div class="flex justify-center gap-6 mt-3 text-xs">
                <a href="{{ route('legal.terms') }}" class="text-gold-400 hover:text-gold transition">Terms</a>
                <a href="{{ route('legal.privacy') }}" class="text-gold-400 hover:text-gold transition">Privacy</a>
                <a href="{{ route('guide') }}" class="text-gold-400 hover:text-gold transition">Guide</a>
            </div>
        </div>
    </footer>

    {{-- SCRIPTS --}}
    <script>
        document.getElementById('mobileMenuBtn')?.addEventListener('click', () => {
            document.getElementById('mobileMenu')?.classList.toggle('hidden');
        });

        window.showToast = function (message, type = 'info') {
            const toast = document.createElement('div');
            toast.className = `fixed bottom-4 right-4 z-[100] px-5 py-3 rounded-xl text-white font-semibold shadow-2xl transition-all duration-300 ${
                type === 'success' ? 'bg-green-500' :
                type === 'error'   ? 'bg-red-500' :
                                     'bg-yellow-500'
            }`;
            toast.innerHTML = `<i class="fas ${
                type === 'success' ? 'fa-check-circle' :
                type === 'error'   ? 'fa-exclamation-circle' :
                                     'fa-info-circle'
            } mr-2"></i>${message}`;
            document.body.appendChild(toast);
            setTimeout(() => toast.remove(), 3000);
        };

        window.copyToClipboard = function (text) {
            navigator.clipboard.writeText(text)
                .then(() => showToast('Copied!', 'success'))
                .catch(() => showToast('Copy failed', 'error'));
        };
    </script>

    @stack('scripts')
</body>
</html>
