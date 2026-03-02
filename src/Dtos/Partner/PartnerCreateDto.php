<?php

namespace Jsadways\OdooApi\Dtos\Partner;

use Jsadways\OdooApi\Dtos\OdooPayloadDto;

class PartnerCreateDto extends OdooPayloadDto
{
    public function __construct(
        public readonly int $type,
        public readonly string $name,
        public readonly string $short_name,
        public readonly string $en_name,
        public readonly string $id_number,
        public readonly string $address,
        public readonly string $tel,
        public readonly ?string $finance_email = null,
    ) {}
}
