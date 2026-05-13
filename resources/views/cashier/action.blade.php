@extends('layouts.app')

@section('content')
<!-- Cashier Action (Blade hands categories to JS) -->
<script>
  window.serverPageData = window.serverPageData || {};
  window.serverPageData.categories = @json($pageData['categories'] ?? []);
</script>
@endsection

