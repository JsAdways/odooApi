<?php

namespace Jsadways\OdooApi\Contracts;

interface OdooServiceContract
{
    public function create(string $url, array $data);
    public function update(string $url, array $data);
    public function list(string $url, array $data = []);
}
