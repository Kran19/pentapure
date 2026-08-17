<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$request = Illuminate\Http\Request::create('/admin/stock', 'GET');
$response = $kernel->handle($request);
file_put_contents('stock_output.html', $response->getContent());
