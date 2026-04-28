<?php

namespace Jsadways\OdooApi\Services\OdooService;

use Jsadways\OdooApi\Contracts\OdooServiceContract;
use Jsadways\OdooApi\Dtos\OdooPayloadDto;
use Jsadways\OdooApi\Enums\OdooEndpoint;
use Jsadways\OdooApi\Services\OdooService\Process\OdooLogin;
use Jsadways\OdooApi\Services\OdooService\Process\OdooProcess;

class OdooService implements OdooServiceContract
{
    public function __construct(
        protected OdooProcess $odooProcess,
        protected OdooLogin $odooLogin,
    ) {
        $this->odooProcess->setToken($this->odooLogin->login());
    }

    public function create(OdooEndpoint $endpoint, OdooPayloadDto $payload)
    {
        $data = $payload->get();

        $this->odooProcess
            ->gen_transaction_key()
            ->cache_data($endpoint->method(), $endpoint->value, $data)
            ->request();

        if ($this->odooProcess->isSuccess()) {
            $this->odooProcess->remove_cache_data();
        }

        return $this->odooProcess->getResult();
    }

    public function update(OdooEndpoint $endpoint, OdooPayloadDto $payload)
    {
        $data = $payload->get();

        $this->odooProcess
            ->gen_transaction_key()
            ->cache_data($endpoint->method(), $endpoint->value, $data)
            ->request();

        if ($this->odooProcess->isSuccess()) {
            $this->odooProcess->remove_cache_data();
        }

        return $this->odooProcess->getResult();
    }

    public function list(OdooEndpoint $endpoint, OdooPayloadDto $payload)
    {
        $data = $payload->get();

        $this->odooProcess
            ->gen_transaction_key()
            ->cache_data($endpoint->method(), $endpoint->value, $data)
            ->request();

        if ($this->odooProcess->isSuccess()) {
            $this->odooProcess->remove_cache_data();
        }

        return $this->odooProcess->getResult();
    }

    public function retry(): array
    {
        $this->odooProcess->setToken($this->odooLogin->login());

        $this->odooProcess
            ->get_pending_requests()
            ->retry_all();

        return $this->odooProcess->getResult();
    }
}
