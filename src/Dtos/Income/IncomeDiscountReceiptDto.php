<?php

namespace Jsadways\OdooApi\Dtos\Income;

use Jsadways\OdooApi\Dtos\OdooPayloadDto;

class IncomeDiscountReceiptDto extends OdooPayloadDto
{
    /**
     * @param IncomeDiscountReceiptCueDto[] $cue
     */
    public function __construct(
        public readonly int $id,
        public readonly int|float $discount_price,
        public readonly array $cue,
    ) {}

    public function get(): array
    {
        $data = parent::get();
        $data['cue'] = array_map(fn (IncomeDiscountReceiptCueDto $cue) => $cue->get(), $this->cue);
        return $data;
    }
}
