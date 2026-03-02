<?php

namespace Jsadways\OdooApi\Dtos\Income;

use Jsadways\OdooApi\Dtos\OdooPayloadDto;

class IncomeCostReceiptDto extends OdooPayloadDto
{
    public function __construct(
        public readonly string $receipt_dt,
        public readonly string $item_name,
        public readonly string $receipt_number,
        public readonly string $file,
    ) {}
}
