<?php

namespace Jsadways\OdooApi\Services\OdooService\Process;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class OdooProcess
{
    protected const PENDING_INDEX_KEY = 'odoo_pending_requests';

    protected string $transactionKey;
    protected string $cacheKey;
    protected array $pendingRequests = [];
    protected mixed $result = null;
    protected bool $success = false;

    public function gen_transaction_key(): static
    {
        $this->transactionKey = str_replace('-', '', Str::uuid()->toString());
        return $this;
    }

    public function cache_data(string $method, string $url, mixed $data): static
    {
        $this->cacheKey = $this->transactionKey;
        Cache::put($this->cacheKey, [
            'transaction_key' => $this->transactionKey,
            'method' => $method,
            'url' => $url,
            'data' => $data,
            'cached_at' => now()->toDateTimeString(),
        ]);

        $index = Cache::get(self::PENDING_INDEX_KEY, []);
        $index[] = $this->cacheKey;
        Cache::put(self::PENDING_INDEX_KEY, array_unique($index));

        return $this;
    }

    public function request(): static
    {
        $cached = Cache::get($this->cacheKey);

        $method = $cached['method'];
        $url = $cached['url'];
        $data = $cached['data'];

        $fullUrl = rtrim(config('odoo_api.odoo_server_host'), '/') . '/' . ltrim($url, '/');
        $response = Http::$method($fullUrl, $this->_gen_payload($data));
        $this->success = $response->successful();
        $this->result = $response->json();
        return $this;
    }

    public function remove_cache_data(): static
    {
        Cache::forget($this->cacheKey);

        $index = Cache::get(self::PENDING_INDEX_KEY, []);
        $index = array_values(array_filter($index, fn ($key) => $key !== $this->cacheKey));
        Cache::put(self::PENDING_INDEX_KEY, $index);

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

    public function get_pending_requests(): static
    {
        $index = Cache::get(self::PENDING_INDEX_KEY, []);
        $requests = [];
        foreach ($index as $cacheKey) {
            $cached = Cache::get($cacheKey);
            if ($cached) {
                $requests[] = $cached;
            }
        }

        usort($requests, fn ($a, $b) => $a['cached_at'] <=> $b['cached_at']);

        $this->pendingRequests = $requests;
        return $this;
    }

    public function retry_all(): static
    {
        $results = [];
        foreach ($this->pendingRequests as $cached) {
            $retryProcess = (new self())
                ->from_cache($cached['transaction_key'])
                ->request();

            if ($retryProcess->isSuccess()) {
                $retryProcess->remove_cache_data();
            }

            $results[] = [
                'transaction_key' => $cached['transaction_key'],
                'success' => $retryProcess->isSuccess(),
                'result' => $retryProcess->getResult(),
            ];
        }
        $this->result = $results;
        return $this;
    }

    public function from_cache(string $cacheKey): static
    {
        $cached = Cache::get($cacheKey);
        $this->cacheKey = $cacheKey;
        $this->transactionKey = $cached['transaction_key'];
        return $this;
    }

    private function _gen_payload(mixed $data): array
    {
        return [
            'transaction_key' => $this->transactionKey,
            'data' => $data,
        ];
    }
}
