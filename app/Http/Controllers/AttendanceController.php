<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Department;
use App\Models\Worker;
use App\Models\Attendance;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    private function authUser() { return session('auth_user'); }

    public function team(Request $request)
    {
        if (!$request->ajax()) return view('attendance.spa');
        
        return response()->json([
            'success' => true,
            'workers' => Worker::with('department')->get(),
            'departments' => Department::withCount('workers')->get()
        ]);
    }

    // --- DASHBOARD ---
    public function home(Request $request)
    {
        $user = $this->authUser();
        if ($user['role'] === 'ATTENDANCE' && !$request->ajax()) {
            return view('attendance.spa');
        }
        
        $today = Carbon::today()->toDateString();
        $totalWorkers  = Worker::count();
        $activeWorkers = Worker::where('status','ACTIVE')->count();
        $presentToday  = Attendance::where('date', $today)->whereIn('status', ['PRESENT', 'HALF_DAY'])->count();
        $absentToday   = Attendance::where('date', $today)->where('status', 'ABSENT')->count();
        $totalOT       = Attendance::where('date', $today)->sum('overtime_hours');
        $departments   = Department::withCount('workers')->orderBy('name')->get();

        return view('attendance.home', [
            'totalWorkers' => $totalWorkers,
            'activeWorkers' => $activeWorkers,
            'presentToday' => $presentToday,
            'absentToday'  => $absentToday,
            'totalOT'      => $totalOT,
            'departments'  => $departments,
            'layout'       => 'layouts.admin'
        ]);
    }

    // --- DEPARTMENTS ---
    public function departments(Request $request)
    {
        if ($this->authUser()['role'] === 'ATTENDANCE' && !$request->ajax()) {
            return view('attendance.spa');
        }
        $departments = Department::withCount('workers')->get();
        return view('attendance.departments', [
            'departments' => $departments,
            'layout'      => 'layouts.admin'
        ]);
    }

    public function storeDepartment(Request $request)
    {
        $request->validate(['name' => 'required|string|max:255']);
        if ($request->department_id) {
            Department::findOrFail($request->department_id)->update($request->only('name'));
            return response()->json(['success' => true, 'message' => 'Department updated']);
        }
        Department::create($request->only('name'));
        return response()->json(['success' => true, 'message' => 'Department created']);
    }

    public function destroyDepartment($id)
    {
        Department::destroy($id);
        return response()->json(['success' => true, 'message' => 'Department deleted']);
    }

    // --- WORKERS ---
    public function workers(Request $request)
    {
        if ($this->authUser()['role'] === 'ATTENDANCE' && !$request->ajax()) {
            return view('attendance.spa');
        }
        $workers     = Worker::with('department')->orderBy('name')->get();
        $departments = Department::where('is_active', true)->orderBy('name')->get();
        return view('attendance.workers', [
            'workers'     => $workers,
            'departments' => $departments,
            'layout'      => 'layouts.admin'
        ]);
    }

    // JSON API for SPA
    public function workersJson()
    {
        $workers = Worker::with('department')->orderBy('name')->get()->map(fn($w) => [
            'id'           => $w->id,
            'name'         => $w->name,
            'department'   => $w->department->name ?? '—',
            'department_id'=> $w->department_id,
            'role'         => $w->role,
            'shift_type'   => $w->shift_type,
            'daily_salary' => (float)$w->daily_salary,
            'status'       => $w->status,
        ]);
        return response()->json(['success' => true, 'workers' => $workers]);
    }

    public function departmentsJson()
    {
        $depts = Department::where('is_active', true)->orderBy('name')->get(['id','name']);
        return response()->json(['success' => true, 'departments' => $depts]);
    }

    public function storeWorker(Request $request)
    {
        $request->validate([
            'name'          => 'required|string|max:255',
            'department_id' => 'required|exists:departments,id',
            'role'          => 'nullable|string',
            'shift_type'    => 'required|in:DAY,NIGHT,CUSTOM',
            'daily_salary'  => 'required|numeric|min:0',
            'status'        => 'required|in:ACTIVE,INACTIVE'
        ]);

        if ($request->worker_id) {
            Worker::findOrFail($request->worker_id)->update($request->all());
            return response()->json(['success' => true, 'message' => 'Worker updated']);
        }
        Worker::create($request->all());
        return response()->json(['success' => true, 'message' => 'Worker created']);
    }

    public function destroyWorker($id)
    {
        Worker::destroy($id);
        return response()->json(['success' => true, 'message' => 'Worker deleted']);
    }

    // --- DAILY ATTENDANCE ---
    public function daily(Request $request)
    {
        if ($this->authUser()['role'] === 'ATTENDANCE' && !$request->ajax()) {
            return view('attendance.spa');
        }
        $date = $request->date ?? Carbon::today()->toDateString();
        $workers = Worker::with(['department', 'attendances' => fn($q) => $q->whereDate('date', $date)])
            ->where('status', 'ACTIVE')->orderBy('name')->get();

        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => 'View ready']);
        }

        return view('attendance.daily', [
            'workers' => $workers,
            'date'    => $date,
            'layout'  => str_contains($request->path(), 'admin') ? 'layouts.admin' : 'layouts.app'
        ]);
    }

    // JSON: attendance for a specific date (for SPA)
    public function dailyJson(Request $request)
    {
        $date = $request->date ?? Carbon::today()->toDateString();
        $workers = Worker::with(['department', 'attendances' => fn($q) => $q->where('date', $date)])
            ->where('status', 'ACTIVE')->orderBy('name')->get();

        $data = $workers->map(function($w) {
            $att = $w->attendances->first();
            return [
                'worker_id'    => $w->id,
                'name'         => $w->name,
                'department'   => $w->department->name ?? '—',
                'shift_type'   => $w->shift_type,
                'daily_salary' => (float)$w->daily_salary,
                'status'       => $att?->status ?? 'ABSENT',
                'in_time'      => $att?->in_time ? \Carbon\Carbon::parse($att->in_time)->format('H:i') : '',
                'out_time'     => $att?->out_time ? \Carbon\Carbon::parse($att->out_time)->format('H:i') : '',
                'break_in'     => $att?->break_in ? \Carbon\Carbon::parse($att->break_in)->format('H:i') : '',
                'break_out'    => $att?->break_out ? \Carbon\Carbon::parse($att->break_out)->format('H:i') : '',
                'total_hours'  => (float)($att?->total_hours ?? 0),
                'overtime_hours'=> (float)($att?->overtime_hours ?? 0),
                'calculated_wage'=> (float)($att?->calculated_wage ?? 0),
            ];
        });
        return response()->json(['success' => true, 'workers' => $data, 'date' => $date]);
    }

    public function storeDailyAttendance(Request $request)
    {
        $request->validate([
            'date'                      => 'required|date',
            'attendances'               => 'required|array',
            'attendances.*.worker_id'   => 'required|exists:workers,id',
            'attendances.*.status'      => 'required|in:PRESENT,ABSENT,HALF_DAY',
            'attendances.*.in_time'     => 'nullable|date_format:H:i',
            'attendances.*.out_time'    => 'nullable|date_format:H:i',
            'attendances.*.break_in'    => 'nullable|date_format:H:i',
            'attendances.*.break_out'   => 'nullable|date_format:H:i',
        ]);

        $date = $request->date;
        $std  = 9; // Standard shift hours

        foreach ($request->attendances as $rec) {
            $worker      = Worker::find($rec['worker_id']);
            $totalHours  = 0;
            $overtimeHrs = 0;
            $wage        = 0;

            if ($rec['status'] !== 'ABSENT' && !empty($rec['in_time']) && !empty($rec['out_time'])) {
                $in  = Carbon::parse($date . ' ' . $rec['in_time']);
                $out = Carbon::parse($date . ' ' . $rec['out_time']);
                if ($out->lt($in)) $out->addDay();
                $mins = $in->diffInMinutes($out);

                if (!empty($rec['break_in']) && !empty($rec['break_out'])) {
                    $bIn  = Carbon::parse($date . ' ' . $rec['break_in']);
                    $bOut = Carbon::parse($date . ' ' . $rec['break_out']);
                    if ($bOut->lt($bIn)) $bOut->addDay();
                    $mins -= $bIn->diffInMinutes($bOut);
                }

                $totalHours  = max(0, $mins / 60);
                $overtimeHrs = max(0, $totalHours - $std);
            }

            $hourly = ($worker->daily_salary ?? 500) / $std;
            
            if ($rec['status'] === 'PRESENT') {
                $normalHrs = min($totalHours, $std);
                $wage = ($normalHrs * $hourly) + ($overtimeHrs * ($hourly * 1.5));
            } elseif ($rec['status'] === 'HALF_DAY') {
                $wage = ($worker->daily_salary ?? 500) / 2;
            }

            Attendance::updateOrCreate(
                ['worker_id' => $worker->id, 'date' => $date],
                [
                    'in_time'         => !empty($rec['in_time']) ? $rec['in_time'] : null,
                    'out_time'        => !empty($rec['out_time']) ? $rec['out_time'] : null,
                    'break_in'        => !empty($rec['break_in']) ? $rec['break_in'] : null,
                    'break_out'       => !empty($rec['break_out']) ? $rec['break_out'] : null,
                    'total_hours'     => (float)$totalHours,
                    'overtime_hours'  => (float)$overtimeHrs,
                    'status'          => $rec['status'],
                    'calculated_wage' => (float)max(0, $wage),
                ]
            );
        }

        return response()->json(['success' => true, 'message' => 'Attendance saved successfully']);
    }

    // --- REPORTS ---
    public function reports(Request $request)
    {
        if ($this->authUser()['role'] === 'ATTENDANCE' && !$request->ajax()) {
            return view('attendance.spa');
        }
        $month     = $request->month ?? Carbon::today()->format('Y-m');
        $startDate = Carbon::parse($month)->startOfMonth()->toDateString();
        $endDate   = Carbon::parse($month)->endOfMonth()->toDateString();

        $attendances = Attendance::with(['worker', 'worker.department'])
            ->whereBetween('date', [$startDate, $endDate])->get();

        $reportData = [];
        foreach ($attendances as $att) {
            $wid = $att->worker_id;
            if (!isset($reportData[$wid])) {
                $reportData[$wid] = [
                    'worker'     => $att->worker,
                    'present'    => 0, 'absent' => 0, 'half' => 0,
                    'total_ot'   => 0, 'total_wage' => 0
                ];
            }
            if ($att->status == 'PRESENT')       $reportData[$wid]['present']++;
            elseif ($att->status == 'ABSENT')    $reportData[$wid]['absent']++;
            elseif ($att->status == 'HALF_DAY')  $reportData[$wid]['half']++;
            $reportData[$wid]['total_ot']   += $att->overtime_hours;
            $reportData[$wid]['total_wage'] += $att->calculated_wage;
        }

        return view('attendance.reports', [
            'reportData' => $reportData,
            'month'      => $month,
            'layout'     => 'layouts.admin'
        ]);
    }

    public function workerReport(Request $request, $id)
    {
        $worker = Worker::with('department')->findOrFail($id);
        $month  = $request->month ?? Carbon::today()->format('Y-m');
        $start  = Carbon::parse($month)->startOfMonth()->toDateString();
        $end    = Carbon::parse($month)->endOfMonth()->toDateString();

        $attendances = Attendance::where('worker_id', $id)
            ->whereBetween('date', [$start, $end])
            ->get()
            ->keyBy('date');

        return view('attendance.worker_report', [
            'worker'      => $worker,
            'attendances' => $attendances,
            'month'       => $month,
            'layout'      => $this->authUser()['role'] === 'ADMIN' ? 'layouts.admin' : 'layouts.app'
        ]);
    }
}
