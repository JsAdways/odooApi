<?php

namespace Jsadways\OdooApi\Dtos\Income;

use Jsadways\OdooApi\Dtos\OdooPayloadDto;

class IncomeDiscountListDto extends OdooPayloadDto
{
    public function __construct(
        public readonly array $filter,
        public readonly ?string $order_by = null,
        public readonly ?string $order = null,
        public readonly ?int $page = null,
        public readonly ?int $per_page = null,
    ) {}
}
