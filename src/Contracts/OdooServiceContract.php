<?php

namespace Jsadways\OdooApi\Contracts;

use Jsadways\OdooApi\Dtos\OdooPayloadDto;
use Jsadways\OdooApi\Enums\OdooEndpoint;

interface OdooServiceContract
{
    public function create(OdooEndpoint $endpoint, OdooPayloadDto $payload);
    public function update(OdooEndpoint $endpoint, OdooPayloadDto $payload);
    public function list(OdooEndpoint $endpoint, OdooPayloadDto $payload);
}
