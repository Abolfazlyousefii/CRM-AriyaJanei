<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight" dir="rtl">ثبت فرم رضایت مشتری</h2>
    </x-slot>

    @php
        $oldCustomers = old('customers', [[
            'submitted_at' => now()->toDateString(),
            'shipment_sent_at_fa' => '',
            'customer_full_name' => '',
            'shipping_method' => '',
            'satisfaction_status' => '',
            'assigned_to_user_id' => '',
            'referral_note' => '',
            'description' => '',
        ]]);
    @endphp

    <div class="py-6 max-w-4xl mx-auto px-4 sm:px-6 lg:px-8" dir="rtl">
        <div class="bg-white shadow-sm rounded-lg p-6">
            <form action="{{ route('customer-satisfaction-forms.store') }}" method="POST" id="customer-satisfaction-form">
                @csrf

                <div id="customers-container" class="d-flex flex-column gap-3">
                    @foreach($oldCustomers as $index => $customer)
                        <details class="border rounded p-3 customer-card" @if($index === 0) open @endif>
                            <summary class="fw-bold cursor-pointer">مشتری {{ $index + 1 }}</summary>

                            <div class="mt-3">
                                <div class="mb-3 hidden">
                                    <label class="form-label">تاریخ ثبت فرم</label>
                                    <input type="date" name="customers[{{ $index }}][submitted_at]" class="form-control" value="{{ $customer['submitted_at'] ?? now()->toDateString() }}" required>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">تاریخ ارسال بار </label>
                                    <input
                                        type="text"
                                        name="customers[{{ $index }}][shipment_sent_at_fa]"
                                        class="form-control"
                                        data-jdp
                                        autocomplete="off"
                                        placeholder="مثال: 1404/11/21"
                                        value="{{ $customer['shipment_sent_at_fa'] ?? '' }}"
                                        required
                                    >
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">نام و نام خانوادگی مشتری</label>
                                    <input type="text" name="customers[{{ $index }}][customer_full_name]" class="form-control" value="{{ $customer['customer_full_name'] ?? '' }}" required>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">روش ارسال</label>
                                    <select name="customers[{{ $index }}][shipping_method]" class="form-select" required>
                                        <option value="">انتخاب کنید</option>
                                        <option value="barbari" @selected(($customer['shipping_method'] ?? '') === 'barbari')>باربری</option>
                                        <option value="tipax" @selected(($customer['shipping_method'] ?? '') === 'tipax')>تیپاکس</option>
                                        <option value="rahmati" @selected(($customer['shipping_method'] ?? '') === 'rahmati')>رحمتی</option>
                                        <option value="ghafari" @selected(($customer['shipping_method'] ?? '') === 'ghafari')>غفاری</option>
                                        <option value="nadi" @selected(($customer['shipping_method'] ?? '') === 'nadi')>نادی</option>
                                        <option value="hozori" @selected(($customer['shipping_method'] ?? '') === 'hozori')>حضوری</option>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">وضعیت رضایت</label>
                                    <select name="customers[{{ $index }}][satisfaction_status]" class="form-select" >
                                        <option value="">انتخاب کنید</option>
                                        <option value="satisfied" @selected(($customer['satisfaction_status'] ?? '') === 'satisfied')>راضی</option>
                                        <option value="unsatisfied" @selected(($customer['satisfaction_status'] ?? '') === 'unsatisfied')>ناراضی</option>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">ارجاع به کاربر </label>
                                    <select name="customers[{{ $index }}][assigned_to_user_id]" class="form-select" >
                                        <option value="">انتخاب کنید</option>
                                        @foreach($reviewUsers as $reviewUser)
                                            <option value="{{ $reviewUser->id }}" @selected((int) ($customer['assigned_to_user_id'] ?? 0) === $reviewUser->id)>
                                                {{ $reviewUser->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">توضیح ارجاع (اختیاری)</label>
                                    <textarea name="customers[{{ $index }}][referral_note]" class="form-control" rows="3">{{ $customer['referral_note'] ?? '' }}</textarea>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">توضیحات (اختیاری)</label>
                                    <textarea name="customers[{{ $index }}][description]" class="form-control" rows="3">{{ $customer['description'] ?? '' }}</textarea>
                                </div>
                            </div>
                        </details>
                    @endforeach
                </div>

                <div class="d-flex gap-2 mt-3">
                    <button type="button" class="btn btn-outline-primary" id="add-customer-btn">+ افزودن مشتری دیگر</button>
                </div>

                <div class="d-flex gap-2 mt-4">
                    <a href="{{ route('customer-satisfaction-forms.index') }}" class="btn btn-secondary">بازگشت</a>
                    <button type="submit" class="btn btn-primary">ثبت فرم‌ها</button>
                </div>
            </form>
        </div>
    </div>

    <template id="customer-template">
        <details class="border rounded p-3 customer-card" open>
            <summary class="fw-bold cursor-pointer">مشتری __INDEX_DISPLAY__</summary>

            <div class="mt-3">
                <div class="mb-3">
                    <label class="form-label">تاریخ ثبت فرم</label>
                    <input type="date" name="customers[__INDEX__][submitted_at]" class="form-control" value="{{ now()->toDateString() }}" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">تاریخ ارسال بار </label>
                    <input
                        type="text"
                        name="customers[__INDEX__][shipment_sent_at_fa]"
                        class="form-control"
                        data-jdp
                        autocomplete="off"
                        placeholder="مثال: 1404/11/21"
                        required
                    >
                </div>

                <div class="mb-3">
                    <label class="form-label">نام و نام خانوادگی مشتری</label>
                    <input type="text" name="customers[__INDEX__][customer_full_name]" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">روش ارسال</label>
                    <select name="customers[__INDEX__][shipping_method]" class="form-select" required>
                        <option value="">انتخاب کنید</option>
                        <option value="barbari">باربری</option>
                        <option value="tipax">تیپاکس</option>
                        <option value="rahmati">رحمتی</option>
                        <option value="ghafari">غفاری</option>
                        <option value="nadi">نادی</option>
                        <option value="hozori">حضوری</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">وضعیت رضایت</label>
                    <select name="customers[__INDEX__][satisfaction_status]" class="form-select" >
                        <option value="">انتخاب کنید</option>
                        <option value="satisfied">راضی</option>
                        <option value="unsatisfied">ناراضی</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">ارجاع به کاربر </label>
                    <select name="customers[__INDEX__][assigned_to_user_id]" class="form-select" >
                        <option value="">انتخاب کنید</option>
                        @foreach($reviewUsers as $reviewUser)
                            <option value="{{ $reviewUser->id }}">{{ $reviewUser->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">توضیح ارجاع (اختیاری)</label>
                    <textarea name="customers[__INDEX__][referral_note]" class="form-control" rows="3"></textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label">توضیحات (اختیاری)</label>
                    <textarea name="customers[__INDEX__][description]" class="form-control" rows="3"></textarea>
                </div>
            </div>
        </details>
    </template>

    <link rel="stylesheet" href="{{ asset('lib/jalalidatepicker.min.css') }}">
    <script src="{{ asset('lib/jalalidatepicker.min.js') }}"></script>
    <script>
        const container = document.getElementById('customers-container');
        const template = document.getElementById('customer-template');
        const addCustomerBtn = document.getElementById('add-customer-btn');

        addCustomerBtn.addEventListener('click', function () {
            const nextIndex = container.querySelectorAll('.customer-card').length;
            const html = template.innerHTML
                .replaceAll('__INDEX__', nextIndex)
                .replaceAll('__INDEX_DISPLAY__', nextIndex + 1);

            container.insertAdjacentHTML('beforeend', html);
            jalaliDatepicker.startWatch();
        });

        jalaliDatepicker.startWatch();
    </script>
</x-app-layout>