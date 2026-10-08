@extends(in_array(session('auth_user')['role'] ?? '', ['ADMIN', 'SUB_ADMIN', 'STOCK_MANAGER']) || str_contains(request()->path(), 'sub_admin') || str_contains(request()->path(), 'admin') ? 'layouts.admin' : 'layouts.app')

@section('content')
<style>
.custom-location-dropdown {
    width: 100%;
    position: relative;
}
.custom-location-dropdown button {
    width: 100%;
    text-align: left;
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: #ffffff !important;
    border: 1px solid #d1d5db !important;
    padding: 0.65rem 0.75rem;
    font-size: 0.88rem;
    font-weight: 600;
    color: #111827 !important;
    border-radius: 8px;
    cursor: pointer;
}
.custom-location-dropdown ul.dropdown-menu {
    display: none;
    position: absolute;
    top: 100%;
    left: 0;
    z-index: 1000;
    width: 100%;
    max-height: 260px;
    overflow-y: auto;
    background: #ffffff !important;
    border: 1px solid #d1d5db !important;
    border-radius: 8px;
    list-style: none;
    margin-top: 0.25rem;
    padding: 0.5rem;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15) !important;
}
/* Hide spin arrows on number inputs */
input[type=number].no-spinners::-webkit-outer-spin-button,
input[type=number].no-spinners::-webkit-inner-spin-button {
  -webkit-appearance: none;
  margin: 0;
}
input[type=number].no-spinners {
  -moz-appearance: textfield;
}
</style>

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
      <label>Driver Contact / Mobile No.</label>
      <div style="display:flex; gap:8px;">
        <select id="dispatch-country-code" onchange="app.onCountryCodeChange('dispatch')" style="width:68px; padding:0.7rem 0.2rem; border-radius:8px; border:1px solid var(--border-soft, #DDCFAF); background:var(--input-bg, transparent); color:var(--text-main, #333); font-weight:600; flex-shrink:0; text-align:center; cursor:pointer;">
          <option value="+91" selected>+91</option>
          <option value="+1">+1</option>
          <option value="+44">+44</option>
          <option value="+971">+971</option>
          <option value="+966">+966</option>
          <option value="+61">+61</option>
          <option value="+65">+65</option>
          <option value="+49">+49</option>
          <option value="+33">+33</option>
          <option value="+86">+86</option>
          <option value="+81">+81</option>
          <option value="other">+...</option>
        </select>
        <input type="text" id="dispatch-contact" placeholder="10-digit mobile number" maxlength="10" oninput="app.handleContactInput(this, document.getElementById('dispatch-country-code'))" style="flex:1; padding:0.7rem; border-radius:8px; border:1px solid var(--border-soft, #DDCFAF); background:var(--input-bg, transparent); color:var(--text-main, #333);">
      </div>
    </div>
    <div class="form-group">
      <label>Vehicle Number</label>
      <input type="text" id="dispatch-vehicle-no" placeholder="e.g. GJ-01-AB-1234 (Optional)" style="padding:0.7rem; width:100%; border-radius:8px; border:1px solid var(--border-soft, #DDCFAF); background:var(--input-bg, transparent); color:var(--text-main, #333);">
    </div>

    <div class="form-group" style="margin-top:1.2rem;">
      <label>LR Copy (Optional)</label>
      <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
        <button type="button" class="btn btn-sm btn-media-camera" onclick="document.getElementById('dispatch-lr-cam').click()" title="Take LR photo with camera">
          📷 Camera
        </button>
        <button type="button" class="btn btn-sm btn-media-gallery" onclick="document.getElementById('dispatch-lr-file').click()" title="Choose photo from device">
          📁 Choose Photo
        </button>
        <input type="file" id="dispatch-lr-cam" accept="image/*" capture="environment" style="display:none;" onchange="app.handleDispatchLRPick(event)">
        <input type="file" id="dispatch-lr-file" accept="image/*" style="display:none;" onchange="app.handleDispatchLRPick(event)">

        <div id="dispatch-lr-preview-container" style="display:none; align-items:center; gap:12px; margin-top:8px; width:100%; padding:10px 14px; background:#fffbeb; border-radius:10px; border:1.5px solid #fde68a;">
          <img id="lr-preview" src="" style="width:65px; height:65px; object-fit:cover; border-radius:8px; border:2px solid #f59e0b; cursor:pointer;" onclick="app.viewImage(this.src)" title="View Cropped Image">
          <div style="display:flex; flex-direction:column; gap:6px;">
            <span id="dispatch-lr-filename" style="font-size:0.88rem; font-weight:700; color:#1e293b;"></span>
            <div style="display:flex; gap:8px;">
              <button type="button" class="btn btn-sm" onclick="app.recropDispatchLR()" style="width:auto; padding:5px 10px; font-size:0.78rem; background:#0284c7; color:#fff !important; font-weight:600; border:none; border-radius:6px;">✂️ Re-crop</button>
              <button type="button" class="btn btn-sm btn-danger" onclick="app.removeDispatchLR()" style="width:auto; padding:5px 10px; font-size:0.78rem; background:#dc2626; color:#fff !important; border:none; border-radius:6px;">🗑️ Remove</button>
            </div>
          </div>
        </div>
      </div>
    </div>

  </div>
  
  @if(empty($isReadOnly))
  <button class="btn mt-2" onclick="app.submitDispatch()">Dispatch Items</button>
  @else
  <div style="margin-top:1rem; padding:0.75rem 1.2rem; background:#fffbe8; border:1px solid #fde68a; border-radius:8px; color:#92400e; font-weight:600; text-align:center;">
    🔒 View-Only Mode: Dispatching items is disabled.
  </div>
  @endif

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

  window.addEventListener('pageshow', function(event) {
    if (event.persisted || (window.performance && window.performance.navigation && window.performance.navigation.type === 2)) {
      window.location.reload();
    }
  });

  document.addEventListener('click', function(e) {
    if (!e.target.closest('.custom-location-dropdown')) {
      document.querySelectorAll('.custom-location-dropdown .dropdown-menu').forEach(menu => {
        menu.style.display = 'none';
      });
    }
  });
</script>
@endsection
