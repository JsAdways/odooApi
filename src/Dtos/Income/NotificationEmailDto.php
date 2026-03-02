<?php

namespace Jsadways\OdooApi\Dtos\Income;

use Jsadways\OdooApi\Dtos\OdooPayloadDto;

class NotificationEmailDto extends OdooPayloadDto
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
    ) {}
}
