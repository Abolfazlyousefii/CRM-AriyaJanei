@if($report->reviewWorkflow)
    @php
        $review = $report->reviewWorkflow->loadMissing(['steps', 'events.actor', 'currentReviewer']);
        $statusText = ['pending' => 'در انتظار بررسی', 'needs_revision' => 'نیازمند اصلاح', 'approved' => 'تأیید نهایی'];
        $actionText = ['submitted' => 'ارسال گزارش', 'approved' => 'تأیید مرحله', 'returned' => 'بازگشت برای اصلاح', 'resubmitted' => 'ارسال مجدد', 'reassigned' => 'تغییر مسئول'];
    @endphp
    <details class="card card-body my-3" dir="rtl">
        <summary class="fw-bold">مسیر و تاریخچه بررسی — {{ $statusText[$review->status] ?? $review->status }}</summary>
        <ol class="mt-3">
            @foreach($review->steps->sortBy('sequence') as $step)
                <li>{{ $step->reviewer_name }} — {{ ['pending' => 'مسئول فعلی', 'waiting' => 'در صف', 'returned' => 'در انتظار اصلاح', 'approved' => 'تأیید شده'][$step->decision] ?? $step->decision }}</li>
            @endforeach
        </ol>
        <ul class="small mt-2">
            @foreach($review->events as $event)
                <li class="mb-2">
                    {{ verta($event->created_at)->format('Y/m/d H:i') }} — {{ $event->actor?->name ?? 'حساب آرشیوشده' }} — {{ $actionText[$event->action] ?? $event->action }}
                    @if($event->comment)<p class="mb-1" style="white-space:pre-wrap;overflow-wrap:anywhere">{{ $event->comment }}</p>@endif
                </li>
            @endforeach
        </ul>
        @if(config('report_review.enabled', false) && ! ($hideReviewLink ?? false))
            <a href="{{ route('report-review.inbox') }}" class="btn btn-outline-primary btn-sm align-self-start">ورود به کارتابل بررسی</a>
        @endif
    </details>
@endif
