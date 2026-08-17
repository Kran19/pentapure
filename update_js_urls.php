<?php

$dir = 'd:\pentapure\resources\views';
$iter = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
$count = 0;

$roles = ['raw', 'semi', 'finished', 'sales', 'dispatch', 'cashier', 'admin', 'attendance'];

foreach ($iter as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        $path = $file->getPathname();
        $content = file_get_contents($path);
        $original = $content;
        
        foreach ($roles as $role) {
            $content = preg_replace("/window\.userSlug \+ '\/$role\//", "window.userSlug + '/", $content);
            $content = preg_replace("/window\.userSlug \+ '\/$role'/", "window.userSlug + ''", $content);
        }
        
        if ($content !== $original) {
            file_put_contents($path, $content);
            $count++;
            echo "Updated JS in: $path\n";
        }
    }
}
echo "Total JS files updated: $count\n";
