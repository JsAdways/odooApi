<?php

namespace Jsadways\OdooApi\Dtos\Income;

use Jsadways\OdooApi\Dtos\OdooPayloadDto;

class IncomeReceiptCreateDto extends OdooPayloadDto
{
    /**
     * @param int $receipt_type 1=電子發票三聯, 2=電子發票二聯, 3=國外Invoice
     * @param int $dollar_type 1=新台幣, 2=美金, 3=日幣
     * @param IncomeReceiptCueDto[] $cue Cue 沖銷陣列
     * @param NotificationEmailDto[] $notification_email 通知信箱陣列
     */
    public function __construct(
        public readonly int $campaign_id,
        public readonly int $receipt_type,
        public readonly int $dollar_type,
        public readonly string $item_name,
        public readonly int|float $price,
        public readonly int|float $tax,
        public readonly int|float $total_price,
        public readonly array $cue,
        public readonly array $notification_email,
        public readonly string $creator_name,
        public readonly string $creator_email,
        public readonly bool $urgent = false,
        public readonly ?string $memo = null,
        public readonly ?string $specify_dt = null,
        public readonly ?string $pickup_dt = null,
    ) {}

    public function get(): array
    {
        $data = parent::get();
        $data['cue'] = array_map(fn (IncomeReceiptCueDto $cue) => $cue->get(), $this->cue);
        $data['notification_email'] = array_map(fn (NotificationEmailDto $email) => $email->get(), $this->notification_email);
        return $data;
    }
}
