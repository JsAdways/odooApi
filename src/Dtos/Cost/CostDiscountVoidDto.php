<?php

namespace Jsadways\OdooApi\Dtos\Cost;

use Jsadways\OdooApi\Dtos\OdooPayloadDto;

class CostDiscountVoidDto extends OdooPayloadDto
{
    public function __construct(
        public readonly int $id,
        public readonly string $reason,
        public readonly string $creator_name,
        public readonly string $creator_email,
        public readonly ?string $file = null,
    ) {}
}
