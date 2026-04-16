<?php

namespace Jsadways\OdooApi\Dtos\Income;

use Jsadways\OdooApi\Dtos\OdooPayloadDto;

class IncomeReceiptVoidUpdateDto extends OdooPayloadDto
{
    public function __construct(
        public readonly int $id,
        public readonly bool $reply_received,
        public readonly ?string $reply_file = null,
    ) {}
}
