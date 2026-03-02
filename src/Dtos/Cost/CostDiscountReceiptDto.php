<?php

namespace Jsadways\OdooApi\Dtos\Cost;

use Jsadways\OdooApi\Dtos\OdooPayloadDto;

class CostDiscountReceiptDto extends OdooPayloadDto
{
    /**
     * @param CostDiscountReceiptCueDto[] $cue
     */
    public function __construct(
        public readonly int $id,
        public readonly int|float $discount_price,
        public readonly array $cue,
    ) {}

    public function get(): array
    {
        $data = parent::get();
        $data['cue'] = array_map(fn (CostDiscountReceiptCueDto $cue) => $cue->get(), $this->cue);
        return $data;
    }
}
