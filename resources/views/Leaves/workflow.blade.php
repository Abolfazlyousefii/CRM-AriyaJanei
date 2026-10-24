@php
    $stageLabels=['substitute'=>'جایگزین','unit'=>'مسئول واحد','internal'=>'مدیر داخلی','executive'=>'مدیر اجرایی'];
    $statusLabels=['waiting'=>'در نوبت','pending'=>'منتظر اقدام','approved'=>'تأیید شد','rejected'=>'رد شد','cancelled'=>'متوقف شد'];
@endphp
<details class="text-end my-2" style="min-width:190px;max-width:300px;white-space:normal">
    <summary>مسیر و سابقهٔ تأیید</summary>
    <ol class="ps-0 pe-3 my-2">
        @foreach($leave->steps()->with('approver')->get() as $step)
            <li class="mb-2"><strong>{{ $stageLabels[$step->stage] }}</strong> — {{ $step->approver?->name }}<br>
                <small>{{ $statusLabels[$step->status] ?? $step->status }}</small>
            </li>
        @endforeach
    </ol>
    @foreach($leave->events()->with(['actor','principal'])->get() as $event)
        <p class="small border-top pt-2 mb-1">{{ verta($event->created_at)->format('Y/m/d H:i') }} —
            {{ $event->principal?->name ?? $event->actor?->name }}:
            {{ ['submitted'=>'ثبت درخواست','approved'=>'تأیید','rejected'=>'رد','cancelled'=>'لغو'][$event->action] ?? $event->action }}
            @if($event->method==='sms_code')<br>با کد پیامکی مدیر اجرایی، ثبت توسط {{ $event->actor?->name }}@endif
            @if($event->reason)<br>{{ $event->reason }}@endif
        </p>
    @endforeach
</details>
@if(app(\App\Services\ExecutiveLeaveCodeService::class)->canDelegate(auth()->user(),$leave))
    <div class="border rounded p-2 text-end" style="min-width:190px;max-width:300px;white-space:normal">
        <p class="small">در نبود اینترنت مدیر اجرایی، کد را به شمارهٔ خود ایشان ارسال کنید و پس از دریافت از ایشان وارد کنید.</p>
        <form method="POST" action="{{ route('leaves.executive-code.request',$leave) }}">@csrf
            <button class="btn btn-outline-primary btn-sm" type="submit">ارسال کد به مدیر اجرایی</button>
        </form>
        <form method="POST" action="{{ route('leaves.executive-code.verify',$leave) }}" class="mt-2">@csrf
            <label class="small" for="leave-code-{{ $leave->id }}">کد چهاررقمی</label>
            <input id="leave-code-{{ $leave->id }}" name="code" class="form-control" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]{4}" minlength="4" maxlength="4" required dir="ltr">
            <button class="btn btn-primary btn-sm mt-2" type="submit">ثبت تأیید مدیر اجرایی با کد</button>
        </form>
    </div>
@endif
