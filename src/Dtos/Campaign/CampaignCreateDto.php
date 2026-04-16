<?php

namespace Jsadways\OdooApi\Dtos\Campaign;

use Jsadways\OdooApi\Dtos\OdooPayloadDto;

class CampaignCreateDto extends OdooPayloadDto
{
    /**
     * @param int $organization 1=傑思, 2=豐富, 3=香港
     * @param CampaignCueDto[] $cue 子訂單陣列
     */
    public function __construct(
        public readonly int $organization,
        public readonly string $name,
        public readonly int $client_id,
        public readonly string $campaign_number,
        public readonly string $start_dt,
        public readonly string $end_dt,
        public readonly int|float $price,
        public readonly int|float $tax,
        public readonly int|float $total_price,
        public readonly int $status,
        public readonly int|float $exchange_rate,
        public readonly array $cue,
        public readonly ?string $client_contact_email = null,
        public readonly ?string $message = null,
        public readonly ?string $memo = null,
    ) {}

    public function get(): array
    {
        $data = parent::get();
        $data['cue'] = array_map(fn (CampaignCueDto $cue) => $cue->get(), $this->cue);
        return $data;
    }
}
