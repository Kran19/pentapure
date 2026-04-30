$roles = @('raw', 'semi', 'finished', 'cashier', 'sales', 'dispatch')
$pages = @('home', 'action', 'history', 'profile')

foreach ($role in $roles) {
    foreach ($pName in $pages) {
        $file = "c:\Users\Admin\Desktop\projects\pentapure\resources\views\$role\$pName.blade.php"
        
        $content = @"
@extends('layouts.app')

@section('content')
<!-- JS Router will render content here based on URL -->
@endsection
"@
        Set-Content -Path $file -Value $content
    }
}

$adminPages = @('dashboard', 'users', 'products', 'stock', 'po', 'logs')
foreach ($pName in $adminPages) {
    $file = "c:\Users\Admin\Desktop\projects\pentapure\resources\views\admin\$pName.blade.php"
    $content = @"
@extends('layouts.admin')

@section('content')
<!-- JS Router will render content here based on URL -->
@endsection
"@
    Set-Content -Path $file -Value $content
}
