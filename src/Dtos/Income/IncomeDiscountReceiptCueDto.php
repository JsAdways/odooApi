<?php

namespace Jsadways\OdooApi\Dtos\Income;

use Jsadways\OdooApi\Dtos\OdooPayloadDto;

class IncomeDiscountReceiptCueDto extends OdooPayloadDto
{
    public function __construct(
        public readonly int $id,
        public readonly int|float $discount_price,
    ) {}
}
