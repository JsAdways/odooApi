<?php

namespace Jsadways\OdooApi\Dtos\Product;

use Jsadways\OdooApi\Dtos\OdooPayloadDto;

class ProductUpdateDto extends OdooPayloadDto
{
    /**
     * @param array<int, array{id: int, name: string, memo?: string}> $products
     */
    public function __construct(
        public readonly array $products,
    ) {}

    public function get(): array
    {
        return $this->products;
    }
}
