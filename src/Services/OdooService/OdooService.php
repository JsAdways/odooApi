<?php

namespace Jsadways\OdooApi\Services\OdooService;

use Jsadways\OdooApi\Contracts\OdooServiceContract;
use Jsadways\OdooApi\Dtos\OdooPayloadDto;
use Jsadways\OdooApi\Enums\OdooEndpoint;
use Jsadways\OdooApi\Services\OdooService\Process\OdooProcess;

class OdooService implements OdooServiceContract
{
    public function create(OdooEndpoint $endpoint, OdooPayloadDto $payload)
    {
        $data = $payload->get();

        $process = (new OdooProcess())
            ->gen_transaction_key()
            ->cache_data($endpoint->method(), $endpoint->value, $data)
            ->request();

        if ($process->isSuccess()) {
            $process->remove_cache_data();
        }

        return $process->getResult();
    }

    public function update(OdooEndpoint $endpoint, OdooPayloadDto $payload)
    {
        $data = $payload->get();

        $process = (new OdooProcess())
            ->gen_transaction_key()
            ->cache_data($endpoint->method(), $endpoint->value, $data)
            ->request();

        if ($process->isSuccess()) {
            $process->remove_cache_data();
        }

        return $process->getResult();
    }

    public function list(OdooEndpoint $endpoint, OdooPayloadDto $payload)
    {
        $data = $payload->get();

        $process = (new OdooProcess())
            ->gen_transaction_key()
            ->cache_data($endpoint->method(), $endpoint->value, $data)
            ->request();

        if ($process->isSuccess()) {
            $process->remove_cache_data();
        }

        return $process->getResult();
    }

    public function retry(): array
    {
        $process = (new OdooProcess())
            ->get_pending_requests()
            ->retry_all();

        return $process->getResult();
    }
}
