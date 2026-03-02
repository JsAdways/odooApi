<?php

namespace Jsadways\OdooApi\Services\OdooService;

use Jsadways\OdooApi\Contracts\OdooServiceContract;
use Jsadways\OdooApi\Services\OdooService\Process\OdooProcess;

class OdooService implements OdooServiceContract
{
    public function create(string $url, array $data)
    {
        $process = (new OdooProcess())
            ->gen_transaction_key()
            ->cache_data($data)
            ->request('post', $url, $data);

        if (!$process->isSuccess()) {
            $process->remove_cache_data();
        }

        return $process->getResult();
    }

    public function update(string $url, array $data)
    {
        $process = (new OdooProcess())
            ->gen_transaction_key()
            ->cache_data($data)
            ->request('put', $url, $data);

        if (!$process->isSuccess()) {
            $process->remove_cache_data();
        }

        return $process->getResult();
    }

    public function list(string $url, array $data = [])
    {
        $process = (new OdooProcess())
            ->gen_transaction_key()
            ->request('get', $url, $data);

        return $process->getResult();
    }
}
