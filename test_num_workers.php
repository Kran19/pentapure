<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$a = \App\Models\Attendance::whereNotNull('num_workers')->get();
echo json_encode($a->toArray());
