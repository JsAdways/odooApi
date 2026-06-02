<?php

namespace Jsadways\OdooApi\Dtos\Campaign;

use Jsadways\OdooApi\Dtos\OdooPayloadDto;

class CampaignCueDto extends OdooPayloadDto
{
    /**
     * @param int $type 1=AP, 2=BR, 3=EC
     */
    public function __construct(
        public readonly string $cue_number,
        public readonly int $type,
        public readonly int $product_id,
        public readonly int $vendor_id,
        public readonly int|float $budget,
        public readonly string $month,
        public readonly int|float $income,
        public readonly int|float $income_total,
        public readonly int|float $income_discount,
        public readonly int|float $income_rebate,
        public readonly int|float $cost,
        public readonly int|float $cost_total,
        public readonly int|float $cost_discount,
        public readonly int|float $cost_rebate,
        public readonly string $profit_code,
        public readonly string $cost_code,
        public readonly ?int $id = null,
    ) {}
}
