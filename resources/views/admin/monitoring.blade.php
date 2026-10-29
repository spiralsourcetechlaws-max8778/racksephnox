@extends('admin.layouts.app')

@section('page-title', 'System Monitoring')
@section('content')

<div class="space-y-6">

    {{-- Overall status banner --}}
    <div class="rounded-xl p-5 border
        @if($overall === 'ok') bg-green-500/10 border-green-500/40
        @elseif($overall === 'warning') bg-yellow-500/10 border-yellow-500/40
        @else bg-red-500/10 border-red-500/40 @endif">
        <div class="flex items-center gap-3">
            <i class="fas
                @if($overall === 'ok') fa-check-circle text-green-400
                @elseif($overall === 'warning') fa-exclamation-triangle text-yellow-400
                @else fa-times-circle text-red-400 @endif text-2xl"></i>
            <div>
                <p class="text-lg font-bold
                    @if($overall === 'ok') text-green-300
                    @elseif($overall === 'warning') text-yellow-300
                    @else text-red-300 @endif">
                    @if($overall === 'ok') All Systems Operational
                    @elseif($overall === 'warning') Some Systems Warning
                    @else Critical Systems Down @endif
                </p>
                <p class="text-sm text-gray-400">Last checked {{ now()->diffForHumans() }}</p>
            </div>
        </div>
    </div>

    {{-- Checks grid --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">

        {{-- Database --}}
        <div class="bg-gray-900 border border-gray-800 rounded-xl p-5">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-sm font-semibold text-gold">Database</h3>
                <i class="fas fa-database text-2xl
                    @if(($checks['database']['status'] ?? '') === 'ok') text-green-400
                    @elseif(($checks['database']['status'] ?? '') === 'warning') text-yellow-400
                    @else text-red-400 @endif"></i>
            </div>
            @if(($checks['database']['status'] ?? '') === 'error')
                <p class="text-red-400 text-sm">{{ $checks['database']['message'] ?? 'Error' }}</p>
            @else
                <p class="text-sm text-gray-300">Driver: <span class="text-white font-mono">{{ $checks['database']['driver'] ?? '—' }}</span></p>
                <p class="text-sm text-gray-300">Latency: <span class="text-white font-mono">{{ $checks['database']['latency_ms'] ?? 0 }}ms</span></p>
                <p class="text-sm text-gray-300">Users: <span class="text-white">{{ $checks['database']['users_count'] ?? 0 }}</span></p>
                <p class="text-sm text-gray-300">Migrations: <span class="text-white">{{ $checks['database']['migrations'] ?? 0 }}</span></p>
            @endif
        </div>

        {{-- Cache --}}
        <div class="bg-gray-900 border border-gray-800 rounded-xl p-5">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-sm font-semibold text-gold">Cache</h3>
                <i class="fas fa-memory text-2xl
                    @if(($checks['cache']['status'] ?? '') === 'ok') text-green-400
                    @else text-red-400 @endif"></i>
            </div>
            <p class="text-sm text-gray-300">Driver: <span class="text-white font-mono">{{ $checks['cache']['driver'] ?? '—' }}</span></p>
            <p class="text-sm text-gray-300">Latency: <span class="text-white font-mono">{{ $checks['cache']['latency_ms'] ?? 0 }}ms</span></p>
        </div>

        {{-- Queue --}}
        <div class="bg-gray-900 border border-gray-800 rounded-xl p-5">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-sm font-semibold text-gold">Queue</h3>
                <i class="fas fa-tasks text-2xl
                    @if(($checks['queue']['status'] ?? '') === 'ok') text-green-400
                    @else text-yellow-400 @endif"></i>
            </div>
            <p class="text-sm text-gray-300">Driver: <span class="text-white font-mono">{{ $checks['queue']['driver'] ?? '—' }}</span></p>
            <p class="text-sm text-gray-300">Pending: <span class="text-white">{{ $checks['queue']['pending'] ?? 0 }}</span></p>
            <p class="text-sm text-gray-300">Failed: <span class="{{ ($checks['queue']['failed'] ?? 0) > 0 ? 'text-red-400' : 'text-white' }}">{{ $checks['queue']['failed'] ?? 0 }}</span></p>
        </div>

        {{-- Storage --}}
        <div class="bg-gray-900 border border-gray-800 rounded-xl p-5">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-sm font-semibold text-gold">Storage</h3>
                <i class="fas fa-hdd text-2xl
                    @if(($checks['storage']['status'] ?? '') === 'ok') text-green-400
                    @else text-red-400 @endif"></i>
            </div>
            <p class="text-sm text-gray-300">Writable: <span class="{{ ($checks['storage']['writable'] ?? false) ? 'text-green-400' : 'text-red-400' }}">{{ ($checks['storage']['writable'] ?? false) ? 'Yes' : 'No' }}</span></p>
            <p class="text-sm text-gray-300">Free: <span class="text-white font-mono">{{ $checks['storage']['free_gb'] ?? '—' }}GB</span></p>
        </div>

        {{-- Config --}}
        <div class="bg-gray-900 border border-gray-800 rounded-xl p-5">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-sm font-semibold text-gold">Config</h3>
                <i class="fas fa-cogs text-2xl
                    @if(($checks['config']['status'] ?? '') === 'ok') text-green-400
                    @else text-yellow-400 @endif"></i>
            </div>
            <p class="text-sm text-gray-300">Env: <span class="text-white font-mono">{{ $checks['config']['app_env'] ?? '—' }}</span></p>
            <p class="text-sm text-gray-300">Debug: <span class="{{ ($checks['config']['debug'] ?? false) ? 'text-yellow-400' : 'text-green-400' }}">{{ ($checks['config']['debug'] ?? false) ? 'ON' : 'OFF' }}</span></p>
            <p class="text-sm text-gray-300">Cached: <span class="text-white">{{ ($checks['config']['config_cached'] ?? false) ? 'Yes' : 'No' }}</span></p>
            <p class="text-sm text-gray-300">Routes: <span class="text-white">{{ ($checks['config']['routes_cached'] ?? false) ? 'Yes' : 'No' }}</span></p>
        </div>

        {{-- Scheduler --}}
        <div class="bg-gray-900 border border-gray-800 rounded-xl p-5">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-sm font-semibold text-gold">Scheduler</h3>
                <i class="fas fa-clock text-2xl
                    @if(($checks['scheduler']['status'] ?? '') === 'ok') text-green-400
                    @else text-yellow-400 @endif"></i>
            </div>
            <p class="text-sm text-gray-300">Status: <span class="{{ ($checks['scheduler']['healthy'] ?? false) ? 'text-green-400' : 'text-yellow-400' }}">{{ ($checks['scheduler']['healthy'] ?? false) ? 'Running' : 'Idle' }}</span></p>
            <p class="text-sm text-gray-300">Last run: <span class="text-white font-mono text-xs">{{ $checks['scheduler']['last_run'] ?? 'Never' }}</span></p>
        </div>

    </div>

    {{-- Pending migrations --}}
    @if($pendingMigrations > 0)
        <div class="bg-yellow-500/10 border border-yellow-500/40 rounded-xl p-5">
            <p class="text-yellow-300 font-semibold">
                <i class="fas fa-exclamation-triangle mr-2"></i>
                {{ $pendingMigrations }} pending migration(s) — run `php artisan migrate` on deploy
            </p>
        </div>
    @endif

    {{-- Recent errors --}}
    <div class="bg-gray-900 border border-gray-800 rounded-xl p-5">
        <h3 class="text-sm font-semibold text-gold mb-3 pb-2 border-b border-gray-800">Recent Errors (from laravel.log)</h3>
        @if(empty($errors))
            <p class="text-sm text-gray-500">No recent errors.</p>
        @else
            <div class="space-y-1 font-mono text-xs">
                @foreach($errors as $error)
                    <div class="bg-gray-950 p-2 rounded text-red-400 overflow-x-auto whitespace-nowrap">{{ $error }}</div>
                @endforeach
            </div>
        @endif
    </div>

    {{-- Quick actions --}}
    <div class="bg-gray-900 border border-gray-800 rounded-xl p-5">
        <h3 class="text-sm font-semibold text-gold mb-3 pb-2 border-b border-gray-800">Quick Actions</h3>
        <div class="flex flex-wrap gap-3">
            <form method="POST" action="{{ route('admin.refresh-cache') }}">
                @csrf
                <button class="bg-gold hover:bg-yellow-500 text-black font-bold px-4 py-2 rounded text-sm">Refresh Dashboard Cache</button>
            </form>
            <a href="{{ route('admin.settings.index') }}" class="bg-gray-700 hover:bg-gray-600 text-white font-bold px-4 py-2 rounded text-sm">Go to Settings</a>
        </div>
    </div>

</div>
@endsection
