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
        
        // Pattern 1: request()->segment(1) . '/raw/' => request()->segment(1) . '/'
        // Pattern 2: request()->segment(1) . '/raw' => request()->segment(1) . '' (if there is no trailing slash)
        foreach ($roles as $role) {
            // Match exactly /role/ or /role at the end of the string concat
            $content = preg_replace("/request\(\)->segment\(1\) \. \'\/$role\//", "request()->segment(1) . '/", $content);
            $content = preg_replace("/request\(\)->segment\(1\) \. \'\/$role\'/", "request()->segment(1) . ''", $content);
        }
        
        // Also handle cases where we used route(...) in some views before?
        // Let's just focus on the url() strings we changed earlier.
        
        if ($content !== $original) {
            file_put_contents($path, $content);
            $count++;
            echo "Updated: $path\n";
        }
    }
}
echo "Total files updated: $count\n";
