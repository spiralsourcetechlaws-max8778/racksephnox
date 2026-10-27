<?php

namespace App\Services\Mpesa;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MpesaClient
{
    protected array $config;
    protected string $env;
    protected array $urls;
    protected int $timeout = 20;

    public function __construct()
    {
        $this->config = config('mpesa');
        $this->env    = $this->config['environment'] ?? 'sandbox';
        $this->urls   = $this->config['urls'][$this->env] ?? [];
    }

    /**
     * Get OAuth access token, cached for 55 minutes (Safaricom gives 1h).
     */
    public function getAccessToken(): string
    {
        $cacheKey = "mpesa_oauth_{$this->env}";

        $cached = Cache::get($cacheKey);
        if ($cached) return $cached;

        $key    = $this->config['consumer_key'] ?? '';
        $secret = $this->config['consumer_secret'] ?? '';
        $url    = $this->urls['oauth'] ?? null;

        if (!$key || !$secret) {
            throw new \RuntimeException('M-Pesa consumer_key/consumer_secret not configured');
        }
        if (!$url) {
            throw new \RuntimeException('M-Pesa OAuth URL missing for env ' . $this->env);
        }

        try {
            $res = Http::timeout($this->timeout)
                ->withBasicAuth($key, $secret)
                ->get($url);

            if ($res->failed()) {
                Log::error('M-Pesa OAuth failed', [
                    'status' => $res->status(),
                    'body'   => substr($res->body(), 0, 500),
                ]);
                throw new \RuntimeException('M-Pesa OAuth failed: HTTP ' . $res->status());
            }

            $token = $res->json('access_token');
            if (!$token) {
                throw new \RuntimeException('M-Pesa OAuth returned no access_token');
            }

            Cache::put($cacheKey, $token, 3300);
            return $token;

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('M-Pesa OAuth connection failed', ['error' => $e->getMessage()]);
            throw new \RuntimeException('Cannot reach M-Pesa OAuth endpoint');
        }
    }

    /**
     * STK password = base64(Shortcode + Passkey + Timestamp).
     */
    public function generateStkPassword(string $timestamp): string
    {
        $shortcode = $this->config['shortcode'] ?? '';
        $passkey   = $this->config['passkey'] ?? '';
        return base64_encode($shortcode . $passkey . $timestamp);
    }

    /**
     * Make a signed POST to any M-Pesa endpoint.
     */
    public function post(string $endpointKey, array $data): array
    {
        $url = $this->urls[$endpointKey] ?? null;
        if (!$url) {
            throw new \RuntimeException("M-Pesa URL not configured for endpoint: {$endpointKey}");
        }

        $token = $this->getAccessToken();

        try {
            $res = Http::timeout($this->timeout)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $token,
                    'Content-Type'  => 'application/json',
                ])
                ->post($url, $data);

            $json = $res->json() ?? [];

            if ($res->failed()) {
                Log::error('M-Pesa API error', [
                    'endpoint' => $endpointKey,
                    'status'   => $res->status(),
                    'response' => $json,
                ]);
                throw new \RuntimeException(
                    'M-Pesa API error: ' . ($json['errorMessage'] ?? $json['ResponseDescription'] ?? 'HTTP ' . $res->status())
                );
            }

            return $json;

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('M-Pesa API connection failed', [
                'endpoint' => $endpointKey,
                'error'    => $e->getMessage(),
            ]);
            throw new \RuntimeException('Cannot reach M-Pesa API endpoint');
        }
    }

    public function isSandbox(): bool
    {
        return $this->env === 'sandbox';
    }
}
