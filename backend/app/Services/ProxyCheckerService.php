<?php

namespace App\Services;

use App\Events\ProxyStatusUpdated;
use App\Models\Proxy;
use Illuminate\Support\Facades\Log;

class ProxyCheckerService
{
    // Для HTTP прокси используем HTTP URL — не нужен CONNECT тоннель
    private const CHECK_URL_HTTP  = 'http://api.ipify.org?format=json';
    // Для SOCKS прокси используем HTTPS
    private const CHECK_URL_SOCKS = 'https://api.ipify.org?format=json';

    private const TIMEOUT = 15;

    public function check(Proxy $proxy): bool
    {
        $proxy->update(['status' => 'checking']);

        $startTime = microtime(true);

        try {
            $ch = curl_init();

            $checkUrl = in_array($proxy->protocol, ['socks4', 'socks5'])
                ? self::CHECK_URL_SOCKS
                : self::CHECK_URL_HTTP;

            curl_setopt_array($ch, [
                CURLOPT_URL             => $checkUrl,
                CURLOPT_PROXY           => $this->buildProxyUrl($proxy),
                CURLOPT_RETURNTRANSFER  => true,
                CURLOPT_TIMEOUT         => self::TIMEOUT,
                CURLOPT_CONNECTTIMEOUT  => self::TIMEOUT,
                CURLOPT_FOLLOWLOCATION  => true,
                CURLOPT_MAXREDIRS       => 3,
                CURLOPT_SSL_VERIFYPEER  => false,
                CURLOPT_SSL_VERIFYHOST  => 0,
                CURLOPT_USERAGENT       => 'Mozilla/5.0 (compatible; ProxyChecker/1.0)',
                CURLOPT_HTTPPROXYTUNNEL => 0,
            ]);

            if (in_array($proxy->protocol, ['socks4', 'socks5'])) {
                curl_setopt($ch, CURLOPT_PROXYTYPE,
                    $proxy->protocol === 'socks5' ? CURLPROXY_SOCKS5 : CURLPROXY_SOCKS4
                );
                curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL, 1);
            }

            if ($proxy->username && $proxy->password) {
                curl_setopt($ch, CURLOPT_PROXYUSERPWD, "{$proxy->username}:{$proxy->password}");
            }

            curl_exec($ch);
            $httpCode   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlErrNo  = curl_errno($ch);
            $curlErrMsg = curl_error($ch);
            curl_close($ch);

            $responseTimeMs = (int) round((microtime(true) - $startTime) * 1000);

            Log::debug("Proxy check [{$proxy->host}:{$proxy->port}]", [
                'http_code'   => $httpCode,
                'curl_errno'  => $curlErrNo,
                'curl_error'  => $curlErrMsg,
                'response_ms' => $responseTimeMs,
                'check_url'   => $checkUrl,
            ]);

            // Прокси активен если нет ошибки соединения (errno=0) и получен
            // любой HTTP-ответ. Таймаут (28) или отказ (7) = недоступен.
            $isAlive = ($curlErrNo === 0 && $httpCode > 0);

            if ($isAlive) {
                $proxy->update([
                    'status'           => 'active',
                    'response_time_ms' => $responseTimeMs,
                    'last_checked_at'  => now(),
                ]);
                broadcast(new ProxyStatusUpdated($proxy->fresh()));
                return true;
            }

            $proxy->update([
                'status'           => 'inactive',
                'response_time_ms' => null,
                'last_checked_at'  => now(),
            ]);
            broadcast(new ProxyStatusUpdated($proxy->fresh()));
            return false;

        } catch (\Throwable $e) {
            Log::error("Proxy check exception [{$proxy->host}:{$proxy->port}]: " . $e->getMessage());

            $proxy->update([
                'status'           => 'inactive',
                'response_time_ms' => null,
                'last_checked_at'  => now(),
            ]);
            return false;
        }
    }

    private function buildProxyUrl(Proxy $proxy): string
    {
        $scheme = match ($proxy->protocol) {
            'socks4' => 'socks4',
            'socks5' => 'socks5',
            default  => 'http',
        };
        return "{$scheme}://{$proxy->host}:{$proxy->port}";
    }
}
