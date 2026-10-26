<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyMpesaSource
{
    /**
     * Safaricom's published callback IP ranges.
     */
    protected array $allowedCidrs = [
        '196.201.214.0/26',
        '196.201.214.128/27',
        '196.201.214.200/30',
        '196.201.214.205/32',
        '196.201.214.206/31',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        // Allow localhost during local development
        if (app()->environment('local') && in_array($request->ip(), ['127.0.0.1', '::1'])) {
            return $next($request);
        }

        if (!$this->ipInRanges($request->ip(), $this->allowedCidrs)) {
            \Log::warning('M-Pesa callback rejected from unauthorized IP', ['ip' => $request->ip()]);
            return response()->json(['ResultCode' => 1, 'ResultDesc' => 'Unauthorized'], 403);
        }

        return $next($request);
    }

    protected function ipInRanges(string $ip, array $cidrs): bool
    {
        $ipLong = ip2long($ip);
        if ($ipLong === false) return false;

        foreach ($cidrs as $cidr) {
            [$subnet, $bits] = explode('/', $cidr);
            $subnetLong = ip2long($subnet);
            $mask = -1 << (32 - (int) $bits);
            if (($ipLong & $mask) === ($subnetLong & $mask)) {
                return true;
            }
        }
        return false;
    }
}
