@extends('layouts.admin')

@section('content')
<div style="padding: 1.5rem;">
  <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem; flex-wrap:wrap; gap:1rem;">
    <h2 style="margin:0;">🏷️ Expense Category Master</h2>
    <button class="btn" onclick="openCategoryForm()" style="width:auto; padding:0.6rem 1.2rem;">+ Add New Category</button>
  </div>

  <!-- Add/Edit Form Card (Open by default) -->
  <div id="category-form-card" class="card" style="display:block; margin-bottom:1.5rem; padding:1.2rem;">
    <div class="card-title" id="form-card-title">Add New Category</div>
    <form id="category-form">
      <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:1rem; margin-top:1rem;">
        <div class="form-group">
          <label>Category Name *</label>
          <input type="text" id="category-name" placeholder="e.g. Packaging, Office Supplies..." required>
        </div>
      </div>
      <div style="display:flex; gap:1rem; margin-top:1.5rem;">
        <button type="submit" class="btn" style="width:auto; padding:0.6rem 1.5rem;">Save Category</button>
        <button type="button" class="btn btn-secondary" onclick="closeCategoryForm()" style="width:auto; padding:0.6rem 1.5rem;">Cancel</button>
      </div>
    </form>
  </div>

  <div class="card" style="padding: 1.2rem;">
    <div class="table-container">
      <table>
        <thead>
          <tr>
            <th>ID</th>
            <th>Category Name</th>
            <th>Status</th>
            <th>Created At</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          @foreach($pageData['categories'] as $c)
          <tr>
            <td>{{ $c->id }}</td>
            <td style="font-weight:600; color:var(--primary-light);">{{ $c->name }}</td>
            <td>
              <label class="switch">
                <input type="checkbox" {{ $c->is_active ? 'checked' : '' }} onchange="adminToggleCategory({{ $c->id }})">
                <span class="slider"></span>
              </label>
            </td>
            <td>{{ date('d M Y', strtotime($c->created_at)) }}</td>
            <td>
              <div class="action-btns">
                <button class="btn-icon edit" onclick="adminEditCategory({{ json_encode($c) }})" title="Edit">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4L18.5 2.5z"></path></svg>
                </button>
                <button class="btn-icon delete" onclick="adminDeleteCategory({{ $c->id }})" title="Delete">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg>
                </button>
              </div>
            </td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>

    <div style="margin-top:1.5rem; display:flex; justify-content:center;">
      {{ $pageData['categories']->links() }}
    </div>
  </div>
</div>

<!-- Modal removed, now using inline form -->

<script>
  let editingCategoryId = null;

  function openCategoryForm() {
    editingCategoryId = null;
    document.getElementById('form-card-title').innerText = 'Add New Category';
    document.getElementById('category-name').value = '';
    document.getElementById('category-form-card').style.display = 'block';
  }

  function adminEditCategory(c) {
    editingCategoryId = c.id;
    document.getElementById('form-card-title').innerText = 'Edit Category';
    document.getElementById('category-name').value = c.name;
    document.getElementById('category-form-card').style.display = 'block';
    document.getElementById('category-form-card').scrollIntoView({ behavior: 'smooth' });
  }

  function closeCategoryForm() {
    document.getElementById('category-form-card').style.display = 'none';
  }

  document.getElementById('category-form').onsubmit = function(e) {
    e.preventDefault();

    const name = document.getElementById('category-name').value.trim();
    if(!name) return;

    fetch('/admin/categories', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
      body: JSON.stringify({ category_id: editingCategoryId, name })
    }).then(r => r.json()).then(d => {
      if(d.success) {
        Swal.fire('Success', d.message, 'success');
        setTimeout(() => location.reload(), 800);
      } else {
        Swal.fire('Error', d.message || 'Error', 'error');
      }
    });
  };

  function adminToggleCategory(id) {
    fetch('/admin/categories/toggle', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
      body: JSON.stringify({ category_id: id })
    }).then(r => r.json()).then(d => {
      if(d.success) app.toast(d.message);
      else app.toast(d.message || 'Error', 'error');
    });
  }

  function adminDeleteCategory(id) {
    Swal.fire({
      title: 'Are you sure?',
      text: "This category will be deleted from master (existing transactions keep the stored string).",
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#d33',
      confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
      if (result.isConfirmed) {
        fetch(`/admin/categories/${id}`, {
          method: 'DELETE',
          headers: { 'X-CSRF-TOKEN': csrfToken }
        }).then(r => r.json()).then(d => {
          if(d.success) {
            Swal.fire('Deleted!', d.message, 'success');
            setTimeout(() => location.reload(), 800);
          } else {
            Swal.fire('Error!', d.message || 'Error', 'error');
          }
        });
      }
    });
  }
</script>
@endsection

