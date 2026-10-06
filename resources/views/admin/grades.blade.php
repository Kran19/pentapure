@extends('layouts.admin')

@section('content')
<div style="padding: 1.5rem;">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem; flex-wrap:wrap; gap:1rem;">
        <h2 style="margin:0;">✅ Grades Master</h2>
        @if(empty($isReadOnly))
        <button class="btn" onclick="openGradeForm()" style="width:auto; padding:0.6rem 1.2rem;">+ Add New Grade</button>
        @endif
    </div>

    <!-- Add/Edit Form Card -->
    @if(empty($isReadOnly))
    <div id="grade-form-card" class="card white-orange-card" style="display:none; margin-bottom:1.5rem; padding:1.2rem;">
        <div class="card-title" id="form-card-title">Add New Grade</div>
        <form id="grade-form">
            <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:1rem; margin-top:1rem;">
                <div class="form-group">
                    <label>Grade Name (e.g., PREMIUM+, SUPER GOLD)</label>
                    <input type="text" id="grade-name" placeholder="Enter grade name" required>
                </div>
            </div>
            <div style="display:flex; gap:1rem; margin-top:1.5rem;">
                <button type="submit" class="btn" style="width:auto; padding:0.6rem 1.5rem;">Save Grade</button>
                <button type="button" class="btn btn-secondary" onclick="closeGradeForm()" style="width:auto; padding:0.6rem 1.5rem;">Cancel</button>
            </div>
        </form>
    </div>
    @endif

    <div class="card">
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Grade Name</th>
                        <th>Status</th>
                        <th>Created At</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($pageData['grades'] as $g)
                    @php
                        $isFixed = in_array(strtoupper(trim($g->name)), ['NONE', 'N/A', 'NA', 'N / A'], true);
                        $hasProducts = ($g->products_count ?? 0) > 0;
                    @endphp
                    <tr>
                        <td>{{ ($pageData['grades']->currentPage() - 1) * $pageData['grades']->perPage() + $loop->iteration }}</td>
                        <td style="font-weight:600; color:var(--primary-light);">
                            {{ $g->name }}
                            @if($isFixed)
                                <span style="font-size:0.7rem; background:#374151; color:#9ca3af; padding:2px 6px; border-radius:4px; margin-left:6px; font-weight:500;">System Fixed</span>
                            @elseif($hasProducts)
                                <span style="font-size:0.7rem; background:rgba(59, 130, 246, 0.15); color:#60a5fa; border:1px solid rgba(59, 130, 246, 0.3); padding:2px 6px; border-radius:4px; margin-left:6px; font-weight:500;" title="Used in {{ $g->products_count }} {{ \Illuminate\Support\Str::plural('product', $g->products_count) }}">
                                    {{ $g->products_count }} {{ \Illuminate\Support\Str::plural('product', $g->products_count) }}
                                </span>
                            @endif
                        </td>
                        <td>
                            <label class="switch">
                                <input type="checkbox" {{ $g->is_active ? 'checked' : '' }} {{ !empty($isReadOnly) ? 'disabled style=cursor:not-allowed;' : '' }} onchange="adminToggleGrade({{ $g->id }})">
                                <span class="slider"></span>
                            </label>
                        </td>
                        <td>{{ date('d-m-Y', strtotime($g->created_at)) }}</td>
                        <td>
                            @if(!empty($isReadOnly))
                                <span style="font-size:0.75rem; color:var(--text-muted); font-style:italic;">View Only</span>
                            @elseif($isFixed)
                                <span style="font-size:0.75rem; color:var(--text-muted); font-style:italic;">Protected</span>
                            @else
                                <div class="action-btns">
                                    <button class="btn-icon edit" onclick="adminEditGrade({{ json_encode($g) }})" title="Edit">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4L18.5 2.5z"></path></svg>
                                    </button>
                                    @if($hasProducts)
                                        <button class="btn-icon delete is-disabled" disabled style="opacity:0.35; cursor:not-allowed;" title="Cannot delete: Assigned to {{ $g->products_count }} {{ \Illuminate\Support\Str::plural('product', $g->products_count) }}">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg>
                                        </button>
                                    @else
                                        <button class="btn-icon delete" onclick="adminDeleteGrade({{ $g->id }})" title="Delete">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg>
                                        </button>
                                    @endif
                                </div>
                            @endif
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

<!-- Modal removed, now using inline form -->

<script>
let editingGradeId = null;

function openGradeForm() {
    if (window.isReadOnly) { Swal.fire('Notice', 'You have view-only access.', 'info'); return; }
    editingGradeId = null;
    document.getElementById('form-card-title').innerText = 'Add New Grade';
    document.getElementById('grade-name').value = '';
    const formCard = document.getElementById('grade-form-card');
    if (formCard) {
        formCard.style.display = 'block';
        formCard.scrollIntoView({ behavior: 'smooth' });
    }
}

function adminEditGrade(g) {
    if (window.isReadOnly) { Swal.fire('Notice', 'You have view-only access.', 'info'); return; }
    Swal.fire({
        title: 'Edit Grade',
        html: `
            <div style="display:flex; flex-direction:column; gap:10px; text-align:left;">
                <label style="font-weight:600; color:#4b5563;">Grade Name</label>
                <input type="text" id="edit-grade-name" class="swal2-input" style="margin:0;" value="${g.name}">
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: 'Save Changes',
        confirmButtonColor: '#f59e0b',
        preConfirm: () => {
            const name = document.getElementById('edit-grade-name').value.trim();
            if (!name) Swal.showValidationMessage('Grade name is required');
            return name;
        }
    }).then((res) => {
        if (res.isConfirmed) {
            fetch(window.baseUrl + '/' + window.userSlug + '/grades', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': window.csrfToken },
                body: JSON.stringify({ grade_id: g.id, name: res.value })
            }).then(r => r.json()).then(d => {
                if(d.success) {
                    Swal.fire('Success', d.message, 'success');
                    setTimeout(() => location.reload(), 800);
                } else {
                    Swal.fire('Error', d.message || 'Error', 'error');
                }
            });
        }
    });
}

function closeGradeForm() {
    const formCard = document.getElementById('grade-form-card');
    if (formCard) formCard.style.display = 'none';
}

const gradeFormEl = document.getElementById('grade-form');
if (gradeFormEl) {
    gradeFormEl.onsubmit = function(e) {
        e.preventDefault();
        if (window.isReadOnly) { Swal.fire('Notice', 'You have view-only access.', 'info'); return; }
        const name = document.getElementById('grade-name').value;
        if(!name) return;

        fetch(window.baseUrl + '/' + window.userSlug + '/grades', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': window.csrfToken },
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
}

function adminToggleGrade(id) {
    if (window.isReadOnly) { Swal.fire('Notice', 'You have view-only access.', 'info'); return; }
    fetch(window.baseUrl + '/' + window.userSlug + '/grades', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': window.csrfToken },
        body: JSON.stringify({ grade_id: id, toggle: true })
    }).then(r => r.json()).then(d => {
        if(d.success) app.toast('Status updated');
        else app.toast('Error', 'error');
    });
}

function adminDeleteGrade(id) {
    if (window.isReadOnly) { Swal.fire('Notice', 'You have view-only access.', 'info'); return; }
    Swal.fire({
        title: 'Are you sure?',
        text: "Are you sure you want to delete this grade?",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch(window.baseUrl + '/' + window.userSlug + '/grades/' + id, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': window.csrfToken }
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

<style>
.btn-icon:disabled,
.btn-icon.is-disabled {
    opacity: 0.35 !important;
    cursor: not-allowed !important;
    background: rgba(255, 255, 255, 0.03) !important;
    border-color: rgba(255, 255, 255, 0.08) !important;
    color: #6b7280 !important;
    pointer-events: auto !important;
}
.btn-icon:disabled:hover,
.btn-icon.is-disabled:hover {
    background: rgba(255, 255, 255, 0.03) !important;
    border-color: rgba(255, 255, 255, 0.08) !important;
    color: #6b7280 !important;
}

/* White and Orange Theme for Forms */
.white-orange-card {
    background-color: #ffffff !important;
    border: 1px solid #e5e7eb !important;
    border-radius: 12px !important;
    box-shadow: 0 4px 6px rgba(0,0,0,0.05) !important;
}
.white-orange-card .card-title,
.white-orange-card h4 {
    color: #333333 !important;
    font-weight: 700 !important;
}
.white-orange-card label {
    color: #4b5563 !important;
    font-weight: 600 !important;
}
.white-orange-card input,
.white-orange-card select,
.white-orange-card textarea {
    background-color: #f9fafb !important;
    border: 1px solid #d1d5db !important;
    color: #333333 !important;
    -webkit-text-fill-color: #333333 !important;
}
.white-orange-card input::placeholder,
.white-orange-card textarea::placeholder {
    color: #9ca3af !important;
    -webkit-text-fill-color: #9ca3af !important;
}
.white-orange-card .btn-primary,
.white-orange-card button[type="submit"] {
    background-color: #f59e0b !important;
    color: #ffffff !important;
    -webkit-text-fill-color: #ffffff !important;
    border: none !important;
}
.white-orange-card .btn-secondary,
.white-orange-card button[type="button"] {
    background-color: #e5e7eb !important;
    color: #374151 !important;
    -webkit-text-fill-color: #374151 !important;
    border: none !important;
}
.white-orange-card span {
    color: #333333 !important;
}
</style>
