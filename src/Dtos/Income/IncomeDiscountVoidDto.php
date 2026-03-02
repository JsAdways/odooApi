<?php

namespace Jsadways\OdooApi\Dtos\Income;

use Jsadways\OdooApi\Dtos\OdooPayloadDto;

class IncomeDiscountVoidDto extends OdooPayloadDto
{
    public function __construct(
        public readonly int $id,
        public readonly string $reason,
        public readonly ?string $file = null,
    ) {}
}
