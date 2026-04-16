<?php

namespace Jsadways\OdooApi\Dtos\Income;

use Jsadways\OdooApi\Dtos\OdooPayloadDto;

class IncomeReceiptVoidDto extends OdooPayloadDto
{
    public function __construct(
        public readonly int $id,
        public readonly string $reason,
        public readonly string $creator_name,
        public readonly string $creator_email,
        public readonly bool $reply_received = false,
        public readonly ?string $file = null,
        public readonly ?string $reply_file = null,
    ) {}
}
