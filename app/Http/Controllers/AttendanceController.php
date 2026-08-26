<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Department;
use App\Models\Worker;
use App\Models\Attendance;
use App\Models\WorkerMonthlyAdjustment;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    private function authUser() { return session('auth_user'); }

    public function team(Request $request)
    {
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
            'layout'       => str_contains($request->path(), 'admin') ? 'layouts.admin' : 'layouts.app'
        ]);
    }

    // --- DEPARTMENTS ---
    public function departments(Request $request)
    {
        $departments = Department::withCount('workers')->get();
        return view('attendance.departments', [
            'departments' => $departments,
            'layout'      => str_contains($request->path(), 'admin') ? 'layouts.admin' : 'layouts.app'
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
        $workers     = Worker::with('department')->orderBy('name')->get();
        $departments = Department::where('is_active', true)->orderBy('name')->get();
        return view('attendance.workers', [
            'workers'     => $workers,
            'departments' => $departments,
            'layout'      => str_contains($request->path(), 'admin') ? 'layouts.admin' : 'layouts.app'
        ]);
    }

    // JSON API for SPA
    public function workersJson()
    {
        $workers = Worker::with('department')->orderBy('name')->get()->map(fn($w) => [
            'id'             => $w->id,
            'name'           => $w->name,
            'department'     => $w->department->name ?? '—',
            'department_id'  => $w->department_id,
            'role'           => $w->role,
            'shift_type'     => $w->shift_type,
            'salary_type'    => $w->salary_type,
            'salary_amount'  => (float)$w->salary_amount,
            'daily_salary'   => (float)$w->daily_salary,
            'status'         => $w->status,
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
            'salary_type'   => 'required|in:DAILY,MONTHLY,FIXED_MONTHLY,LABOUR_MUKADAM',
            'salary_amount' => 'required|numeric|min:0',
            'per_hour_salary' => 'nullable|numeric|min:0',
            'status'        => 'required|in:ACTIVE,INACTIVE'
        ]);

        $data = $request->all();
        
        // Auto-calculate daily_salary for backward compatibility and internal logic
        if ($request->salary_type === 'MONTHLY') {
            $data['daily_salary'] = $request->salary_amount / 30;
        } else {
            $data['daily_salary'] = $request->salary_amount;
        }

        if ($request->worker_id) {
            Worker::findOrFail($request->worker_id)->update($data);
            return response()->json(['success' => true, 'message' => 'Worker updated']);
        }
        Worker::create($data);
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
        $date = $request->date ?? Carbon::today()->toDateString();
        $workers = Worker::with(['department', 'attendances' => fn($q) => $q->whereDate('date', $date)])
            ->where('status', 'ACTIVE')->orderBy('name')->get();

        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => 'View ready']);
        }

        $departments = \App\Models\Department::orderBy('name')->get();

        return view('attendance.daily', [
            'workers'     => $workers,
            'departments' => $departments,
            'date'        => $date,
            'layout'      => str_contains($request->path(), 'admin') ? 'layouts.admin' : 'layouts.app'
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
                'shift_type'   => $att?->shift_type ?? $w->shift_type,
                'ot_ut'        => $att?->ot_ut ?? 'NONE',
                'ot_ut_hours'  => (float)($att?->ot_ut_hours ?? 0),
                'advance'      => (float)($att?->advance ?? 0),
                'remark'       => $att?->remark ?? '',
                'is_finished'  => (bool)($att?->is_finished ?? false),
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
            'attendances.*.status'      => 'required|string',
            'attendances.*.in_time'     => 'nullable|date_format:H:i',
            'attendances.*.out_time'    => 'nullable|date_format:H:i',
            'attendances.*.break_in'    => 'nullable|date_format:H:i',
            'attendances.*.break_out'   => 'nullable|date_format:H:i',
            'attendances.*.shift_type'  => 'nullable|string',
            'attendances.*.ot_ut'       => 'nullable|in:NONE,OT,UT',
            'attendances.*.ot_ut_hours' => 'nullable|numeric',
            'attendances.*.advance'     => 'nullable|numeric',
            'attendances.*.num_workers' => 'nullable|integer|min:0',
            'attendances.*.remark'      => 'nullable|string',
            'attendances.*.is_finished' => 'nullable|boolean',
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
                $mins = abs($in->diffInMinutes($out));

                if (!empty($rec['break_in']) && !empty($rec['break_out'])) {
                    $bIn  = Carbon::parse($date . ' ' . $rec['break_in']);
                    $bOut = Carbon::parse($date . ' ' . $rec['break_out']);
                    if ($bOut->lt($bIn)) $bOut->addDay();
                    $mins -= abs($bIn->diffInMinutes($bOut));
                }

                $totalHours  = max(0, $mins / 60);
                $overtimeHrs = max(0, $totalHours - $std);
            }

            if (($rec['ot_ut'] ?? 'NONE') === 'OT') {
                $overtimeHrs = (float)($rec['ot_ut_hours'] ?? 0);
            } elseif (($rec['ot_ut'] ?? 'NONE') === 'UT') {
                $overtimeHrs = -(float)($rec['ot_ut_hours'] ?? 0);
            }

            $hourly = ($worker->daily_salary ?? 500) / $std;
            $multiplier = $this->getPresentMultiplier($rec['status']);
            
            if ($rec['status'] === 'ABSENT') {
                $wage = 0;
            } else {
                if ($worker->salary_type === 'LABOUR_MUKADAM') {
                    $numWorkers = isset($rec['num_workers']) && $rec['num_workers'] !== '' ? (int)$rec['num_workers'] : 0;
                    $wage = ($worker->daily_salary ?? 0) * $numWorkers;
                } else {
                    if ($totalHours > 0) {
                        $normalHrs = min($totalHours, $std);
                        // For hourly tracked, if they are PRESENT but worked less, they get fraction.
                        // We apply the multiplier to the base day value if it's more than 1, otherwise fraction.
                        $wage = ($normalHrs * $hourly) * ($multiplier >= 1 ? $multiplier : 1) + ($overtimeHrs * ($hourly * 1.5));
                        if ($multiplier == 0.5) {
                            $wage = (($worker->daily_salary ?? 500) / 2) + ($overtimeHrs * ($hourly * 1.5));
                        }
                    } else {
                        $wage = (($worker->daily_salary ?? 500) * $multiplier) + ($overtimeHrs * ($hourly * 1.5));
                    }
                }
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
                    'shift_type'      => $rec['shift_type'] ?? null,
                    'ot_ut'           => $rec['ot_ut'] ?? 'NONE',
                    'ot_ut_hours'     => (float)($rec['ot_ut_hours'] ?? 0),
                    'advance'         => (float)($rec['advance'] ?? 0),
                    'num_workers'     => isset($rec['num_workers']) && $rec['num_workers'] !== '' ? (int)$rec['num_workers'] : null,
                    'remark'          => $rec['remark'] ?? null,
                    'is_finished'     => !empty($rec['is_finished']),
                ]
            );
        }

        return response()->json(['success' => true, 'message' => 'Attendance saved successfully']);
    }

    // --- REPORTS ---
    public function reports(Request $request)
    {
        $month     = $request->month ?? Carbon::today()->format('Y-m');
        $startDate = Carbon::parse($month)->startOfMonth()->toDateString();
        $endDate   = Carbon::parse($month)->endOfMonth()->toDateString();

        // Get workers and their attendance for the month
        $workers = Worker::with(['department', 'attendances' => function($q) use ($startDate, $endDate) {
            $q->whereBetween('date', [$startDate, $endDate]);
        }])->where('status', 'ACTIVE')->orderBy('name')->get();

        $reportData = [];
        foreach ($workers as $w) {
            $present = 0; $absent = 0; $half = 0;
            $totalOT = 0; $totalWage = 0;

            foreach ($w->attendances as $att) {
                if ($att->status == 'ABSENT') {
                    $absent++;
                } else {
                    $present += $this->getPresentMultiplier($att->status);
                }
                $totalOT += $att->overtime_hours;

                if ($w->salary_type === 'DAILY' || $w->salary_type === 'LABOUR_MUKADAM') {
                    $totalWage += $att->calculated_wage;
                } else {
                    // For Monthly, we only sum the OT portion here. 
                    // Base salary is added once at the end.
                    $hourly = ($w->daily_salary ?? 0) / 9;
                    $otPay = $att->overtime_hours * ($hourly * 1.5);
                    $totalWage += $otPay;
                }
            }

            if ($w->salary_type === 'MONTHLY') {
                $totalWage += $w->salary_amount;
            }

            $reportData[$w->id] = [
                'worker'     => $w,
                'present'    => $present,
                'absent'     => $absent,
                'half'       => $half,
                'total_ot'   => $totalOT,
                'total_wage' => $totalWage
            ];
        }

        return view('attendance.reports', [
            'reportData' => $reportData,
            'month'      => $month,
            'layout'     => str_contains($request->path(), 'admin') ? 'layouts.admin' : 'layouts.app'
        ]);
    }

    public function workerReport(Request $request, $id)
    {
        $data = $this->prepareWorkerReportData($request, $id);
        $data['layout'] = str_contains($request->path(), 'admin') ? 'layouts.admin' : 'layouts.app';
        return view('attendance.worker_report', $data);
    }

    public function updateMonthlyAdjustment(Request $request, $id)
    {
        $request->validate([
            'month' => 'required|date_format:Y-m',
            'petrol_food_amount' => 'nullable|numeric',
            'advance' => 'nullable|numeric',
            'remark' => 'nullable|string'
        ]);

        $adj = WorkerMonthlyAdjustment::updateOrCreate(
            ['worker_id' => $id, 'month' => $request->month],
            [
                'petrol_food_amount' => $request->petrol_food_amount ?? 0,
                'advance' => $request->advance ?? 0,
                'remark' => $request->remark
            ]
        );

        return redirect()->back()->with('success', 'Monthly adjustments saved successfully');
    }

    public function workerMonthlySalaryPdf(Request $request, $id)
    {
        $data = $this->prepareWorkerReportData($request, $id);
        
        $pdf = Pdf::loadView('pdf.monthly-salary-sheet', $data)->setPaper('A4', 'portrait');
        
        $filename = strtoupper(str_replace(' ', '_', $data['worker']->name)) . '_SALARY_' . str_replace('-', '', $data['month']) . '.pdf';
        
        return $pdf->download($filename);
    }

    private function prepareWorkerReportData(Request $request, $id)
    {
        $worker = Worker::with('department')->findOrFail($id);
        
        $month = $request->query('month', date('Y-m'));
        $start = Carbon::parse($month)->startOfMonth();
        $end   = Carbon::parse($month)->endOfMonth();
        $daysInMonth = $start->daysInMonth;

        $attendances = Attendance::where('worker_id', $id)
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->get()
            ->keyBy('date');

        $adjustment = WorkerMonthlyAdjustment::firstOrCreate(
            ['worker_id' => $id, 'month' => $month],
            ['petrol_food_amount' => 0, 'advance' => 0, 'remark' => null]
        );

        $totalOT = 0;
        $totalWage = 0;
        $presentDays = 0;
        $otUtAdjustment = 0;
        $hourlyRate = 0;
        $perDaySalary = 0;
        $attendanceSalary = 0;

        foreach ($attendances as $att) {
            $totalOT += $att->overtime_hours;
            if ($worker->salary_type === 'LABOUR_MUKADAM') {
                $presentDays += $att->num_workers ?? 0;
            } else {
                if ($att->status !== 'ABSENT') {
                    $presentDays += $this->getPresentMultiplier($att->status);
                }
            }
        }

        if ($worker->salary_type === 'FIXED_MONTHLY') {
            $totalWage = $worker->salary_amount + $adjustment->petrol_food_amount;
        } elseif ($worker->salary_type === 'LABOUR_MUKADAM') {
            $perDaySalary = $worker->salary_amount ?? 0;
            $attendanceSalary = $presentDays * $perDaySalary;
            
            $hourlyRate = $worker->per_hour_salary ?? 0;
            $otUtAdjustment = $totalOT * $hourlyRate;
            
            $totalWage = $attendanceSalary + $otUtAdjustment + $adjustment->petrol_food_amount;
        } elseif ($worker->salary_type === 'MONTHLY') {
            $perDaySalary = $worker->salary_amount / $daysInMonth;
            $attendanceSalary = $presentDays * $perDaySalary;
            
            $hourlyRate = $worker->per_hour_salary > 0 ? $worker->per_hour_salary : (($worker->daily_salary ?? ($worker->salary_amount / 30)) / 9);
            $otUtAdjustment = $totalOT * $hourlyRate;
            
            $totalWage = $attendanceSalary + $otUtAdjustment + $adjustment->petrol_food_amount;
        } elseif ($worker->salary_type === 'DAILY') {
            $perDaySalary = $worker->salary_amount ?? 0;
            $attendanceSalary = $presentDays * $perDaySalary;
            
            $hourlyRate = $worker->per_hour_salary ?? 0;
            $otUtAdjustment = $totalOT * $hourlyRate;
            
            $totalWage = $attendanceSalary + $otUtAdjustment + $adjustment->petrol_food_amount;
        } else {
            foreach ($attendances as $att) {
                $attendanceSalary += $att->calculated_wage;
            }
            $hourlyRate = $worker->per_hour_salary > 0 ? $worker->per_hour_salary : (($worker->daily_salary ?? 0) / 9);
            $otUtAdjustment = $totalOT * $hourlyRate;
            // Assuming calculated_wage already handles OT for daily workers normally, we'll keep existing logic or just use attendanceSalary
            $totalWage = $attendanceSalary + $adjustment->petrol_food_amount;
            $perDaySalary = $worker->daily_salary ?? 0;
        }

        $payableSalary = $totalWage - $adjustment->advance;

        return compact(
            'worker', 'attendances', 'month', 'start', 'end', 'daysInMonth',
            'adjustment', 'presentDays', 'perDaySalary', 'attendanceSalary',
            'totalOT', 'hourlyRate', 'otUtAdjustment', 'totalWage', 'payableSalary'
        );
    }

    public function profile(Request $request)
    {
        $authUser = $this->authUser();
        $layout = str_contains($request->path(), 'admin') ? 'layouts.admin' : 'layouts.app';
        return view('attendance.profile', compact('authUser', 'layout'));
    }

    private function getPresentMultiplier($status) {
        $map = [
            'PRESENT' => 1,
            'ABSENT' => 0,
            'HALF' => 0.5,
            'PRESENT + HALF' => 1.5,
            'DOUBLE' => 2,
            'SUNDAY' => 1,
            'HALF (OFF)' => 1.5,
            'PRESENT (OFF)' => 2,
            'PR. + HALF (OFF)' => 2.5,
            'DOUBLE (OFF)' => 3,
            'HOLIDAY' => 1,
            'PAID LEAVE' => 1,
        ];
        return $map[$status] ?? 0;
    }
}
