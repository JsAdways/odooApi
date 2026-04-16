<?php

namespace Jsadways\OdooApi\Dtos\Income;

use Jsadways\OdooApi\Dtos\OdooPayloadDto;

class IncomeCreditNoteDto extends OdooPayloadDto
{
    public function __construct(
        public readonly int $dollar_type,
        public readonly string $item_name,
        public readonly string $credit_note_number,
        public readonly string $date,
        public readonly string $contact_name,
        public readonly string $contact_address,
        public readonly string $memo,
    ) {}
}
