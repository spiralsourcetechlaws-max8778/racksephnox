@extends('layouts.app')

@section('content')
<div class="py-8">
    <div class="max-w-3xl mx-auto px-4 sm:px-6">

        <a href="{{ route('lottery.index') }}" class="text-gold-400 text-sm">
            <i class="fas fa-arrow-left"></i> Back to Lottery
        </a>

        <h1 class="text-3xl font-bold golden-title mt-4 mb-2 text-center">🛡️ Provably Fair</h1>
        <p class="text-gold-400 text-center text-sm mb-8">Verify every spin with cryptographic proof</p>

        <div class="card-golden p-6 mb-6">
            <h2 class="text-lg font-bold text-gold mb-3">How it works</h2>
            <ol class="text-sm text-ivory/70 space-y-2 list-decimal list-inside">
                <li>Server generates a secret seed per user (hash shown to you).</li>
                <li>You may set your own client seed at any time.</li>
                <li>Each spin increments a <strong>nonce</strong>.</li>
                <li>The result comes from <code>HMAC-SHA256(client_seed + nonce, server_seed)</code>.</li>
                <li>Rotate your seed at any time — the previous server seed is revealed so you can verify past spins.</li>
            </ol>
        </div>

        <div class="card-golden p-6 mb-6">
            <h2 class="text-lg font-bold text-gold mb-3">Your Active Seed</h2>
            @if($seed)
                <div class="space-y-2 text-sm">
                    <div>
                        <p class="text-xs text-gold-400">Server Seed Hash</p>
                        <p class="font-mono text-xs text-ivory break-all bg-black/40 p-2 rounded">{{ $seed->server_seed_hash }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gold-400">Client Seed</p>
                        <p class="font-mono text-xs text-ivory break-all bg-black/40 p-2 rounded">{{ $seed->client_seed }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gold-400">Nonce</p>
                        <p class="font-mono text-ivory">{{ $seed->nonce }}</p>
                    </div>
                </div>
                <div class="mt-4 flex gap-2">
                    <form method="POST" action="{{ route('lottery.fair.rotate') }}">
                        @csrf
                        <button class="btn-golden text-sm px-4 py-2">Rotate Seed</button>
                    </form>
                    <form method="POST" action="{{ route('lottery.fair.client-seed') }}" class="flex gap-2 flex-1">
                        @csrf
                        <input type="text" name="client_seed" class="input-golden flex-1 text-sm"
                               placeholder="New client seed" required>
                        <button class="btn-outline-silver text-sm px-3">Set</button>
                    </form>
                </div>
            @else
                <p class="text-ivory/50">No active seed. Play a spin to generate one.</p>
            @endif
        </div>

        <div class="card-golden p-6">
            <h2 class="text-lg font-bold text-gold mb-3">Verify a Spin</h2>
            <form method="POST" action="{{ route('lottery.verify') }}">
                @csrf
                <div class="space-y-2">
                    <input type="text" name="server_seed" class="input-golden w-full font-mono text-xs"
                           placeholder="Server seed (revealed)" required>
                    <input type="text" name="client_seed" class="input-golden w-full font-mono text-xs"
                           placeholder="Client seed" required>
                    <input type="number" name="nonce" class="input-golden w-full font-mono text-xs"
                           placeholder="Nonce" required>
                    <input type="text" name="hash" class="input-golden w-full font-mono text-xs"
                           placeholder="Hash from spin record" required>
                </div>
                <button class="btn-golden w-full mt-3">Verify</button>
            </form>

            @if(session('verified') !== null)
                <div class="mt-4 p-3 rounded-xl text-center font-bold
                            {{ session('verified') ? 'bg-green-500/20 text-green-300' : 'bg-red-500/20 text-red-300' }}">
                    {{ session('verified') ? '✅ Spin verified fair' : '❌ Spin verification failed' }}
                </div>
            @endif
        </div>

    </div>
</div>
@endsection
