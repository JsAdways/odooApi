<?php

namespace Jsadways\OdooApi\Tests;

use Tests\TestCase;

abstract class OdooLiveTestCase extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (empty(config('odoo_api.odoo_server_host'))
            || empty(config('odoo_api.odoo_user'))
            || empty(config('odoo_api.odoo_password'))) {
            $this->markTestSkipped('Odoo API config is not set (ODOO_SERVER_HOST / ODOO_USER / ODOO_PASSWORD)');
        }
    }

    protected function assertOdooSuccess(mixed $result, string $label): void
    {
        dump([$label => $result]);
        $this->assertIsArray($result, "[$label] response is not array (got ".var_export($result, true).')');
        $this->assertSame(200, $result['status_code'] ?? null, "[$label] status_code != 200, body=".json_encode($result));
        $this->assertArrayHasKey('data', $result, "[$label] missing data field");
    }
}
