@extends('admin.layouts.app')

@section('page-title', 'System Monitoring')
@section('breadcrumbs')
    <a href="{{ route('admin.dashboard') }}" class="text-gold hover:text-yellow-400">Dashboard</a>
    <span class="mx-1">/</span> Monitoring
@endsection

@section('content')
@php
    $formatBytes = function ($bytes) {
        if ($bytes === null || $bytes === false) return '—';
        $bytes = (float) $bytes;
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) { $bytes /= 1024; $i++; }
        return round($bytes, 2) . ' ' . $units[$i];
    };

    $dbUp         = ($database['status'] ?? '') === 'up';
    $cacheUp      = ($cache['status'] ?? '') === 'up';
    $errorsToday  = (int) ($logs['errors_today'] ?? 0);
    $failedJobs   = (int) ($queue['failed'] ?? 0);
    $pendingMigs  = (int) ($database['migrations']['pending'] ?? 0);

    $criticalDown = !$dbUp || !$cacheUp;
    $hasWarnings  = $errorsToday > 0 || $failedJobs > 0 || $pendingMigs > 0;
    $allHealthy   = !$criticalDown && !$hasWarnings;
@endphp

<div class="space-y-4">

    {{-- ==================== STATUS BANNER ==================== --}}
    @if($criticalDown)
        <div class="bg-red-500/10 border-2 border-red-500/60 p-4 rounded-lg flex items-center justify-between">
            <div class="flex items-center gap-3">
                <i class="fas fa-exclamation-circle text-red-400 text-3xl"></i>
                <div>
                    <p class="text-lg font-bold text-red-400">System Degraded</p>
                    <p class="text-sm text-red-300/80">One or more critical services are offline. Investigate immediately.</p>
                </div>
            </div>
        </div>
    @elseif($hasWarnings)
        <div class="bg-yellow-500/10 border-2 border-yellow-500/60 p-4 rounded-lg flex items-center justify-between">
            <div class="flex items-center gap-3">
                <i class="fas fa-exclamation-triangle text-yellow-400 text-3xl"></i>
                <div>
                    <p class="text-lg font-bold text-yellow-400">Minor Issues Detected</p>
                    <p class="text-sm text-yellow-300/80">
                        @if($errorsToday > 0){{ $errorsToday }} error(s) today. @endif
                        @if($failedJobs > 0){{ $failedJobs }} failed job(s). @endif
                        @if($pendingMigs > 0){{ $pendingMigs }} pending migration(s). @endif
                    </p>
                </div>
            </div>
        </div>
    @else
        <div class="bg-green-500/10 border-2 border-green-500/60 p-4 rounded-lg flex items-center justify-between">
            <div class="flex items-center gap-3">
                <i class="fas fa-check-circle text-green-400 text-3xl"></i>
                <div>
                    <p class="text-lg font-bold text-green-400">All Systems Operational</p>
                    <p class="text-sm text-green-300/80">Every monitored service is healthy.</p>
                </div>
            </div>
        </div>
    @endif

    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-xl font-bold text-white">Live System Monitoring</h2>
            <p class="text-sm text-gray-400">Read-only snapshot of your platform's current health.</p>
        </div>
        <a href="{{ route('admin.monitoring.index') }}" class="px-4 py-2 bg-gray-700 hover:bg-gray-600 text-white rounded text-sm transition">
            <i class="fas fa-sync-alt mr-1"></i> Refresh
        </a>
    </div>

    {{-- ==================== CRITICAL SERVICES ==================== --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">

        {{-- Database --}}
        <div class="admin-card p-5 border-l-4 {{ $dbUp ? 'border-l-green-500' : 'border-l-red-500' }}">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-lg font-semibold text-gold">🗄️ Database</h3>
                <span class="px-2 py-0.5 text-xs rounded {{ $dbUp ? 'bg-green-500/20 text-green-400' : 'bg-red-500/20 text-red-400' }}">
                    {{ $dbUp ? 'UP' : 'DOWN' }}
                </span>
            </div>
            <dl class="space-y-1 text-sm">
                <div class="flex justify-between"><dt class="text-gray-400">Connection</dt><dd class="text-white font-mono">{{ $database['connection'] }}</dd></div>
                @if($dbUp)
                    <div class="flex justify-between"><dt class="text-gray-400">Ping</dt><dd class="text-white">{{ $database['ping_ms'] }} ms</dd></div>
                    <div class="flex justify-between"><dt class="text-gray-400">Database</dt><dd class="text-white text-xs truncate max-w-[180px]">{{ $database['database'] ?? '—' }}</dd></div>
                    <div class="flex justify-between"><dt class="text-gray-400">Size</dt><dd class="text-white">{{ $database['size'] ?? '—' }}</dd></div>
                    <div class="flex justify-between"><dt class="text-gray-400">Tables</dt><dd class="text-white">{{ $database['table_count'] ?? '—' }}</dd></div>
                    <div class="flex justify-between"><dt class="text-gray-400">Users</dt><dd class="text-white">{{ number_format($database['users'] ?? 0) }}</dd></div>
                    <div class="flex justify-between">
                        <dt class="text-gray-400">Migrations</dt>
                        <dd class="{{ $pendingMigs > 0 ? 'text-yellow-400' : 'text-green-400' }}">
                            {{ $pendingMigs > 0 ? "$pendingMigs pending" : 'Up to date' }}
                        </dd>
                    </div>
                @else
                    <p class="text-red-400 text-xs">{{ $database['error'] }}</p>
                @endif
            </dl>
        </div>

        {{-- Cache --}}
        <div class="admin-card p-5 border-l-4 {{ $cacheUp ? 'border-l-green-500' : 'border-l-red-500' }}">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-lg font-semibold text-gold">⚡ Cache</h3>
                <span class="px-2 py-0.5 text-xs rounded {{ $cacheUp ? 'bg-green-500/20 text-green-400' : 'bg-red-500/20 text-red-400' }}">
                    {{ strtoupper($cache['status']) }}
                </span>
            </div>
            <dl class="space-y-1 text-sm">
                <div class="flex justify-between"><dt class="text-gray-400">Driver</dt><dd class="text-white">{{ $cache['driver'] }}</dd></div>
                @if($cacheUp)
                    <div class="flex justify-between"><dt class="text-gray-400">Write/Read</dt><dd class="text-green-400">{{ $cache['test_ms'] }} ms</dd></div>
                @elseif(!empty($cache['error']))
                    <p class="text-red-400 text-xs">{{ $cache['error'] }}</p>
                @endif
            </dl>
        </div>

        {{-- Queue --}}
        <div class="admin-card p-5 border-l-4 {{ ($failedJobs > 0) ? 'border-l-yellow-500' : 'border-l-green-500' }}">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-lg font-semibold text-gold">📬 Queue</h3>
                <span class="px-2 py-0.5 text-xs rounded {{ $failedJobs > 0 ? 'bg-yellow-500/20 text-yellow-400' : 'bg-green-500/20 text-green-400' }}">
                    {{ $failedJobs > 0 ? 'WARN' : 'OK' }}
                </span>
            </div>
            <dl class="space-y-1 text-sm">
                <div class="flex justify-between"><dt class="text-gray-400">Driver</dt><dd class="text-white">{{ $queue['driver'] }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-400">Pending Jobs</dt><dd class="text-white">{{ $queue['pending'] ?? '—' }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-400">Failed Jobs</dt><dd class="{{ $failedJobs > 0 ? 'text-yellow-400 font-bold' : 'text-green-400' }}">{{ $failedJobs }}</dd></div>
                @if($queue['oldest_pending'])
                    <div class="flex justify-between"><dt class="text-gray-400">Oldest</dt><dd class="text-gray-300 text-xs">{{ $queue['oldest_pending'] }}</dd></div>
                @endif
            </dl>
        </div>
    </div>

    {{-- ==================== APPLICATION + SERVER ==================== --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">

        <div class="admin-card p-5">
            <h3 class="text-lg font-semibold text-gold mb-3 pb-2 border-b border-gray-800">🚀 Application</h3>
            <dl class="grid grid-cols-2 gap-y-1 gap-x-3 text-sm">
                <dt class="text-gray-400">Name</dt><dd class="text-white">{{ $app['name'] }}</dd>
                <dt class="text-gray-400">Environment</dt>
                <dd><span class="px-2 py-0.5 text-xs rounded {{ $app['environment'] === 'production' ? 'bg-green-500/20 text-green-400' : 'bg-yellow-500/20 text-yellow-400' }}">{{ $app['environment'] }}</span></dd>
                <dt class="text-gray-400">Debug</dt>
                <dd><span class="px-2 py-0.5 text-xs rounded {{ $app['debug'] ? 'bg-red-500/20 text-red-400' : 'bg-green-500/20 text-green-400' }}">{{ $app['debug'] ? 'ON ⚠️' : 'OFF' }}</span></dd>
                <dt class="text-gray-400">Laravel</dt><dd class="text-white font-mono text-xs">{{ $app['version'] }}</dd>
                <dt class="text-gray-400">PHP</dt><dd class="text-white font-mono text-xs">{{ $app['php_version'] }}</dd>
                <dt class="text-gray-400">URL</dt><dd class="text-white text-xs truncate">{{ $app['url'] }}</dd>
                <dt class="text-gray-400">Cache</dt><dd class="text-white text-xs">{{ $app['cache_driver'] }}</dd>
                <dt class="text-gray-400">Queue</dt><dd class="text-white text-xs">{{ $app['queue_driver'] }}</dd>
                <dt class="text-gray-400">Session</dt><dd class="text-white text-xs">{{ $app['session_driver'] }}</dd>
                <dt class="text-gray-400">Timezone</dt><dd class="text-white text-xs">{{ $app['timezone'] }}</dd>
            </dl>
        </div>

        <div class="admin-card p-5">
            <h3 class="text-lg font-semibold text-gold mb-3 pb-2 border-b border-gray-800">🖥️ Server</h3>
            <dl class="grid grid-cols-2 gap-y-1 gap-x-3 text-sm">
                <dt class="text-gray-400">OS</dt><dd class="text-white text-xs">{{ $system['os'] }}</dd>
                <dt class="text-gray-400">Hostname</dt><dd class="text-white text-xs">{{ $system['hostname'] }}</dd>
                <dt class="text-gray-400">Uptime</dt><dd class="text-white text-xs">{{ $system['uptime'] ?? '—' }}</dd>
                <dt class="text-gray-400">Load 1/5/15</dt><dd class="text-white font-mono text-xs">{{ $system['load_1'] }} / {{ $system['load_5'] }} / {{ $system['load_15'] }}</dd>
                <dt class="text-gray-400">Memory</dt><dd class="text-white text-xs">{{ $formatBytes($system['memory_used']) }} / {{ $system['memory_limit'] }}</dd>
                <dt class="text-gray-400">Peak</dt><dd class="text-white text-xs">{{ $formatBytes($system['memory_peak']) }}</dd>
                <dt class="text-gray-400">SAPI</dt><dd class="text-white text-xs">{{ $system['php_sapi'] }}</dd>
                <dt class="text-gray-400">Server</dt><dd class="text-white text-xs truncate">{{ $system['server_software'] }}</dd>
            </dl>
            <div class="mt-3 pt-3 border-t border-gray-800">
                <p class="text-xs text-gray-400 uppercase mb-2">Extensions</p>
                <div class="flex flex-wrap gap-1">
                    @foreach($system['extensions'] as $ext => $loaded)
                        <span class="px-2 py-0.5 text-xs rounded {{ $loaded ? 'bg-green-500/20 text-green-400' : 'bg-red-500/20 text-red-400' }}">
                            {{ $ext }}
                        </span>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    {{-- ==================== STORAGE ==================== --}}
    <div class="admin-card p-5">
        <h3 class="text-lg font-semibold text-gold mb-3 pb-2 border-b border-gray-800">💾 Storage</h3>
        @if($storage['total'])
            <div class="mb-2 flex justify-between text-sm">
                <span class="text-gray-400">Disk Usage</span>
                <span class="text-white">{{ $storage['used_pct'] }}% · {{ $formatBytes($storage['free']) }} free of {{ $formatBytes($storage['total']) }}</span>
            </div>
            <div class="w-full bg-gray-800 rounded-full h-3 mb-3">
                @php
                    $pct = $storage['used_pct'] ?? 0;
                    $barColor = $pct > 90 ? 'bg-red-500' : ($pct > 75 ? 'bg-yellow-500' : 'bg-green-500');
                @endphp
                <div class="{{ $barColor }} h-3 rounded-full transition-all" style="width: {{ $pct }}%"></div>
            </div>
        @endif
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 text-sm">
            <div><p class="text-xs text-gray-400 uppercase">Disk Free</p><p class="text-white font-semibold">{{ $formatBytes($storage['free']) }}</p></div>
            <div><p class="text-xs text-gray-400 uppercase">Log File</p><p class="text-white font-semibold">{{ $formatBytes($storage['log_size']) }}</p></div>
            <div><p class="text-xs text-gray-400 uppercase">Storage Dir</p><p class="text-white font-semibold">{{ $formatBytes($storage['storage_size']) }}</p></div>
            <div><p class="text-xs text-gray-400 uppercase">Log Age</p><p class="text-white font-semibold">{{ $logs['last_modified'] ? now()->setTimestamp($logs['last_modified'])->diffForHumans() : '—' }}</p></div>
        </div>
    </div>

    {{-- ==================== BUSINESS METRICS ==================== --}}
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3">
        <div class="admin-card p-4 text-center">
            <p class="text-xs text-gray-400 uppercase">Total Users</p>
            <p class="text-2xl font-bold text-gold">{{ number_format($business['users_total']) }}</p>
        </div>
        <div class="admin-card p-4 text-center">
            <p class="text-xs text-gray-400 uppercase">New Today</p>
            <p class="text-2xl font-bold text-green-400">+{{ number_format($business['users_today']) }}</p>
        </div>
        <div class="admin-card p-4 text-center">
            <p class="text-xs text-gray-400 uppercase">Active Sessions</p>
            <p class="text-2xl font-bold text-blue-400">{{ number_format($business['active_sessions']) }}</p>
            <p class="text-xs text-gray-500">last 15 min</p>
        </div>
        <div class="admin-card p-4 text-center">
            <p class="text-xs text-gray-400 uppercase">Txns Today</p>
            <p class="text-2xl font-bold text-white">{{ number_format($business['transactions_today']) }}</p>
        </div>
        <div class="admin-card p-4 text-center">
            <p class="text-xs text-gray-400 uppercase">Volume Today</p>
            <p class="text-xl font-bold text-gold">KES {{ number_format($business['volume_today'], 0) }}</p>
        </div>
        <div class="admin-card p-4 text-center">
            <p class="text-xs text-gray-400 uppercase">Failed Jobs (24h)</p>
            <p class="text-2xl font-bold {{ $business['failed_jobs_24h'] > 0 ? 'text-red-400' : 'text-green-400' }}">{{ number_format($business['failed_jobs_24h']) }}</p>
        </div>
    </div>

    {{-- ==================== RECENT ERRORS ==================== --}}
    <div class="admin-card p-5">
        <div class="flex justify-between items-center mb-3 pb-2 border-b border-gray-800">
            <div>
                <h3 class="text-lg font-semibold text-gold">⚠️ Recent Errors</h3>
                <p class="text-xs text-gray-500">{{ $errorsToday }} error(s) recorded today · {{ $logs['warnings_today'] }} warning(s)</p>
            </div>
            <span class="text-xs text-gray-500">{{ $formatBytes($logs['size']) }} log file</span>
        </div>

        @if(!empty($recentErrors))
            <div class="space-y-2 max-h-96 overflow-y-auto">
                @foreach($recentErrors as $errorLine)
                    <div class="bg-gray-800/50 border-l-2 border-l-red-500 p-2 rounded text-xs font-mono text-red-300 break-all">
                        {{ Str::limit($errorLine, 300) }}
                    </div>
                @endforeach
            </div>
        @elseif(!$logs['exists'])
            <p class="text-gray-500 text-sm">No log file yet.</p>
        @else
            <div class="text-center py-8">
                <i class="fas fa-check-circle text-4xl text-green-500/50 mb-2"></i>
                <p class="text-green-400 text-sm">No errors recorded recently. Clean logs! ✨</p>
            </div>
        @endif
    </div>

</div>
@endsection
