@php
    $usage = $monthlyUsage['by_leave'][$leave->id] ?? null;
    $usageFormatter = app(\App\Services\LeaveMonthlyUsageService::class);
    $usagePeriod = $monthlyUsage['period'];
@endphp
@if($usage)
    <div data-monthly-leave-summary="{{ $leave->id }}"
         class="alert {{ $usage['over_limit'] ? 'alert-danger' : 'alert-secondary' }} p-2 mb-2 small text-end"
         style="min-width:210px;max-width:290px;white-space:normal;line-height:1.8"
         @if($usage['over_limit']) role="status" @endif>
        <strong class="d-block">مرخصی ماه جاری · {{ $usagePeriod['label'] }}</strong>
        <small class="d-block">{{ verta($usagePeriod['start'])->format('Y/m/d') }} تا {{ verta($usagePeriod['end']->copy()->subDay())->format('Y/m/d') }}</small>
        <div>تأییدشده: <strong>{{ $usageFormatter->formatMinutes($usage['approved_minutes']) }}</strong></div>
        <small class="d-block">استحقاقی: {{ $usageFormatter->formatMinutes($usage['entitled_minutes']) }} · اضطراری: {{ $usageFormatter->formatMinutes($usage['emergency_minutes']) }}</small>
        <div>با احتساب این درخواست: <strong>{{ $usageFormatter->formatMinutes($usage['projected_minutes']) }}</strong></div>
        @if($usage['over_limit'])
            <strong class="d-block mt-1">هشدار: بیشتر از ۲۰ ساعت در ماه جاری</strong>
        @endif
        <small class="d-block mt-1">هر روز استحقاقی: ۸ ساعت؛ درخواست‌های دیگرِ در انتظار، لغوشده و ردشده محاسبه نمی‌شوند.</small>
    </div>
@endif
