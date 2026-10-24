<x-layouts.app>
    <x-slot name="header"><h1 class="h4">مدیران اصلی چارت</h1></x-slot>
    <div class="card mx-auto my-4" style="max-width:640px" dir="rtl"><div class="card-body">
        <a href="{{ route('admin.users.index') }}">بازگشت به چارت</a>
        <p class="mt-3">مدیر اجرایی در رأس چارت است؛ مدیر داخلی زیر نظر او قرار می‌گیرد و واحدها به مدیریت داخلی متصل می‌شوند.</p>
        @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
        @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
        <form method="POST" action="{{ route('admin.organization.workflow-settings.update') }}">@csrf @method('PATCH')
            @foreach(['executive_manager_id'=>'مدیر اجرایی','internal_manager_id'=>'مدیر داخلی'] as $field=>$label)
                <label class="form-label mt-2" for="{{ $field }}">{{ $label }}</label>
                <select class="form-select" id="{{ $field }}" name="{{ $field }}" required>
                    <option value="">انتخاب شخص</option>
                    @foreach($people as $person)
                        @if(!$person->isBlocked())<option value="{{ $person->id }}" @selected(old($field,$setting?->{$field})==$person->id)>{{ $person->name }}</option>@endif
                    @endforeach
                </select>
            @endforeach
            <p class="small text-muted mt-3">این تنظیم، مسیر سازمانی را مشخص می‌کند و دسترسی ادمین یا نقش نرم‌افزاری جدیدی نمی‌دهد.</p>
            <button class="btn btn-primary mt-3" type="submit">ذخیره مدیران چارت</button>
        </form>
    </div></div>
</x-layouts.app>
