<?php

namespace Jsadways\OdooApi\Dtos\Cost;

use Jsadways\OdooApi\Dtos\OdooPayloadDto;

class CostDiscountReceiptCueDto extends OdooPayloadDto
{
    public function __construct(
        public readonly int $id,
        public readonly int|float $discount_price,
    ) {}
}
