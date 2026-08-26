@extends($layout)

@section('content')
<!-- Include Flatpickr CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

<div style="padding:1.5rem;">
  <!-- Top Section -->
  <div class="flex-between mb-1" style="flex-wrap:wrap; gap:10px;">
    <h2 style="margin:0;">📝 Daily Punch</h2>
    
    <div style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
      <form method="GET" action="{{ url()->current() }}" style="display:flex; gap:10px; align-items:center;">
        <label style="font-weight:bold;">Date:</label>
        <input type="date" name="date" value="{{ $date }}" onchange="this.form.submit()" style="padding:0.4rem; border-radius:4px; border:1px solid #ccc;">
      </form>

      <input type="text" id="workerSearch" placeholder="Search worker..." onkeyup="filterWorkers()" style="padding:0.4rem; border-radius:4px; border:1px solid #ccc; width:200px;">
      
      <select id="deptFilter" onchange="filterWorkers()" style="padding:0.4rem; border-radius:4px; border:1px solid #ccc; width:200px;">
        <option value="">All Departments</option>
        @foreach($departments as $dept)
          <option value="{{ strtolower($dept->name) }}">{{ $dept->name }}</option>
        @endforeach
      </select>

      @if($authUser['role'] !== 'ADMIN')
        <button class="btn btn-secondary" onclick="markAllPresent()" style="width:auto; padding:0.4rem 1rem;">Mark All</button>
        <button class="btn" onclick="saveAllAttendance()" style="width:auto; padding:0.4rem 1.5rem; background:#2ecc71;">Save All</button>
      @endif
    </div>
  </div>

  <form id="bulk-attendance-form">
    @php
      $groupedWorkers = $workers->groupBy(function($w) {
          return $w->department ? $w->department->name : 'General';
      });
      $globalIndex = 0;
    @endphp

    @foreach($groupedWorkers as $deptName => $deptWorkers)
    <div class="department-group" data-group-dept="{{ strtolower($deptName) }}" style="margin-top:1.5rem;">
      <h3 style="margin-bottom:1rem; border-bottom:2px solid #eee; padding-bottom:0.5rem; color:#555;">🏢 {{ $deptName }}</h3>
      
      <!-- Employee Cards Grid -->
      <div class="workersGrid" style="display:grid; grid-template-columns:repeat(auto-fill, minmax(300px, 1fr)); gap:1rem;">
        @foreach($deptWorkers as $w)
          @php
            $index = $globalIndex++;
            $att = $w->attendances->first();
            $status = $att ? $att->status : 'ABSENT';
            $shift = $att ? ($att->shift_type ?? $w->shift_type) : $w->shift_type;
            $in = $att && $att->in_time ? \Carbon\Carbon::parse($att->in_time)->format('H:i') : '';
            $out = $att && $att->out_time ? \Carbon\Carbon::parse($att->out_time)->format('H:i') : '';
            $bin = $att && $att->break_in ? \Carbon\Carbon::parse($att->break_in)->format('H:i') : '';
            $bout = $att && $att->break_out ? \Carbon\Carbon::parse($att->break_out)->format('H:i') : '';
            
            $otUt = $att ? ($att->ot_ut ?? 'NONE') : 'NONE';
            $otUtHours = $att ? ($att->ot_ut_hours ?? 0) : 0;
            $advance = $att ? ($att->advance ?? 0) : 0;
            $isFinished = $att ? ($att->is_finished ? true : false) : false;
            
            $isAdmin = ($authUser['role'] === 'ADMIN');
          @endphp
        
        <div class="card worker-card" style="padding:1rem; border-left:4px solid {{ $status === 'ABSENT' ? '#e74c3c' : '#2ecc71' }};" data-name="{{ strtolower($w->name) }}" data-dept="{{ strtolower($w->department->name ?? '') }}">
          <input type="hidden" name="attendances[{{$index}}][worker_id]" value="{{ $w->id }}">
          
          <!-- Header -->
          <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:1rem; border-bottom:1px solid #eee; padding-bottom:0.5rem;">
            <div>
              <div style="font-weight:bold; font-size:1.1rem;">{{ $w->name }}</div>
              <div style="font-size:0.85rem; color:var(--text-muted);">{{ $w->department->name ?? 'N/A' }}</div>
            </div>
            <div style="text-align:right;">
              <div class="status-badge" style="background: {{ $status === 'ABSENT' ? '#e74c3c' : '#2ecc71' }}; color:white; padding:4px 8px; border-radius:4px; font-size:0.75rem; font-weight:bold; display:inline-block; margin-bottom:4px;">
                {{ $status }}
              </div>
              <div class="shift-badge" style="font-size:0.75rem; color:var(--text-muted);">
                @if($shift === 'DAY') ☀ Day 9-6 @elseif($shift === 'NIGHT') 🌙 Night 9-6 @else ⚙ Custom @endif
              </div>
              <div style="margin-top:4px; font-size:0.8rem;">
              </div>
            </div>
          </div>

          <!-- Row 1: Status | Shift -->
          <div style="display:flex; gap:10px; margin-bottom:0.75rem;">
            <div style="flex:1;">
              <label style="font-size:0.8rem; color:var(--text-muted); display:block;">Status</label>
              <select name="attendances[{{$index}}][status]" class="status-select" onchange="handleStatusChange(this)" {{ $isAdmin ? 'disabled' : '' }} style="width:100%; padding:0.4rem; border:1px solid #ccc; border-radius:4px;">
                  <option value="PRESENT" {{ $status=='PRESENT'?'selected':'' }}>PRESENT (1)</option>
                  <option value="ABSENT" {{ $status=='ABSENT'?'selected':'' }}>ABSENT (0)</option>
                  <option value="HALF" {{ $status=='HALF'?'selected':'' }}>HALF (0.5)</option>
                  <option value="PRESENT + HALF" {{ $status=='PRESENT + HALF'?'selected':'' }}>PRESENT + HALF (1.5)</option>
                  <option value="DOUBLE" {{ $status=='DOUBLE'?'selected':'' }}>DOUBLE (2)</option>
                  <option value="SUNDAY" {{ $status=='SUNDAY'?'selected':'' }}>SUNDAY (1)</option>
                  <option value="HALF (OFF)" {{ $status=='HALF (OFF)'?'selected':'' }}>HALF (OFF) (1.5)</option>
                  <option value="PRESENT (OFF)" {{ $status=='PRESENT (OFF)'?'selected':'' }}>PRESENT (OFF) (2)</option>
                  <option value="PR. + HALF (OFF)" {{ $status=='PR. + HALF (OFF)'?'selected':'' }}>PR. + HALF (OFF) (2.5)</option>
                  <option value="DOUBLE (OFF)" {{ $status=='DOUBLE (OFF)'?'selected':'' }}>DOUBLE (OFF) (3)</option>
                  <option value="HOLIDAY" {{ $status=='HOLIDAY'?'selected':'' }}>HOLIDAY (1)</option>
                  <option value="PAID LEAVE" {{ $status=='PAID LEAVE'?'selected':'' }}>PAID LEAVE (1)</option>
              </select>
            </div>
            <div style="flex:1; display:{{ $status === 'ABSENT' ? 'none' : 'block' }};" class="extra-field-block">
              <label style="font-size:0.8rem; color:var(--text-muted); display:block;">Shift</label>
              <select name="attendances[{{$index}}][shift_type]" class="shift-select" onchange="handleShiftChange(this)" {{ $isAdmin ? 'disabled' : '' }} style="width:100%; padding:0.4rem; border:1px solid #ccc; border-radius:4px;">
                  <option value="DAY" {{ $shift=='DAY'?'selected':'' }}>Day Shift</option>
                  <option value="NIGHT" {{ $shift=='NIGHT'?'selected':'' }}>Night Shift</option>
                  <option value="CUSTOM" {{ $shift=='CUSTOM'?'selected':'' }}>Custom</option>
              </select>
            </div>
          </div>

          <!-- Row 2: In Time | Out Time -->
          <div class="extra-field-flex" style="display:{{ $status === 'ABSENT' ? 'none' : 'flex' }}; gap:10px; margin-bottom:0.75rem;">
            <div style="flex:1;">
              <label class="label-in" style="font-size:0.8rem; color:var(--text-muted); display:block;">In Time</label>
              <input type="text" name="attendances[{{$index}}][in_time]" value="{{ $in }}" class="time-picker time-in" {{ $isAdmin ? 'disabled' : '' }} style="width:100%; padding:0.4rem; border:1px solid #ccc; border-radius:4px;" placeholder="Select Time">
            </div>
            <div style="flex:1;">
              <label class="label-out" style="font-size:0.8rem; color:var(--text-muted); display:block;">Out Time</label>
              <input type="text" name="attendances[{{$index}}][out_time]" value="{{ $out }}" class="time-picker time-out" {{ $isAdmin ? 'disabled' : '' }} style="width:100%; padding:0.4rem; border:1px solid #ccc; border-radius:4px;" placeholder="Select Time">
            </div>
          </div>

          <!-- Row 3: Break / Night Shift -->
          <div class="row-3 extra-field-flex" style="display:{{ ($status !== 'ABSENT' && $shift === 'CUSTOM') ? 'flex' : 'none' }}; gap:10px; margin-bottom:0.75rem;">
            <div style="flex:1;">
              <label class="label-bin" style="font-size:0.8rem; color:var(--text-muted); display:block;">Night In Time</label>
              <input type="text" name="attendances[{{$index}}][break_in]" value="{{ $bin }}" class="time-picker time-bin" {{ $isAdmin ? 'disabled' : '' }} style="width:100%; padding:0.4rem; border:1px solid #ccc; border-radius:4px;" placeholder="Select Time">
            </div>
            <div style="flex:1;">
              <label class="label-bout" style="font-size:0.8rem; color:var(--text-muted); display:block;">Night Out Time</label>
              <input type="text" name="attendances[{{$index}}][break_out]" value="{{ $bout }}" class="time-picker time-bout" {{ $isAdmin ? 'disabled' : '' }} style="width:100%; padding:0.4rem; border:1px solid #ccc; border-radius:4px;" placeholder="Select Time">
            </div>
          </div>

          <!-- Row 4: OT/UT | OT/UT Hours -->
          <div class="extra-field-flex" style="display:{{ $status === 'ABSENT' ? 'none' : 'flex' }}; gap:10px; margin-bottom:0.75rem;">
            <div style="flex:1;">
              <label style="font-size:0.8rem; color:var(--text-muted); display:block;">OT / UT</label>
              <select name="attendances[{{$index}}][ot_ut]" class="ot-select" onchange="handleOTUTChange(this)" {{ $isAdmin ? 'disabled' : '' }} style="width:100%; padding:0.4rem; border:1px solid #ccc; border-radius:4px;">
                  <option value="NONE" {{ $otUt=='NONE'?'selected':'' }}>None</option>
                  <option value="OT" {{ $otUt=='OT'?'selected':'' }}>OT - Overtime</option>
                  <option value="UT" {{ $otUt=='UT'?'selected':'' }}>UT - Undertime</option>
              </select>
            </div>
            <div style="flex:1;" class="ot-hours-container" style="display: {{ $otUt !== 'NONE' ? 'block' : 'none' }};">
              <label class="ot-label" style="font-size:0.8rem; color:var(--text-muted); display:block;">{{ $otUt === 'UT' ? 'UT Hours' : 'OT Hours' }}</label>
              <div style="position:relative; width:100%;">
                <span class="ot-sign" style="position:absolute; left:8px; top:50%; transform:translateY(-50%); font-weight:bold; color:{{ $otUt === 'UT' ? '#e74c3c' : '#2ecc71' }}; pointer-events:none; font-size:1.1rem;">{{ $otUt === 'UT' ? '-' : '+' }}</span>
                <input type="number" name="attendances[{{$index}}][ot_ut_hours]" value="{{ $otUtHours }}" step="0.5" min="0" class="ot-hours" {{ $isAdmin ? 'disabled' : '' }} style="width:100%; padding:0.4rem; padding-left:22px; border:1px solid #ccc; border-radius:4px;">
              </div>
            </div>
          </div>

          <!-- Row 5: Advance & Num Workers -->
          <div class="extra-field-flex" style="display:{{ $status === 'ABSENT' ? 'none' : 'flex' }}; gap:10px; margin-bottom:0.5rem;">
            <div style="flex:1;">
              <label style="font-size:0.8rem; color:var(--text-muted); display:block;">Advance (₹)</label>
              <input type="number" name="attendances[{{$index}}][advance]" value="{{ $advance }}" min="0" step="1" class="advance-input" {{ $isAdmin ? 'disabled' : '' }} style="width:100%; padding:0.4rem; border:1px solid #ccc; border-radius:4px;">
            </div>
            @if($w->salary_type === 'LABOUR_MUKADAM')
            <div style="flex:1;">
              <label style="font-size:0.8rem; color:var(--text-muted); display:block;">No. of Workers</label>
              <input type="number" name="attendances[{{$index}}][num_workers]" value="{{ $att->num_workers ?? '' }}" min="0" step="1" class="num-workers-input" {{ $isAdmin ? 'disabled' : '' }} style="width:100%; padding:0.4rem; border:1px solid #ccc; border-radius:4px;">
            </div>
            @endif
          </div>

          <!-- Row 6: Remark -->
          <div class="extra-field-block" style="display:{{ $status === 'ABSENT' ? 'none' : 'block' }}; margin-bottom:0.5rem;">
            <label style="font-size:0.8rem; color:var(--text-muted); display:block;">Remark</label>
            <input type="text" name="attendances[{{$index}}][remark]" value="{{ $att->remark ?? '' }}" class="remark-input" {{ $isAdmin ? 'disabled' : '' }} style="width:100%; padding:0.4rem; border:1px solid #ccc; border-radius:4px;">
          </div>
          
        </div>
        @endforeach
      </div>
    </div>
    @endforeach
  </form>
</div>

<script>
// Filter by name or department
function filterWorkers() {
    const term = document.getElementById('workerSearch').value.toLowerCase();
    const deptFilter = document.getElementById('deptFilter').value.toLowerCase();
    
    document.querySelectorAll('.department-group').forEach(group => {
        let hasVisibleCards = false;
        const groupDept = group.getAttribute('data-group-dept');
        
        group.querySelectorAll('.worker-card').forEach(card => {
            const name = card.getAttribute('data-name');
            const dept = card.getAttribute('data-dept');
            
            let matchesTerm = name.includes(term) || dept.includes(term);
            let matchesDept = deptFilter === "" || groupDept === deptFilter;
            
            if (matchesTerm && matchesDept) {
                card.style.display = 'block';
                hasVisibleCards = true;
            } else {
                card.style.display = 'none';
            }
        });
        
        group.style.display = hasVisibleCards ? 'block' : 'none';
    });
}

// Handle Status Change
function handleStatusChange(selectEl) {
    const card = selectEl.closest('.worker-card');
    const inputs = card.querySelectorAll('input[type="time"]');
    const badge = card.querySelector('.status-badge');
    const val = selectEl.value;

    // Update border color
    let color = (val === 'ABSENT') ? '#e74c3c' : '#2ecc71';

    card.style.borderLeftColor = color;
    badge.style.background = color;
    badge.innerText = val.replace('_', ' ');

    const extraBlocks = card.querySelectorAll('.extra-field-block');
    const extraFlexes = card.querySelectorAll('.extra-field-flex');

    if (val === 'ABSENT') {
        extraBlocks.forEach(f => f.style.display = 'none');
        extraFlexes.forEach(f => f.style.display = 'none');
        inputs.forEach(i => { i.value = ''; i.disabled = true; });
    } else {
        extraBlocks.forEach(f => f.style.display = 'block');
        extraFlexes.forEach(f => {
            if (f.classList.contains('row-3')) {
                const shiftVal = card.querySelector('.shift-select').value;
                f.style.display = (shiftVal === 'CUSTOM') ? 'flex' : 'none';
            } else {
                f.style.display = 'flex';
            }
        });
        inputs.forEach(i => { i.disabled = false; });
    }
}

// Handle Shift Change
function handleShiftChange(selectEl) {
    const card = selectEl.closest('.worker-card');
    const badge = card.querySelector('.shift-badge');
    const val = selectEl.value;
    
    const inTime = card.querySelector('.time-in');
    const outTime = card.querySelector('.time-out');
    
    const labelIn = card.querySelector('.label-in');
    const labelOut = card.querySelector('.label-out');
    const row3 = card.querySelector('.row-3');
    
    if (val === 'DAY') {
        badge.innerText = '☀ Day 9-6';
        labelIn.innerText = 'In Time';
        labelOut.innerText = 'Out Time';
        row3.style.display = 'none';
        if(inTime._flatpickr) inTime._flatpickr.setDate('09:00');
        if(outTime._flatpickr) outTime._flatpickr.setDate('18:00');
    } else if (val === 'NIGHT') {
        badge.innerText = '🌙 Night 9-6';
        labelIn.innerText = 'In Time';
        labelOut.innerText = 'Out Time';
        row3.style.display = 'none';
        if(inTime._flatpickr) inTime._flatpickr.setDate('21:00');
        if(outTime._flatpickr) outTime._flatpickr.setDate('06:00');
    } else {
        badge.innerText = '⚙ Custom';
        labelIn.innerText = 'Day In Time';
        labelOut.innerText = 'Day Out Time';
        row3.style.display = 'flex';
    }
}

// Handle OT/UT Change
function handleOTUTChange(selectEl) {
    const card = selectEl.closest('.worker-card');
    const container = card.querySelector('.ot-hours-container');
    const label = card.querySelector('.ot-label');
    const sign = card.querySelector('.ot-sign');
    const val = selectEl.value;

    if (val === 'NONE') {
        container.style.display = 'none';
    } else {
        container.style.display = 'block';
        label.innerText = val === 'UT' ? 'UT Hours' : 'OT Hours';
        sign.innerText = val === 'UT' ? '-' : '+';
        sign.style.color = val === 'UT' ? '#e74c3c' : '#2ecc71';
    }
}

// Initialize states
document.querySelectorAll('.status-select').forEach(sel => handleStatusChange(sel));
document.querySelectorAll('.ot-select').forEach(sel => handleOTUTChange(sel));

// Mark All Present
function markAllPresent() {
    document.querySelectorAll('.worker-card').forEach(card => {
        const status = card.querySelector('.status-select');
        status.value = 'PRESENT';
        handleStatusChange(status);

        const shift = card.querySelector('.shift-select');
        // We do not overwrite shift type if it's already set, but we set the defaults based on it
        handleShiftChange(shift);
    });
}

// Save All via AJAX
function saveAllAttendance() {
    const form = document.getElementById('bulk-attendance-form');
    const formData = new FormData(form);
    
    // Structure the data array
    const data = { date: '{{ $date }}', attendances: [] };
    
    document.querySelectorAll('.worker-card').forEach((card, i) => {
        data.attendances.push({
            worker_id: formData.get(`attendances[${i}][worker_id]`),
            status: formData.get(`attendances[${i}][status]`),
            shift_type: formData.get(`attendances[${i}][shift_type]`),
            in_time: formData.get(`attendances[${i}][in_time]`),
            out_time: formData.get(`attendances[${i}][out_time]`),
            break_in: formData.get(`attendances[${i}][break_in]`),
            break_out: formData.get(`attendances[${i}][break_out]`),
            ot_ut: formData.get(`attendances[${i}][ot_ut]`),
            ot_ut_hours: formData.get(`attendances[${i}][ot_ut_hours]`),
            advance: formData.get(`attendances[${i}][advance]`),
            num_workers: formData.get(`attendances[${i}][num_workers]`),
            remark: formData.get(`attendances[${i}][remark]`),
        });
    });

    Swal.fire({
        title: 'Saving Records',
        text: 'Please wait...',
        allowOutsideClick: false,
        didOpen: () => Swal.showLoading()
    });

    fetch(window.location.href.split('?')[0], {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || csrfToken },
        body: JSON.stringify(data)
    }).then(r=>r.json()).then(d=>{
        if(d.success) Swal.fire('Saved!', d.message, 'success');
        else Swal.fire('Error', d.message, 'error');
    }).catch(e => {
        Swal.fire('Error', 'An unexpected error occurred.', 'error');
    });
}
</script>
<!-- Include Flatpickr JS -->
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script>
  flatpickr(".time-picker", {
      enableTime: true,
      noCalendar: true,
      dateFormat: "H:i",
      altInput: true,
      altFormat: "h:i K",
      allowInput: true
  });
</script>
@endsection
