<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$products = \Illuminate\Support\Facades\DB::table('products')->where('name', 'like', '%jeera%')->get();
foreach($products as $p) {
    echo $p->name . PHP_EOL;
}
