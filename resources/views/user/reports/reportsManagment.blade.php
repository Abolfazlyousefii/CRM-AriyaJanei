<x-layouts.app>
    <x-slot name="header">
        <div class="d-flex align-items-center gap-3">
            <h2 class="fw-semibold fs-4 mb-0">فهرست گزارشات</h2>
            <span>|</span>
        </div>
    </x-slot>

    @if(config('report_review.enabled', false))
        <div class="container mt-3" dir="rtl"><a href="{{ route('report-review.inbox') }}" class="btn btn-primary btn-sm">گزارش‌های منتظر بررسی من</a></div>
    @endif

    @if(isset($usersWithoutYesterdayReport))
        <div class="alert alert-info card-soft mb-3" dir="rtl">
            <div class="fw-bold mb-2">افرادی که دیروز گزارش ارسال نکرده‌اند:</div>

            @if($usersWithoutYesterdayReport->count())
                <div class="d-flex flex-wrap gap-2">
                    @foreach($usersWithoutYesterdayReport as $u)
                        <span class="badge bg-secondary">{{ $u->name }}</span>
                    @endforeach
                </div>
            @else
                <div class="text-success">همه دیروز گزارش ارسال کرده‌اند ✅</div>
            @endif
        </div>
    @endif

    <div class="py-4 dash-wrap">
        <div class="container">
            <div class="card card-soft shadow-sm border-0 bg-white" dir="rtl">
                <div class="card-body">

                    {{-- پیام‌ها --}}
                    @if(session('info'))
                        <div class="alert alert-warning">{{ session('info') }}</div>
                    @endif

                    @if(session('error'))
                        <div class="alert alert-danger">{{ session('error') }}</div>
                    @endif

                    @if(session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif

                    {{-- فیلترها --}}
                    <form action="{{ route('user.reports.reportsManagment') }}" method="GET" class="row g-3 mb-4 align-items-end">
                        <div class="col-md-4">
                            <label for="user_id" class="form-label">شخص کاربر</label>
                            <select name="user_id" id="user_id" class="form-select">
                                <option value="">همه کاربران</option>
                                @foreach($availableUsers ?? [] as $userOption)
                                    <option value="{{ $userOption->id }}" @selected((int) request('user_id') === $userOption->id)>
                                        {{ $userOption->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label for="date_from" class="form-label">از تاریخ</label>
                            <input
                                type="text"
                                name="date_from"
                                id="date_from"
                                class="form-control"
                                data-jdp
                                data-jdp-format="YYYY/MM/DD"
                                autocomplete="off"
                                placeholder="1404/01/01"
                                value="{{ old('date_from', $dateFromJalali ?? '') }}"
                            >
                        </div>

                        <div class="col-md-3">
                            <label for="date_to" class="form-label">تا تاریخ</label>
                            <input
                                type="text"
                                name="date_to"
                                id="date_to"
                                class="form-control"
                                data-jdp
                                data-jdp-format="YYYY/MM/DD"
                                autocomplete="off"
                                placeholder="1404/01/30"
                                value="{{ old('date_to', $dateToJalali ?? '') }}"
                            >
                        </div>

                        <div class="col-md-2 d-flex gap-2">
                            <button type="submit" class="btn btn-primary w-100">اعمال</button>
                            <a href="{{ route('user.reports.reportsManagment') }}" class="btn btn-outline-secondary w-100">حذف</a>
                        </div>
                    </form>

                    {{-- جدول --}}
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped align-middle text-center bg-white">
                            <thead>
                                <tr class="text-dark">
                                    <th class="fw-semibold">#</th>
                                    <th>نویسنده</th>
                                    <th>عنوان</th>
                                    <th>توضیحات</th>
                                    <th>تاریخ ایجاد</th>
                                    <th>تعداد فایل‌ها</th>
                                    <th>وضعیت</th>
                                    <th>عملیات</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($reports as $report)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>{{ $report->user->name ?? '-' }}</td>
                                        <td>{{ $report->title ?? '-' }}</td>
                                        <td>{{ \Illuminate\Support\Str::limit($report->description, 50) }}</td>
                                        <td>{{ \Morilog\Jalali\Jalalian::fromDateTime($report->created_at)->format('Y/m/d') }}</td>
                                        <td>{{ $report->attachments->count() }}</td>
                                        <td>
                                            @if($report->status === \App\Models\Report::STATUS_SUBMITTED)
                                                <span class="badge bg-warning text-dark">خوانده نشده</span>
                                            @elseif($report->status === \App\Models\Report::STATUS_READ)
                                                <span class="badge bg-success">خوانده شده</span>
                                            @else
                                                <span class="badge bg-secondary">پیش‌نویس</span>
                                            @endif
                                        </td>
                                        <td>
                                            <a href="{{ route('user.reports.show', $report) }}" class="btn btn-sm btn-primary">مشاهده</a>
                                            <a href="{{ route('user.reports.edit', $report) }}" class="btn btn-sm btn-success">ویرایش</a>

                                            <form action="{{ route('user.reports.destroy', $report) }}" method="POST" class="d-inline">
                                                @csrf
                                                @method('delete')
                                                <button type="submit" onclick="return confirm('آیا مطمئن هستید؟')" class="btn btn-sm btn-danger">
                                                    حذف
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center text-muted py-3">
                                            گزارشی یافت نشد.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{-- صفحه‌بندی --}}
                    <div class="mt-3">
                        {{ $reports->links() }}
                    </div>

                </div>
            </div>
        </div>
    </div>
</x-layouts.app>

{{-- فقط همین کتابخانه را نگه دار --}}
<link rel="stylesheet" href="{{ asset('lib/persian-datepicker.min.css') }}">
<link rel="stylesheet" href="{{ asset('lib/flatpickr.min.css') }}">
<link rel="stylesheet" href="{{ asset('lib/jalalidatepicker.min.css') }}">

<script src="{{ asset('lib/jquery.min.js') }}"></script>
<script src="{{ asset('lib/persian-date.min.js') }}"></script>
<script src="{{ asset('lib/persian-datepicker.min.js') }}"></script>
<script src="{{ asset('lib/flatpickr.min.js') }}"></script>
<script src="{{ asset('lib/jalalidatepicker.min.js') }}"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        jalaliDatepicker.startWatch();
    });
</script>