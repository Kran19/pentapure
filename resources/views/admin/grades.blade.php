@extends('layouts.admin')

@section('content')
<div style="padding: 1.5rem;">
    <div class="flex-between mb-1">
        <h2 style="margin:0;">✅ Grades Master</h2>
        <button class="btn btn-sm" style="width:auto; padding:0.4rem 1rem;" onclick="openGradeModal()">+ Add New Grade</button>
    </div>

    <div class="card">
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Grade Name</th>
                        <th>Status</th>
                        <th>Created At</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($pageData['grades'] as $g)
                    <tr>
                        <td>{{ $g->id }}</td>
                        <td style="font-weight:600; color:var(--primary-light);">{{ $g->name }}</td>
                        <td>
                            <label class="switch">
                                <input type="checkbox" {{ $g->is_active ? 'checked' : '' }} onchange="adminToggleGrade({{ $g->id }})">
                                <span class="slider"></span>
                            </label>
                        </td>
                        <td>{{ date('d M Y', strtotime($g->created_at)) }}</td>
                        <td>
                            <div class="action-btns">
                                <button class="btn-icon edit" onclick="adminEditGrade({{ json_encode($g) }})" title="Edit">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4L18.5 2.5z"></path></svg>
                                </button>
                                <button class="btn-icon delete" onclick="adminDeleteGrade({{ $g->id }})" title="Delete">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        
        <!-- Pagination Links -->
        <div style="margin:1rem; display:flex; justify-content:center;">
            {{ $pageData['grades']->links() }}
        </div>
    </div>
</div>

<!-- Add Grade Modal -->
<div class="modal-overlay" id="grade-modal">
    <div class="modal-content">
        <h3 class="mb-1" id="modal-title">Add New Grade</h3>
        <form id="grade-form">
            <div class="form-group">
                <label>Grade Name (e.g., PREMIUM+, SUPER GOLD)</label>
                <input type="text" id="grade-name" placeholder="Enter grade name" required>
            </div>
            <div style="display:flex; gap:10px; margin-top:1rem;">
                <button type="button" class="btn btn-secondary" onclick="closeGradeModal()">Cancel</button>
                <button type="submit" class="btn">Save Grade</button>
            </div>
        </form>
    </div>
</div>

<script>
let editingGradeId = null;

function openGradeModal() {
    editingGradeId = null;
    document.getElementById('modal-title').innerText = 'Add New Grade';
    document.getElementById('grade-name').value = '';
    document.getElementById('grade-modal').classList.add('active');
}

function adminEditGrade(g) {
    editingGradeId = g.id;
    document.getElementById('modal-title').innerText = 'Edit Grade';
    document.getElementById('grade-name').value = g.name;
    document.getElementById('grade-modal').classList.add('active');
}

function closeGradeModal() {
    document.getElementById('grade-modal').classList.remove('active');
}

document.getElementById('grade-form').onsubmit = function(e) {
    e.preventDefault();
    const name = document.getElementById('grade-name').value;
    if(!name) return;

    fetch('/admin/grades', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
        body: JSON.stringify({ grade_id: editingGradeId, name })
    }).then(r => r.json()).then(d => {
        if(d.success) {
            Swal.fire('Success', d.message, 'success');
            setTimeout(() => location.reload(), 800);
        } else {
            Swal.fire('Error', d.message || 'Error', 'error');
        }
    });
};

function adminToggleGrade(id) {
    fetch('/admin/grades', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
        body: JSON.stringify({ grade_id: id, toggle: true })
    }).then(r => r.json()).then(d => {
        if(d.success) app.toast('Status updated');
        else app.toast('Error', 'error');
    });
}

function adminDeleteGrade(id) {
    Swal.fire({
        title: 'Are you sure?',
        text: "This grade will be removed from all products!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch(`/admin/grades/${id}`, {
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
