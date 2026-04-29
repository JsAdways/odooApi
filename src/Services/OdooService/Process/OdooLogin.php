<?php

namespace Jsadways\OdooApi\Services\OdooService\Process;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class OdooLogin
{
    protected const CACHE_KEY = 'odoo_api_session_token';
    protected const LOGIN_ENDPOINT = '/user/login';
    protected const SAFETY_BUFFER_SECONDS = 60;

    public function login(): string
    {
        $cachedToken = Cache::get(self::CACHE_KEY);
        if (!empty($cachedToken)) {
            return $cachedToken;
        }

        return $this->_request_new_token();
    }

    private function _request_new_token(): string
    {
        $host = rtrim(config('odoo_api.odoo_server_host'), '/');
        $user = config('odoo_api.odoo_user');
        $password = config('odoo_api.odoo_password');

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])->post($host . self::LOGIN_ENDPOINT, [
            'transaction_key' => 'LOGIN-' . uniqid(),
            'data' => [
                'login' => $user,
                'password' => $password,
            ],
        ]);

        if (!$response->successful()) {
            throw new RuntimeException(
                'Odoo login failed: HTTP ' . $response->status() . ' ' . $response->body()
            );
        }

        $body = $response->json();
        $token = $body['data']['session_token'] ?? null;
        $expiresAt = $body['data']['expires_at'] ?? null;

        if (empty($token) || empty($expiresAt)) {
            throw new RuntimeException('Odoo login response missing session_token or expires_at');
        }

//        $ttl = Carbon::parse($expiresAt)->diffInSeconds(now()) - self::SAFETY_BUFFER_SECONDS;
//        $ttl = now()->diffInSeconds($expiresAt, false) - self::SAFETY_BUFFER_SECONDS;
        $ttl = Carbon::parse($expiresAt)->timestamp - now()->timestamp - self::SAFETY_BUFFER_SECONDS;
        if ($ttl <= 0) {
            throw new RuntimeException('Odoo login returned already-expired token');
        }

        Cache::put(self::CACHE_KEY, $token, $ttl);

        return $token;
    }
}
