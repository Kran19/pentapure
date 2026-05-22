<?php
require __DIR__ . '/vendor/autoload.php';

use Barryvdh\DomPDF\Facade\Pdf;

// Boot laravel app for DomPDF
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$dompdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML('<h1>Test</h1>');
$pdfContent = $dompdf->output();

$tmpFile = tempnam(sys_get_temp_dir(), 'test_fpdi_') . '.pdf';
file_put_contents($tmpFile, $pdfContent);

$fpdi = new \setasign\Fpdi\Fpdi();
try {
    $pages = $fpdi->setSourceFile($tmpFile);
    echo "FPDI successfully read DomPDF output. Pages: $pages\n";
} catch (\Exception $e) {
    echo "FPDI failed: " . $e->getMessage() . "\n";
}
@unlink($tmpFile);
