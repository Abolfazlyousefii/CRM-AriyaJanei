<x-layouts.app>
    <x-slot name="header">
        <div class="d-flex align-items-center gap-3">
            <h2 class="fw-semibold fs-4 mb-0">فهرست گزارشات</h2>
            <span>|</span>
            <a href="{{ route('user.reports.create') }}" class="text-decoration-none">
                ایجاد گزارش جدید
            </a>
        </div>
    </x-slot>

    <div class="py-4">
        <div class="container">
            <div class="card shadow-sm border-0" dir="rtl">
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

                    {{-- جدول --}}
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped align-middle text-center">
                            <thead class="table-light">
                            <tr>
                                <th>#</th>
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
                                    <td>{{ $report->title ?? '-' }}</td>
                                    <td>{{ Str::limit($report->description, 50) }}</td>
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

                                        @if($report->created_at->gt(now()->subHours(2)) || $report->reviewWorkflow?->status === 'needs_revision')
                                            
                                       

                                        @can('update', $report)
                                        <a href="{{ route('user.reports.edit', $report) }}" class="btn btn-sm btn-success">ویرایش</a>
                                        @endcan
                                        @can('delete', $report)
                                            <form action="{{ route('user.reports.destroy', $report) }}"
                                                  method="POST" class="d-inline">
                                                @csrf
                                                @method('delete')
                                                <button type="submit" onclick="return confirm('آیا مطمئن هستید؟')"
                                                        class="btn btn-sm btn-danger">
                                                    حذف
                                                </button>
                                            </form>
                                            @endcan
                                             @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-3">
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
