<?php

namespace App\Http\Controllers\Payroll;

use App\Http\Controllers\Controller;
use App\Models\PayrollSalary;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\PayrollAuditLog;

class StaffSalaryController extends Controller
{
    public function index(Request $request)
    {
        $query = \App\Models\PayrollSalary::with(['employee.user']);
        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }
        if ($request->filled('salary_month')) {
            $query->where('salary_month', $request->salary_month);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('search')) {
            $query->whereHas('employee.user', function($q) use ($request) {
                $q->where('name', 'like', '%'.$request->search.'%');
            });
        }
        $salaries = $query->orderByDesc('salary_month')->paginate(20);
        $employees = \App\Models\PayrollEmployee::with('user')->get();
        return view('payroll.staff-salary', compact('salaries', 'employees'));
    }

    public function create()
    {
        $employees = \App\Models\PayrollEmployee::with('user')->get();
        return view('payroll.create-salary', compact('employees'));
    }

    public function store(\Illuminate\Http\Request $request)
    {
        $data = $request->validate([
            'employee_id' => 'required|exists:payroll_employees,id',
            'salary_month' => 'required|date_format:Y-m',
            'gross_salary' => 'required|numeric',
            'deductions' => 'nullable|numeric',
            'net_salary' => 'required|numeric',
            'status' => 'required|string',
            'pf' => 'nullable|numeric',
            'esi' => 'nullable|numeric',
            'tds' => 'nullable|numeric',
            'custom_components' => 'nullable|string',
        ]);
        if (!empty($data['custom_components'])) {
            $json = json_decode($data['custom_components'], true);
            $data['custom_components'] = is_array($json) ? $json : null;
        } else {
            $data['custom_components'] = null;
        }
        \App\Models\PayrollSalary::create($data);
        return redirect()->route('payroll.salary.index')->with('status', 'Salary record added!');
    }

    public function edit($id)
    {
        $salary = \App\Models\PayrollSalary::findOrFail($id);
        $employees = \App\Models\PayrollEmployee::with('user')->get();
        return view('payroll.edit-salary', compact('salary', 'employees'));
    }

    public function update(\Illuminate\Http\Request $request, $id)
    {
        $salary = \App\Models\PayrollSalary::findOrFail($id);
        $old = $salary->only(['gross_salary', 'deductions', 'net_salary', 'status']);
        $data = $request->validate([
            'gross_salary' => 'required|numeric',
            'deductions' => 'nullable|numeric',
            'net_salary' => 'required|numeric',
            'status' => 'required|string',
            'pf' => 'nullable|numeric',
            'esi' => 'nullable|numeric',
            'tds' => 'nullable|numeric',
            'custom_components' => 'nullable|string',
        ]);
        if (!empty($data['custom_components'])) {
            $json = json_decode($data['custom_components'], true);
            $data['custom_components'] = is_array($json) ? $json : null;
        } else {
            $data['custom_components'] = null;
        }
        $salary->update($data);
        PayrollAuditLog::create([
            'salary_id' => $salary->id,
            'user_id' => Auth::id(),
            'action' => 'update',
            'old_values' => $old,
            'new_values' => $data,
        ]);
        return redirect()->route('payroll.salary.index')->with('status', 'Salary record updated!');
    }

    public function show($id)
    {
        $salary = \App\Models\PayrollSalary::with(['employee.user', 'payslip'])->findOrFail($id);
        $revisions = PayrollAuditLog::where('salary_id', $id)->orderByDesc('created_at')->get();
        return view('payroll.show-salary', compact('salary', 'revisions'));
    }

    public function generatePayslip($id)
    {
        $salary = \App\Models\PayrollSalary::with(['employee.user'])->findOrFail($id);
        // Render Blade template as HTML
        $html = view('payroll.payslip-template', compact('salary'))->render();
        $fileName = 'payslips/payslip_' . $salary->id . '.html';
        \Illuminate\Support\Facades\Storage::disk('public')->put($fileName, $html);
        $salary->payslip()->updateOrCreate([], ['file_path' => 'storage/' . $fileName]);
        return redirect()->route('payroll.salary.show', $salary->id)->with('status', 'Payslip generated!');
    }

    public function downloadPayslip($id)
    {
        $salary = \App\Models\PayrollSalary::with('payslip')->findOrFail($id);
        if (!$salary->payslip || !\Illuminate\Support\Facades\Storage::disk('public')->exists(str_replace('storage/', '', $salary->payslip->file_path))) {
            abort(404);
        }
        $filePath = public_path($salary->payslip->file_path);
        $ext = pathinfo($filePath, PATHINFO_EXTENSION);
        $downloadName = 'payslip_' . $salary->id . '.' . $ext;
        return response()->download($filePath, $downloadName);
    }

    public function summary(Request $request)
    {
        $query = \App\Models\PayrollSalary::query();
        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }
        if ($request->filled('salary_month')) {
            $query->where('salary_month', $request->salary_month);
        }
        $totalPaid = (clone $query)->where('status', 'paid')->sum('net_salary');
        $totalPending = (clone $query)->where('status', 'pending')->sum('net_salary');
        $thisMonth = (clone $query)->where('salary_month', now()->format('Y-m'))->sum('net_salary');
        $monthly = (clone $query)
            ->selectRaw('salary_month, SUM(CASE WHEN status = "paid" THEN net_salary ELSE 0 END) as paid, SUM(CASE WHEN status = "pending" THEN net_salary ELSE 0 END) as pending')
            ->groupBy('salary_month')
            ->orderBy('salary_month')
            ->get()
            ->keyBy('salary_month')
            ->map(fn($row) => ['paid' => $row->paid, 'pending' => $row->pending]);
        $designationBreakdown = (clone $query)
            ->join('payroll_employees', 'payroll_salaries.employee_id', '=', 'payroll_employees.id')
            ->selectRaw('payroll_employees.designation, SUM(net_salary) as total')
            ->groupBy('payroll_employees.designation')
            ->orderByDesc('total')
            ->get();
        $topEarners = (clone $query)
            ->with(['employee.user'])
            ->orderByDesc('net_salary')
            ->take(5)
            ->get();
        $employees = \App\Models\PayrollEmployee::with('user')->get();
        // Outlier detection (top/bottom 3 salaries)
        $outliers = (clone $query)
            ->with(['employee.user'])
            ->orderBy('net_salary')
            ->take(3)
            ->get()
            ->merge(
                (clone $query)
                    ->with(['employee.user'])
                    ->orderByDesc('net_salary')
                    ->take(3)
                    ->get()
            );
        // Attendance-linked analytics (salary per present day)
        $attendanceStats = (clone $query)
            ->join('attendances', 'payroll_employees.user_id', '=', 'attendances.user_id')
            ->selectRaw('payroll_salaries.id, payroll_salaries.net_salary, COUNT(attendances.id) as present_days, (payroll_salaries.net_salary / NULLIF(COUNT(attendances.id),0)) as per_day_salary')
            ->groupBy('payroll_salaries.id')
            ->orderByDesc('per_day_salary')
            ->take(5)
            ->get();
        $months = array_keys($monthly->toArray());
        $paid = array_values(collect($monthly)->pluck('paid')->toArray());
        $pending = array_values(collect($monthly)->pluck('pending')->toArray());
        return view('payroll.summary', compact('totalPaid', 'totalPending', 'thisMonth', 'monthly', 'employees', 'designationBreakdown', 'topEarners', 'outliers', 'attendanceStats', 'months', 'paid', 'pending'));
    }

    public function export()
    {
        $salaries = \App\Models\PayrollSalary::with(['employee.user'])->get();
        $csv = "Staff,Designation,Month,Net Salary,Status\n";
        foreach ($salaries as $s) {
            $csv .= '"'.($s->employee->user->name ?? '-').'",';
            $csv .= '"'.($s->employee->designation ?? '-').'",';
            $csv .= '"'.$s->salary_month.'",';
            $csv .= '"'.$s->net_salary.'",';
            $csv .= '"'.$s->status.'"\n';
        }
        return response($csv)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="salary_export.csv"');
    }

    public function bulkUpdate(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'action' => 'required|string',
        ]);
        $ids = $request->ids;
        if ($request->action === 'mark_paid') {
            \App\Models\PayrollSalary::whereIn('id', $ids)->update(['status' => 'paid']);
            return back()->with('status', 'Selected salaries marked as paid!');
        }
        if ($request->action === 'export') {
            $salaries = \App\Models\PayrollSalary::with(['employee.user'])->whereIn('id', $ids)->get();
            $csv = "Staff,Designation,Month,Net Salary,Status\n";
            foreach ($salaries as $s) {
                $csv .= '"'.($s->employee->user->name ?? '-').'",';
                $csv .= '"'.($s->employee->designation ?? '-').'",';
                $csv .= '"'.$s->salary_month.'",';
                $csv .= '"'.$s->net_salary.'",';
                $csv .= '"'.$s->status.'"\n';
            }
            return response($csv)
                ->header('Content-Type', 'text/csv')
                ->header('Content-Disposition', 'attachment; filename="salary_export_selected.csv"');
        }
        if ($request->action === 'delete') {
            \App\Models\PayrollSalary::whereIn('id', $ids)->delete();
            return back()->with('status', 'Selected salaries deleted!');
        }
        if ($request->action === 'generate_payslips') {
            foreach ($ids as $id) {
                $salary = \App\Models\PayrollSalary::with(['employee.user'])->find($id);
                if ($salary) {
                    $html = view('payroll.payslip-template', compact('salary'))->render();
                    $fileName = 'payslips/payslip_' . $salary->id . '.html';
                    \Illuminate\Support\Facades\Storage::disk('public')->put($fileName, $html);
                    $salary->payslip()->updateOrCreate([], ['file_path' => 'storage/' . $fileName]);
                }
            }
            return back()->with('status', 'Payslips generated for selected!');
        }
        return back();
    }

    public function emailPayslip($id)
    {
        $salary = \App\Models\PayrollSalary::with(['employee.user', 'payslip'])->findOrFail($id);
        $user = $salary->employee->user;
        if ($salary->payslip && $user && $user->email) {
            $filePath = public_path($salary->payslip->file_path);
            \Illuminate\Support\Facades\Mail::send([], [], function($message) use ($user, $salary, $filePath) {
                $message->to($user->email)
                    ->subject('Your Payslip for ' . $salary->salary_month)
                    ->attach($filePath, [
                        'as' => 'payslip_' . $salary->id . '.html',
                        'mime' => 'text/html',
                    ])
                    ->setBody('Dear ' . $user->name . ',<br>Your payslip for ' . $salary->salary_month . ' is attached.<br><br>Regards,<br>HR Team', 'text/html');
            });
            return back()->with('status', 'Payslip emailed to staff!');
        }
        return back()->with('error', 'Payslip or staff email not found!');
    }
}
