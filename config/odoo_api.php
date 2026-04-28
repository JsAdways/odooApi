<?php

return [
    'odoo_server_host' => env('ODOO_SERVER_HOST', 'http://localhost:8069'),
    'odoo_user' => env('ODOO_USER'),
    'odoo_password' => env('ODOO_PASSWORD'),
    'auto_retry' => env('ODOO_AUTO_RETRY', false),
];
