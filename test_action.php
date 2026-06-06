<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Product;

$user = User::where('role', 'FINISHED')->first();
session(['auth_user' => $user->toArray()]);

$semiStock = (new App\Http\Controllers\FinishedController())->action()->getData()['pageData'];
$beetroot = collect($semiStock['products'])->first(fn($p) => $p['name'] === 'Beetroot Powder');
echo json_encode($beetroot, JSON_PRETTY_PRINT);
