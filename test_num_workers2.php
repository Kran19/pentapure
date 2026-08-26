<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$worker = \App\Models\Worker::where('salary_type', 'LABOUR_MUKADAM')->first();
if ($worker) {
    \App\Models\Attendance::updateOrCreate(
        ['worker_id' => $worker->id, 'date' => '2026-08-26'],
        ['num_workers' => 999]
    );
    $att = \App\Models\Attendance::where('worker_id', $worker->id)->where('date', '2026-08-26')->first();
    echo "Saved num_workers: " . $att->num_workers . "\n";
} else {
    echo "No LABOUR_MUKADAM worker found\n";
}
