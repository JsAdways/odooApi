<?php

namespace Jsadways\OdooApi\Dtos\Income;

use Jsadways\OdooApi\Dtos\OdooPayloadDto;

class IncomeReceiptCueDto extends OdooPayloadDto
{
    public function __construct(
        public readonly int $id,
        public readonly int|float $finance_income,
        public readonly int|float $offseted_price,
        public readonly int|float $offset_price,
    ) {}
}
