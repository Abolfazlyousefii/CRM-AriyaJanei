<x-app-layout>
    <x-slot name="header">تنظیمات مرخصی</x-slot>
    <div class="container py-4" dir="rtl">
        @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
        <div class="card"><div class="card-body">
            <p>درخواست‌های جدیدِ دارای چارت معتبر به مسئول مشخص همان فرد ارسال می‌شوند. افراد این فهرست، درخواست‌های قدیمی یا فاقد مسیر سازمانی را بررسی می‌کنند؛ تصمیم یکی از آنان کافی است. تغییر این فهرست مسئول درخواست‌های سازمانی قبلی را عوض نمی‌کند.</p>
            <form method="POST" action="{{ route('admin.leaves.settings.update') }}">
                @csrf @method('PUT')
                @foreach($users as $person)
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" name="approver_ids[]" id="approver-{{ $person->id }}" value="{{ $person->id }}" @checked(in_array($person->id, old('approver_ids', $selectedIds)))>
                        <label class="form-check-label" for="approver-{{ $person->id }}">{{ $person->name }} — {{ $person->phone }}</label>
                    </div>
                @endforeach
                @foreach($errors->all() as $error) <div class="text-danger">{{ $error }}</div> @endforeach
                <button class="btn btn-primary mt-3">ذخیره تنظیمات</button>
                <a class="btn btn-secondary mt-3" href="{{ route('leaves') }}">بازگشت</a>
            </form>
        </div></div>
    </div>
</x-app-layout>
