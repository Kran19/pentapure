<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$stocks = \Illuminate\Support\Facades\DB::table('stocks')
    ->join('products', 'stocks.product_id', '=', 'products.id')
    ->selectRaw('stocks.product_id as productId, products.name, stocks.stage')
    ->get();

foreach($stocks as $s) {
    echo $s->name . ' - ' . $s->stage . PHP_EOL;
    break;
}
