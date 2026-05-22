@extends('layouts.app')

@section('content')
{{-- Inject full pageData (transactions + bills + summary) so JS can render the ledger --}}
<script>
  window.serverPageData = @json($pageData);
</script>
<div id="cashier-ledger-container">
  <!-- Rendered by app.js renderCashierLedger() -->
</div>
<script>
  document.addEventListener('DOMContentLoaded', () => {
    const container = document.getElementById('cashier-ledger-container');
    if (container && typeof app !== 'undefined') {
      app.renderCashierLedger(container);
    }
  });
</script>
@endsection
