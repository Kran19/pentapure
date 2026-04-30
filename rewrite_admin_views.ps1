$pages = @(
    @{ name='dashboard'; fn='app.renderAdminDashboard' },
    @{ name='users'; fn='app.renderAdminUsers' },
    @{ name='products'; fn='app.renderAdminProducts' },
    @{ name='stock'; fn='app.renderAdminStock' },
    @{ name='po'; fn='app.renderAdminPurchaseOrders' },
    @{ name='logs'; fn='app.renderAdminLogs' }
)

foreach ($page in $pages) {
    $pName = $page.name
    $fnName = $page.fn
    $file = "c:\Users\Admin\Desktop\projects\pentapure\resources\views\admin\$pName.blade.php"
    
    $content = @"
@extends('layouts.admin')

@section('content')
<script>
  document.addEventListener("DOMContentLoaded", () => {
      app.currentView = '$pName';
      $fnName(document.querySelector('.admin-sidebar .nav-item.active'));
  });
</script>
@endsection
"@
    Set-Content -Path $file -Value $content
}
