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
        
        // 1. Fix route('logout') -> route(request()->segment(1) . '.logout')
        $content = str_replace("route('logout')", "route(request()->segment(1) . '.logout')", $content);
        $content = str_replace('route("logout")', 'route(request()->segment(1) . ".logout")', $content);

        // 2. Fix route('role.xxx') -> route(request()->segment(1) . '.xxx')
        foreach ($roles as $role) {
            $content = preg_replace("/route\(\'$role\./", "route(request()->segment(1) . '.", $content);
            $content = preg_replace("/route\(\"$role\./", "route(request()->segment(1) . \".", $content);
        }
        
        // 3. Fix route('login') -> Not needed if they don't break, but login might be global now.
        // Actually, route('global.login') is what we named the global login. Let's fix route('login') to route('global.login').
        $content = preg_replace("/route\(\'login\'\)/", "route('global.login')", $content);

        if ($content !== $original) {
            file_put_contents($path, $content);
            $count++;
            echo "Fixed route() in: $path\n";
        }
    }
}
echo "Total route files fixed: $count\n";
