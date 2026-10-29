@extends('admin.layouts.app')

@section('page-title', 'Settings')
@section('content')

<div class="space-y-6">

    {{-- ═══ SYSTEM STATS ═══ --}}
    <div class="bg-gray-900 border border-gray-800 rounded-xl p-5">
        <h2 class="text-sm font-semibold text-gold mb-3 pb-2 border-b border-gray-800">System Information</h2>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 text-xs">
            <div><p class="text-gray-400">PHP</p><p class="text-white font-mono">{{ $stats['php_version'] }}</p></div>
            <div><p class="text-gray-400">Laravel</p><p class="text-white font-mono">{{ $stats['laravel_version'] }}</p></div>
            <div><p class="text-gray-400">Environment</p><p class="text-white font-mono">{{ $stats['app_env'] }}</p></div>
            <div><p class="text-gray-400">Debug</p><p class="text-{{ $stats['debug_mode'] === 'ON' ? 'yellow' : 'green' }}-400 font-mono">{{ $stats['debug_mode'] }}</p></div>
            <div><p class="text-gray-400">Cache</p><p class="text-white font-mono">{{ $stats['cache_driver'] }}</p></div>
            <div><p class="text-gray-400">Queue</p><p class="text-white font-mono">{{ $stats['queue_driver'] }}</p></div>
            <div><p class="text-gray-400">Database</p><p class="text-white font-mono">{{ $stats['db_driver'] }}</p></div>
        </div>
    </div>

    {{-- ═══ GENERAL SETTINGS ═══ --}}
    <div class="bg-gray-900 border border-gray-800 rounded-xl p-6">
        <h2 class="text-lg font-semibold text-gold mb-4 pb-2 border-b border-gray-800">General Configuration</h2>

        <form method="POST" action="{{ route('admin.settings.update') }}" class="space-y-4">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm text-gray-400 mb-1">Site Name</label>
                    <input type="text" name="site_name" value="{{ $settings['site_name'] ?? 'Racksephnox' }}" class="w-full bg-gray-800 border border-gray-700 text-white rounded px-3 py-2" required>
                </div>
                <div>
                    <label class="block text-sm text-gray-400 mb-1">Support Email</label>
                    <input type="email" name="support_email" value="{{ $settings['support_email'] ?? '' }}" class="w-full bg-gray-800 border border-gray-700 text-white rounded px-3 py-2" required>
                </div>
            </div>

            <h3 class="text-sm font-semibold text-gold mt-6 mb-3 pb-2 border-b border-gray-800">Financial Limits & Fees</h3>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm text-gray-400 mb-1">Deposit Fee (%)</label>
                    <input type="number" step="0.01" name="deposit_fee_percent" value="{{ $settings['deposit_fee_percent'] ?? '0' }}" class="w-full bg-gray-800 border border-gray-700 text-white rounded px-3 py-2" required>
                </div>
                <div>
                    <label class="block text-sm text-gray-400 mb-1">Withdrawal Fee (%)</label>
                    <input type="number" step="0.01" name="withdrawal_fee_percent" value="{{ $settings['withdrawal_fee_percent'] ?? '0' }}" class="w-full bg-gray-800 border border-gray-700 text-white rounded px-3 py-2" required>
                </div>
                <div>
                    <label class="block text-sm text-gray-400 mb-1">Min Withdrawal (KES)</label>
                    <input type="number" step="1" name="min_withdrawal" value="{{ $settings['min_withdrawal'] ?? '100' }}" class="w-full bg-gray-800 border border-gray-700 text-white rounded px-3 py-2" required>
                </div>
            </div>

            <div class="pt-4 border-t border-gray-800">
                <button type="submit" class="bg-gold hover:bg-yellow-500 text-black font-bold px-6 py-2 rounded">Save Settings</button>
            </div>
        </form>
    </div>

    {{-- ═══ SYSTEM ACTIONS ═══ --}}
    <div class="bg-gray-900 border border-gray-800 rounded-xl p-6">
        <h2 class="text-lg font-semibold text-gold mb-4 pb-2 border-b border-gray-800">System Actions</h2>

        <div class="space-y-4">
            <div class="flex justify-between items-center p-3 bg-gray-800/50 rounded-lg">
                <div>
                    <p class="text-sm text-white font-medium">Clear Config, Route & View Caches</p>
                    <p class="text-xs text-gray-400">Application cache preserved (dashboard stays warm)</p>
                </div>
                <form method="POST" action="{{ route('admin.settings.cache-clear') }}" onsubmit="return confirm('Clear config, route, and view caches?');">
                    @csrf
                    <button type="submit" class="bg-gray-700 hover:bg-gray-600 text-white font-bold px-4 py-2 rounded text-sm">Clear</button>
                </form>
            </div>

            <div class="flex justify-between items-center p-3 bg-gray-800/50 rounded-lg">
                <div>
                    <p class="text-sm text-white font-medium">Maintenance Mode</p>
                    <p class="text-xs {{ ($settings['maintenance_mode'] ?? '0') === '1' ? 'text-red-400' : 'text-green-400' }}">
                        {{ ($settings['maintenance_mode'] ?? '0') === '1' ? 'SITE IS DOWN — Bypass: /racksephnox-admin' : 'Site is live' }}
                    </p>
                </div>
                <form method="POST" action="{{ route('admin.settings.maintenance') }}"
                      onsubmit="return confirm({{ ($settings['maintenance_mode'] ?? '0') === '1' ? 'true' : 'false' }} ? 'Disable maintenance mode?' : '⚠️ ENABLE maintenance mode? All users will get 503. Bypass URL: /racksephnox-admin — SAVE IT. No shell on Render free tier.');">
                    @csrf
                    @if(($settings['maintenance_mode'] ?? '0') === '1')
                        <button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-bold px-4 py-2 rounded text-sm">Disable</button>
                    @else
                        <button type="submit" class="bg-red-600 hover:bg-red-700 text-white font-bold px-4 py-2 rounded text-sm">Enable</button>
                    @endif
                </form>
            </div>
        </div>
    </div>

</div>
@endsection
