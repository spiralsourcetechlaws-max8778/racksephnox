<?php

// ═══════════════════════════════════════════════════════════════
//  PRODUCTION SAFETY · Force errors to log, never to output
//  This prevents "headers already sent" errors from deprecations
//  and warnings breaking JSON/HTML responses in production.
// ═══════════════════════════════════════════════════════════════
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
ini_set('log_errors', '1');
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE & ~E_STRICT);
// ═══════════════════════════════════════════════════════════════

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

require __DIR__.'/../vendor/autoload.php';

$app = require_once __DIR__.'/../bootstrap/app.php';

$app->handleRequest(Request::capture());
