@extends('layouts.app')

@section('content')
<div class="flex-between mb-1" style="flex-wrap:wrap; gap:10px; align-items:center;">
  <h2 style="margin:0;">🛒 Purchase Orders</h2>
  <button class="btn btn-sm btn-primary" onclick="document.getElementById('po-modal').classList.add('active')">+ New PO</button>
</div>

<div class="table-container">
  <table>
    <thead>
      <tr>
        <th>Material</th>
        <th>Quantity</th>
        <th>Status</th>
        <th>Date</th>
      </tr>
    </thead>
    <tbody>
      @forelse($pageData['purchaseOrders'] as $po)
        <tr>
          <td style="font-weight:600;">{{ $po->product?->name }}</td>
          <td>{{ $po->quantity }} kg</td>
          <td><span class="badge {{ $po->status === 'DONE' ? 'badge-done' : 'badge-pending' }}">{{ $po->status }}</span></td>
          <td style="font-size:0.8rem;">{{ \Carbon\Carbon::parse($po->created_at)->format('d M Y') }}</td>
        </tr>
      @empty
        <tr><td colspan="4" class="text-center text-muted">No purchase orders found.</td></tr>
      @endforelse
    </tbody>
  </table>
</div>

<div style="margin-top:1.5rem; display:flex; justify-content:center;">
  {{ $pageData['purchaseOrders']->links() }}
</div>

<!-- Add PO Modal -->
<div id="po-modal" class="modal-overlay" onclick="if(event.target==this) this.classList.remove('active')">
  <div class="modal-content card" style="max-width:400px; width:100%;">
    <div class="card-title">Request Raw Material</div>
    <form action="{{ url(request()->segment(1) . '/po') }}" method="POST" id="po-form" onsubmit="disableBtn(this)">
      @csrf
      <div class="form-group">
        <label>Select Material</label>
        <select name="product_id" required>
            @foreach(\App\Models\Product::raw()->active()->get() as $rm)
                <option value="{{ $rm->id }}">{{ $rm->name }}</option>
            @endforeach
        </select>
      </div>
      <div class="form-group">
        <label>Quantity (kg)</label>
        <input type="number" name="quantity" step="0.01" min="0.1" required>
      </div>
      <div class="form-group">
        <label>Note (Optional)</label>
        <textarea name="note" rows="2"></textarea>
      </div>
      <div style="display:flex; gap:10px; margin-top:1.5rem;">
        <button type="submit" class="btn btn-primary" style="flex:1;">Submit Request</button>
        <button type="button" class="btn btn-secondary" style="flex:1;" onclick="document.getElementById('po-modal').classList.remove('active')">Cancel</button>
      </div>
    </form>
  </div>
</div>

<script>
function disableBtn(form) {
    const btn = form.querySelector('button[type="submit"]');
    btn.disabled = true;
    btn.style.opacity = '0.7';
}
</script>
@endsection
