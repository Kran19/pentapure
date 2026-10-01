<?php
require __DIR__ . '/vendor/autoload.php';

$stockDate = '2026-10-01';
$f1 = function() {
    $f2 = function() use ($stockDate) {
        var_dump($stockDate);
    };
    $f2();
};
$f1();
    