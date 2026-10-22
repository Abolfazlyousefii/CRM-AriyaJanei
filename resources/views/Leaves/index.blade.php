<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight" dir="rtl">
            لیست مرخصی‌ها
        </h2>
    </x-slot>

    <div class="py-6 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8" dir="rtl">
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

        @if(auth()->user()->hasRole('Admin'))
            <a href="{{ route('admin.leaves.settings.edit') }}" class="btn btn-secondary mb-4">تنظیمات مرخصی</a>
            @if(app(\App\Services\LeaveApprovalService::class)->approvers()->isEmpty())
                <div class="alert alert-warning">هنوز تأییدکننده‌ای انتخاب نشده است. از تنظیمات مرخصی افراد مسئول را انتخاب کنید.</div>
            @endif
        @endif

        <a href="{{ route('leaves.create') }}" class="btn btn-primary mb-4">ایجاد مرخصی جدید</a>



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

        <div class="bg-white shadow-sm rounded-lg p-4 sm:p-6">
            <div class="overflow-x-auto">
                <table class="table table-bordered table-striped w-full text-center min-w-[1000px]">
                    <thead>
                        <tr>
                            <th>کارمند</th>
                            <th>جایگزین</th>
                            <th>نوع مرخصی</th>
                            <th>نوع ثبت</th>
                            <th>شروع</th>
                            <th>پایان</th>
                            <th>توضیحات</th>
                            <th>تاریخ ارائه درخواست</th>
                            <th>وضعیت</th>
                            <th>عملیات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $user = Auth::user(); @endphp

                    
                        @forelse($leaves as $leave)
  @php
    $startDate = verta($leave->start_date)->format('Y/m/d');
    $endDate = verta($leave->end_date)->format('Y/m/d');

    $startTime = $leave->start_time
        ? \Carbon\Carbon::parse($leave->start_time)->format('H:i:s')
        : '00:00:00';

    $endTime = $leave->end_time
        ? \Carbon\Carbon::parse($leave->end_time)->format('H:i:s')
        : '00:00:00';

    $startDateTime = $startDate . ' ' . $startTime;
    $endDateTime = $endDate . ' ' . $endTime;

    $createdAt = verta($leave->created_at)->format('Y/m/d H:i:s');
@endphp

    <tr>
        <td class="whitespace-nowrap">
            {{ $leave->user?->name ?? '-' }}
            @if($leave->approval_route === 'organization')
                <small class="d-block text-muted">مسئول بررسی: {{ $leave->assignedApprover?->name ?? 'نیازمند تعیین مسئول' }}</small>
            @endif
        </td>
        <td class="whitespace-nowrap">{{ $leave->substituteUser?->name ?? '-' }}</td>
        <td class="whitespace-nowrap">{{ $leave->leave_type }}</td>
        <td class="whitespace-nowrap">{{ $leave->leave_unit }}</td>

        <td class="whitespace-nowrap">{{ $startDateTime }}</td>
        <td class="whitespace-nowrap">{{ $endDateTime }}</td>

        <td>{{ $leave->reason ?: '-' }}</td>

        <td class="whitespace-nowrap">{{ $createdAt }}</td>

        <td class="whitespace-nowrap">
            @switch($leave->status)
                @case('pending')
                @case('manager_approved')
                @case('internal_approved')
                @case('accounting_approved')
                @case('pending_manager')
                @case('pending_internal')
                @case('pending_account')
                    <span class="badge bg-warning">در انتظار بررسی تأییدکنندگان</span>
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
        </td>

        <td class="whitespace-nowrap">
            @if($leave->user_id === $user->id && in_array($leave->status, \App\Services\LeaveApprovalService::PENDING_STATUSES))
                <form action="{{ route('leaves.destroy', $leave->id) }}" method="POST" class="d-inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-outline-danger btn-sm">حذف</button>
                </form>
            @endif

            @can('approve', $leave)
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
                                <td colspan="10" class="text-center text-muted">هیچ مرخصی‌ای ثبت نشده است.</td>
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
    jalaliDatepicker.startWatch({
        selector: '.jalali-datepicker',
        time: false,
        format: 'YYYY/MM/DD'
    });
});
</script>
