<?php

namespace Jsadways\OdooApi\Facades;

use Illuminate\Support\Facades\Facade;
use Jsadways\OdooApi\Contracts\OdooServiceContract;

class Odoo extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return OdooServiceContract::class;
    }
}
