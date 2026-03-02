<?php

namespace Jsadways\OdooApi\Services\OdooService\Process;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class OdooProcess
{
    protected string $transactionKey;
    protected mixed $result = null;
    protected bool $success = false;

    public function gen_transaction_key(): static
    {
        $this->transactionKey = str_replace('-', '', Str::uuid()->toString());
        return $this;
    }

    public function cache_data(mixed $data, ?string $key = null): static
    {
        $cacheKey = $key ?? $this->transactionKey;
        Cache::put($cacheKey, $this->_gen_payload($data));
        return $this;
    }

    public function request(string $method, string $url, mixed $data = []): static
    {
        $fullUrl = rtrim(config('odoo_api.odoo_server_host'), '/') . '/' . ltrim($url, '/');
        $response = Http::$method($fullUrl, $this->_gen_payload($data));
        $this->success = $response->successful();
        $this->result = $response->json();
        return $this;
    }

    public function remove_cache_data(?string $key = null): static
    {
        $cacheKey = $key ?? $this->transactionKey;
        Cache::forget($cacheKey);
        return $this;
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function getResult(): mixed
    {
        return $this->result;
    }

    public function getTransactionKey(): string
    {
        return $this->transactionKey;
    }

    private function _gen_payload(mixed $data): array
    {
        return [
            'transaction_key' => $this->transactionKey,
            'data' => $data,
        ];
    }
}
