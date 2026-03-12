<?php

namespace App\Services;

use App\Models\Proxy;

class ProxyCheckerService
{
    private const CHECK_URL = 'https://api.ipify.org?format=json';
    private const TIMEOUT   = 10;

    public function check(Proxy $proxy): bool
    {
        $proxy->update(['status' => 'checking']);

        $startTime = microtime(true);

        try {
            $ch = curl_init();

            curl_setopt_array($ch, [
                CURLOPT_URL            => self::CHECK_URL,
                CURLOPT_PROXY          => $this->buildProxyUrl($proxy),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => self::TIMEOUT,
                CURLOPT_CONNECTTIMEOUT => self::TIMEOUT,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_USERAGENT      => 'ProxyManager/1.0',
            ]);

            if (in_array($proxy->protocol, ['socks4', 'socks5'])) {
                curl_setopt($ch, CURLOPT_PROXYTYPE,
                    $proxy->protocol === 'socks5' ? CURLPROXY_SOCKS5 : CURLPROXY_SOCKS4
                );
            }

            if ($proxy->username && $proxy->password) {
                curl_setopt($ch, CURLOPT_PROXYUSERPWD, "{$proxy->username}:{$proxy->password}");
            }

            $response  = curl_exec($ch);
            $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_errno($ch);
            curl_close($ch);

            $responseTimeMs = (int) round((microtime(true) - $startTime) * 1000);

            if (!$curlError && $response !== false && $httpCode >= 200 && $httpCode < 300) {
                $proxy->update([
                    'status'           => 'active',
                    'response_time_ms' => $responseTimeMs,
                    'last_checked_at'  => now(),
                ]);
                return true;
            }

            $proxy->update([
                'status'           => 'inactive',
                'response_time_ms' => null,
                'last_checked_at'  => now(),
            ]);
            return false;

        } catch (\Throwable $e) {
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

