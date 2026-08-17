@extends('layouts.app')

@section('content')
<div class="card">
  <div class="card-title">Dispatch Customer Order</div>
  
  <div class="form-group" style="margin-bottom:1.5rem;">
    <label>Select Order</label>
    <select id="dispatch-order" onchange="app.onDispatchOrderSelect(this.value)">
      <option value="" disabled selected>-- Select Order --</option>
      @foreach($pageData['pendingOrders'] as $o)
        <option value="{{ $o['id'] }}">#{{ strtoupper((string)$o['id']) }} - {{ $o['company']['name'] }}</option>
      @endforeach
    </select>
  </div>
  
  <div id="order-preview" style="display:none; background:rgba(0,0,0,0.1); padding:1rem; border-radius:8px; margin-bottom:1.5rem; font-size:0.9rem;"></div>
  
  <div id="dispatch-details" style="display:none;">
    <div class="form-group">
      <label>Override Transporter (Optional)</label>
      <select id="dispatch-transporter">
        <option value="">-- Keep Order Transporter --</option>
        @foreach(\App\Models\Transporter::all() as $t)
          <option value="{{ $t->id }}">{{ $t->name }}</option>
        @endforeach
      </select>
    </div>
    <div class="form-group">
      <label>Driver Mobile No.</label>
      <input type="text" id="dispatch-driver" placeholder="Optional" style="padding:0.7rem; width:100%; border-radius:8px; border:1px solid rgba(255,255,255,0.1); background:#161b22; color:#fff;">
    </div>
    <div class="form-group">
      <label>LR Number</label>
      <input type="text" id="dispatch-lr-no" placeholder="Optional" style="padding:0.7rem; width:100%; border-radius:8px; border:1px solid rgba(255,255,255,0.1); background:#161b22; color:#fff;">
    </div>
    
    <div class="form-group mt-1">
      <label>Upload Lorry Receipt (LR) Copy <span style="font-weight:normal; color:var(--text-muted); font-size:0.75rem;">(Optional - Upload Later Allowed)</span></label>
      <div class="image-upload-wrapper" onclick="document.getElementById('dispatch-lr').click()" style="border:2px dashed rgba(255,255,255,0.1); border-radius:12px; padding:2rem; text-align:center; cursor:pointer; background:rgba(255,255,255,0.02); transition:0.2s;">
        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color:var(--text-muted); margin-bottom:10px;"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
        <div style="font-size:0.9rem; color:var(--text-muted);">Click to upload LR Image</div>
        <input type="file" id="dispatch-lr" accept="image/*" style="display:none;" onchange="app.previewLR(event)">
        <img id="lr-preview" class="image-preview" style="display:none; max-height:200px; margin:10px auto 0 auto; border-radius:8px; object-fit:contain;">
      </div>
    </div>
  </div>
  
  <button class="btn mt-2" onclick="app.submitDispatch()">Dispatch Items</button>
<a href="{{ url(request()->segment(1) . '/po') }}" class="btn mt-2" style="margin-left:0.5rem;">Purchase Requests</a>
</div>

<script>
  document.addEventListener('DOMContentLoaded', () => {
    window.currentPendingOrders = @json($pageData['pendingOrders']);
    
    // Check for auto-select from localStorage (redirected from home page click)
    const autoId = localStorage.getItem('auto_dispatch_id');
    if (autoId) {
      localStorage.removeItem('auto_dispatch_id');
      const select = document.getElementById('dispatch-order');
      if (select) {
        select.value = autoId;
        app.onDispatchOrderSelect(autoId);
      }
    }
  });
</script>
@endsection
