<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight" dir="rtl">
            لیست مرخصی‌ها
        </h2>
    </x-slot>


<style>
.leave-workspace{width:100%;max-width:none;padding:20px clamp(12px,2.4vw,40px);margin:0 auto;box-sizing:border-box}
.leave-workspace *{box-sizing:border-box}
.leave-tools{border:1px solid var(--border,#dce2eb);border-radius:12px;padding:12px 16px;margin:4px 0 16px;background:var(--surface,#fff)}
.leave-tools>summary{cursor:pointer;font-weight:600;list-style-position:inside}
.leave-tools .card{margin:12px 0 0!important;border:0;background:transparent;box-shadow:none!important}
.leave-tools .card-body{padding:0}
.leave-list-panel{border:1px solid var(--border,#dce2eb);border-radius:14px;background:var(--surface,#fff);padding:0 12px 12px}
.leave-table{table-layout:fixed;margin:0;width:100%;font-size:13px;text-align:right;border-collapse:collapse}
.leave-table th{padding:16px 10px;color:var(--muted,#64748b);font-size:12px;font-weight:600;border-bottom:1px solid var(--border,#dce2eb)}
.leave-table th:nth-child(1){width:19%}.leave-table th:nth-child(2){width:15%}.leave-table th:nth-child(3){width:22%}.leave-table th:nth-child(4){width:20%}.leave-table th:nth-child(5){width:24%}
.leave-table td{padding:16px 10px;vertical-align:top;border:0;border-bottom:1px solid var(--border,#dce2eb);white-space:normal;overflow-wrap:anywhere;color:var(--text,#18283b);background:transparent!important}
.leave-table tbody tr:last-child td{border-bottom:0}
.leave-table .badge{white-space:normal;line-height:1.6}
.leave-table .btn{margin:3px 0;min-height:34px}
.leave-table details{min-width:0!important;max-width:100%!important;margin-top:8px}
.leave-table summary{cursor:pointer;font-size:12px;color:var(--muted,#64748b)}
.leave-unit{display:inline-block;padding:2px 7px;border-radius:6px;background:var(--surface-2,#f1f5f9);font-size:11px;margin-inline-start:4px}
.leave-date{display:flex;justify-content:space-between;gap:6px;margin-bottom:5px;font-size:12px;flex-wrap:wrap}
.leave-date>span{color:var(--muted,#64748b)}
.leave-description p{margin:8px 0;line-height:1.8}
.leave-workspace [data-monthly-leave-summary]{min-width:0!important;max-width:none!important;width:100%;font-size:12px}
@media(min-width:1600px){.leave-table{font-size:14px}.leave-workspace{padding-inline:40px}}
@media(max-width:900px){.leave-list-panel{padding:0;border:0;background:transparent}.leave-table,.leave-table tbody{display:block;width:100%}.leave-table thead{display:none}.leave-table tbody tr{display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1fr);border:1px solid var(--border,#dce2eb);border-radius:12px;background:var(--surface,#fff);margin-bottom:12px;padding:8px}.leave-table td{display:block;padding:10px;border:0;min-width:0}.leave-table td:before{content:attr(data-label);display:block;font-size:11px;color:var(--muted,#64748b);margin-bottom:6px}.leave-table td:last-child{grid-column:1/-1;border-top:1px solid var(--border,#dce2eb)}.leave-table td[colspan]{grid-column:1/-1}.leave-date{justify-content:flex-start}}
@media(max-width:420px){.leave-table tbody tr{grid-template-columns:minmax(0,1fr)}.leave-table td{padding:8px}.leave-workspace{padding:12px}}
</style>

    <div class="leave-workspace" dir="rtl">
        @if(session('success'))
            <div class="alert alert-success text-center mb-4">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger text-center mb-4">
                {{ session('error') }}
            </div>
        @endif
        @if($errors->any())<div class="alert alert-danger" role="alert">{{ $errors->first() }}</div>@endif

        @if(auth()->user()->hasRole('Admin'))
            <a href="{{ route('admin.leaves.settings.edit') }}" class="btn btn-secondary mb-4">تنظیمات مرخصی</a>
            @if(!config('organization_flow.enabled',false) && app(\App\Services\LeaveApprovalService::class)->approvers()->isEmpty())
                <div class="alert alert-warning">هنوز تأییدکننده‌ای انتخاب نشده است. از تنظیمات مرخصی افراد مسئول را انتخاب کنید.</div>
            @endif
        @endif

        <a href="{{ route('leaves.create') }}" class="btn btn-primary mb-4">ایجاد مرخصی جدید</a>



<details class="leave-tools"><summary>گزارش و خروجی</summary>
        @if(auth()->user()->hasRole('Accountant'))
            <div class="card mb-3 shadow-sm">
                <div class="card-body">
                    <form method="GET" action="{{ route('leaves.print.monthly') }}" target="_blank" class="row g-2 align-items-end">
                        <div class="col-md-3">
                            <label class="form-label">ماه شمسی</label>
                            <input
                                type="text"
                                name="month"
                                class="form-control"
                                value="{{ request('month') }}"
                                placeholder="1405/02"
                                pattern="\d{4}/\d{2}"
                                required
                            >
                        </div>

                        <div class="col-md-3">
                            <button type="submit" class="btn btn-secondary w-100">
                                پرینت مرخصی‌های ماه
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        @endif

        <div class="card mb-3 shadow-sm">
            <div class="card-body">
                <form method="GET" action="{{ route('leaves.export.csv') }}" class="row g-2 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label">از تاریخ</label>
                        <input
                            type="text"
                            name="from"
                            class="form-control jalali-datepicker"
                            value="{{ request('from') }}"
                            placeholder="1404/11/01"
                            required
                            autocomplete="off"
                            readonly
                        >
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">تا تاریخ</label>
                        <input
                            type="text"
                            name="to"
                            class="form-control jalali-datepicker"
                            value="{{ request('to') }}"
                            placeholder="1404/11/30"
                            required
                            autocomplete="off"
                            readonly
                        >
                    </div>

                    <div class="col-md-3">
                        <button type="submit" class="btn btn-success w-100">
                            خروجی CSV
                        </button>
                    </div>
                </form>
            </div>
        </div>

        </details>
        <div class="leave-list-panel">
            <div class="leave-table-wrap">
                <table class="table leave-table w-full">
                    <thead>
                        <tr>
                            <th>کارمند</th>
                            <th>درخواست</th>
                            <th>بازه مرخصی</th>
                            <th>وضعیت و سابقه</th>
                            <th>بررسی درخواست</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $user = Auth::user(); @endphp

                    
                        @forelse($leaves as $leave)
  @php
    $startDate = verta($leave->start_date)->format('Y/m/d');
    $endDate = verta($leave->end_date)->format('Y/m/d');

    $startTime = $leave->start_time
        ? \Carbon\Carbon::parse($leave->start_time)->format('H:i')
        : '00:00:00';

    $endTime = $leave->end_time
        ? \Carbon\Carbon::parse($leave->end_time)->format('H:i')
        : '00:00:00';

    $startDateTime = $startDate . ' ' . $startTime;
    $endDateTime = $endDate . ' ' . $endTime;

    $createdAt = verta($leave->created_at)->format('Y/m/d H:i');
@endphp

    <tr>
        <td data-label="کارمند">
            {{ $leave->user?->name ?? '-' }}
            @if(in_array($leave->approval_route,['organization','staged'],true))
                <small class="d-block text-muted">مسئول بررسی: {{ $leave->assignedApprover?->name ?? 'نیازمند تعیین مسئول' }}</small>
            @endif
        </td>
        <td data-label="درخواست">
            <strong>{{ $leave->leave_type }}</strong>
            <span class="leave-unit">{{ $leave->leave_unit }}</span>
            <small class="d-block text-muted mt-1">جایگزین: {{ $leave->substituteUser?->name ?? '-' }}</small>
        </td>
        <td data-label="بازه مرخصی">
            <div class="leave-date"><span>از</span> <bdi>{{ $startDateTime }}</bdi></div>
            <div class="leave-date"><span>تا</span> <bdi>{{ $endDateTime }}</bdi></div>
            <details class="leave-description"><summary>جزئیات درخواست</summary>
                <p>{{ $leave->reason ?: 'توضیحی ثبت نشده است.' }}</p>
                <small>ثبت: <bdi>{{ $createdAt }}</bdi></small>
            </details>
        </td>
        <td data-label="وضعیت و سابقه">
            @switch($leave->status)
                @case('pending')
                @case('manager_approved')
                @case('internal_approved')
                @case('accounting_approved')
                @case('pending_manager')
                @case('pending_internal')
                @case('pending_account')
                    <span class="badge bg-warning">در انتظار بررسی</span>
                    @break

                @case('final_rejected')
                    <span class="badge bg-danger">رد شده</span>
                    @break

                @case('final_approved')
                    <span class="badge bg-success">تأیید نهایی</span>
                    @break

                @case('manager_rejected')
                    <span class="badge bg-danger">رد توسط جایگزین</span>
                    @break

                @case('internal_rejected')
                    <span class="badge bg-danger">رد توسط مدیر واحد</span>
                    @break

                @case('accounting_rejected')
                    <span class="badge bg-danger">رد توسط مدیر داخلی</span>
                    @break

                @default
                    <span class="badge bg-secondary">{{ $leave->status }}</span>
            @endswitch
            @if($leave->approval_route==='staged')
                @include('Leaves.workflow',['leave'=>$leave])
            @endif

        </td>

        <td data-label="بررسی درخواست">
            @can('delete',$leave)
                <form action="{{ route('leaves.destroy', $leave->id) }}" method="POST" class="d-inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-outline-danger btn-sm">{{ config('organization_flow.enabled',false) ? 'لغو درخواست' : 'حذف' }}</button>
                </form>
            @endcan

            @can('approve', $leave)
                @include('Leaves.monthly-usage', ['leave' => $leave, 'monthlyUsage' => $monthlyUsage])
                <form action="{{ route('leaves.approve', $leave->id) }}" method="POST" class="d-inline">
                    @csrf @method('PATCH')
                    <button class="btn btn-success btn-sm">تأیید</button>
                </form>
                <form action="{{ route('leaves.reject', $leave->id) }}" method="POST" class="d-inline">
                    @csrf @method('PATCH')
                    <button class="btn btn-danger btn-sm">رد</button>
                </form>
            @endcan
            @if(auth()->user()->hasRole('Admin') && $leave->approval_route === 'organization' && in_array($leave->status, \App\Services\LeaveApprovalService::PENDING_STATUSES, true))
                <details class="mt-2" style="min-width:160px;max-width:260px;white-space:normal">
                    <summary>تغییر مسئول بررسی</summary>
                    <form class="mt-2" method="POST" action="{{ route('admin.leaves.reassign', $leave) }}">
                        @csrf @method('PATCH')
                        <label class="small" for="leave-assignee-{{ $leave->id }}">مسئول جدید</label>
                        <select name="assignee_id" id="leave-assignee-{{ $leave->id }}" class="form-select form-select-sm mb-2" required>
                            <option value="">انتخاب مسئول</option>
                            @foreach($leaveAssignees as $person)
                                @if((int) $person->id !== (int) $leave->user_id)
                                    <option value="{{ $person->id }}">{{ $person->name }}</option>
                                @endif
                            @endforeach
                        </select>
                        <label class="small" for="leave-reason-{{ $leave->id }}">دلیل تغییر</label>
                        <textarea name="reason" id="leave-reason-{{ $leave->id }}" class="form-control form-control-sm mb-2" rows="2" required minlength="3" maxlength="1000"></textarea>
                        <button class="btn btn-outline-primary btn-sm" type="submit">ثبت ارجاع</button>
                    </form>
                </details>
            @endif
        </td>
    </tr>
@empty
                     
                            <tr>
                                <td colspan="5" class="text-center text-muted">هیچ مرخصی‌ای ثبت نشده است.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $leaves->links() }}
            </div>
        </div>
    </div>
</x-app-layout>

<link rel="stylesheet" href="{{ asset('lib/jalalidatepicker.min.css') }}">
<script src="{{ asset('lib/jalalidatepicker.min.js') }}"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    if (window.jalaliDatepicker && typeof window.jalaliDatepicker.startWatch === 'function') {
        window.jalaliDatepicker.startWatch({ selector: '.jalali-datepicker', time: false, format: 'YYYY/MM/DD' });
    } else {
        document.querySelectorAll('.jalali-datepicker').forEach(function (input) {
            input.readOnly = false;
            input.placeholder = '۱۴۰۵/۰۱/۰۱';
            input.title = 'تاریخ شمسی را به شکل سال/ماه/روز وارد کنید.';
        });
    }
});
</script>
