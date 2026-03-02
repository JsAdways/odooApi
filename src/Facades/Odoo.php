<?php

namespace Jsadways\OdooApi\Facades;

use Illuminate\Support\Facades\Facade;
use Jsadways\OdooApi\Contracts\OdooServiceContract;
use Jsadways\OdooApi\Services\OdooService\OdooService;

/** @mixin OdooService */
class Odoo extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return OdooServiceContract::class;
    }
}
