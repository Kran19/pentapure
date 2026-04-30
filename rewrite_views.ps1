$roles = @('raw', 'semi', 'finished', 'cashier', 'sales', 'dispatch')
$pages = @(
    @{ name='home'; fn='app.renderHome' },
    @{ name='action'; fn='app.renderAction' },
    @{ name='history'; fn='app.renderHistory' },
    @{ name='profile'; fn='app.renderProfile' }
)

foreach ($role in $roles) {
    foreach ($page in $pages) {
        $pName = $page.name
        $fnName = $page.fn
        $file = "c:\Users\Admin\Desktop\projects\pentapure\resources\views\$role\$pName.blade.php"
        
        $content = @"
@extends('layouts.app')

@section('content')
<script>
  document.addEventListener("DOMContentLoaded", () => {
      app.currentView = '$pName';
      $fnName(document.getElementById('content-area'));
  });
</script>
@endsection
"@
        Set-Content -Path $file -Value $content
    }
}
