<x-layouts.app>
    <x-slot name="header">
        <div dir="rtl" class="d-flex flex-wrap align-items-center justify-content-between gap-2">
            <h2 class="fs-4 fw-bold mb-0">گزارش‌های منتظر بررسی من</h2>
            <a href="{{ route('user.reports.reportsManagment') }}" class="btn btn-outline-secondary btn-sm">بازگشت به گزارش‌های کاری</a>
        </div>
    </x-slot>

    <section dir="rtl" class="container py-4" id="report-review-inbox" data-csrf="{{ csrf_token() }}">
        <div class="alert alert-info">
            هر گزارش فقط توسط مسئول مرحله فعلی تأیید می‌شود. سرپرست یا مدیر بالادستی صرفاً پس از تأیید مرحله قبلی، مسئول بررسی خواهد شد.
        </div>
        <div id="report-review-feedback" class="alert" role="alert" hidden></div>

        <h3 class="fs-5 fw-bold mb-3">در انتظار تصمیم من ({{ $assigned->total() }})</h3>
        @forelse($assigned as $workflow)
            @php
                $currentStep = $workflow->steps->firstWhere('sequence', $workflow->current_sequence);
            @endphp
            <article class="card shadow-sm mb-3">
                <div class="card-body">
                    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
                        <div>
                            <h4 class="fs-6 fw-bold">{{ $workflow->report->title ?: 'گزارش کار شماره '.$workflow->report_id }}</h4>
                            <p class="text-muted small mb-2">ارسال‌کننده: {{ $workflow->report->user?->name ?? 'حساب آرشیوشده' }}</p>
                        </div>
                        <span class="badge bg-primary">مرحله {{ $workflow->current_sequence }} از {{ $workflow->steps->count() }}</span>
                    </div>
                    <p class="mb-3" style="white-space:pre-wrap">{{ $workflow->report->description }}</p>
                    <details class="mb-3">
                        <summary class="small fw-bold">مسیر تأیید ثبت‌شده</summary>
                        <ol class="mt-2">
                            @foreach($workflow->steps->sortBy('sequence') as $step)
                                <li>
                                    {{ $step->reviewer_name }} —
                                    {{ match ($step->decision) {
                                        'approved' => 'تأیید شده',
                                        'returned' => 'ارجاع برای اصلاح',
                                        'pending' => 'در حال بررسی',
                                        default => 'در صف'
                                    } }}
                                </li>
                            @endforeach
                        </ol>
                    </details>
                    <div class="row g-2">
                        <div class="col-12 col-lg-9">
                            <label class="small fw-semibold mb-1" for="review-comment-{{ $workflow->id }}">نظر مسئول بررسی</label>
                            <textarea id="review-comment-{{ $workflow->id }}" class="form-control" rows="2" maxlength="2000"
                                      placeholder="برای بازگشت به کارمند، دلیل اصلاح را بنویسید." data-comment-for="{{ $workflow->id }}"></textarea>
                        </div>
                        <div class="col-12 col-lg-3 d-flex flex-column justify-content-end gap-2">
                            <button class="btn btn-success btn-sm" type="button"
                                    data-review-action="{{ route('report-review.approve', $workflow) }}"
                                    data-workflow="{{ $workflow->id }}" data-action-kind="approve">
                                تأیید و ارجاع به مرحله بعد
                            </button>
                            <button class="btn btn-outline-danger btn-sm" type="button"
                                    data-review-action="{{ route('report-review.return', $workflow) }}"
                                    data-workflow="{{ $workflow->id }}" data-action-kind="return">
                                بازگشت برای اصلاح
                            </button>
                        </div>
                    </div>
                    @if($currentStep?->comment)
                        <p class="small text-muted mt-2">یادداشت مرحله: {{ $currentStep->comment }}</p>
                    @endif
                </div>
            </article>
        @empty
            <div class="card card-body text-muted mb-4">در حال حاضر گزارشی برای تأیید شما وجود ندارد.</div>
        @endforelse
        {{ $assigned->links() }}

        <h3 class="fs-5 fw-bold mt-5 mb-3">گزارش‌های من که نیاز به اصلاح دارند ({{ $returned->total() }})</h3>
        @forelse($returned as $workflow)
            <article class="card card-body mb-3">
                <h4 class="fs-6 fw-bold">{{ $workflow->report->title ?: 'گزارش کار شماره '.$workflow->report_id }}</h4>
                @php($reviewStep = $workflow->steps->firstWhere('sequence', $workflow->current_sequence))
                <p class="small">نظر مسئول بررسی: {{ $reviewStep?->comment ?: 'توضیحی ثبت نشده است.' }}</p>
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('user.reports.edit', $workflow->report) }}" class="btn btn-outline-primary btn-sm">ویرایش متن گزارش</a>
                    <button type="button" class="btn btn-primary btn-sm"
                            data-review-action="{{ route('report-review.resubmit', $workflow) }}"
                            data-workflow="{{ $workflow->id }}" data-action-kind="resubmit">ارسال مجدد پس از اصلاح</button>
                </div>
            </article>
        @empty
            <p class="text-muted small">گزارش برگشتی برای شما وجود ندارد.</p>
        @endforelse
        {{ $returned->links() }}
    </section>
    <script src="{{ asset('js/report-review.js') }}" defer></script>
</x-layouts.app>
