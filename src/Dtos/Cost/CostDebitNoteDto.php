<?php

namespace Jsadways\OdooApi\Dtos\Cost;

use Jsadways\OdooApi\Dtos\OdooPayloadDto;

class CostDebitNoteDto extends OdooPayloadDto
{
    public function __construct(
        public readonly int $dollar_type,
        public readonly string $item_name,
        public readonly string $debit_note_number,
        public readonly string $memo,
    ) {}
}
