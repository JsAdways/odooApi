<?php

namespace Jsadways\OdooApi\Dtos\Product;

use Jsadways\OdooApi\Dtos\OdooPayloadDto;

class ProductCreateDto extends OdooPayloadDto
{
    public function __construct(
        public readonly string $name,
        public readonly ?string $memo = null,
    ) {}
}
