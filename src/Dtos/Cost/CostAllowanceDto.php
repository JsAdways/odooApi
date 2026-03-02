<?php

namespace Jsadways\OdooApi\Dtos\Cost;

use Jsadways\OdooApi\Dtos\OdooPayloadDto;

class CostAllowanceDto extends OdooPayloadDto
{
    public function __construct(
        public readonly string $file,
    ) {}
}
