<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight" dir="rtl">ثبت فرم رضایت مشتری</h2>
    </x-slot>

    @php
        $oldCustomers = old('customers', [[
            'submitted_at' => now()->toDateString(),
            'shipment_sent_at_fa' => '',
            'customer_full_name' => '',
            'satisfaction_status' => '',
            'assigned_to_user_id' => '',
            'description' => '',
            'operator_communication_score' => '',
            'shipment_score' => '',
            'product_quality_score' => '',
            'needs_consultation' => '',
            'wants_in_person_purchase' => '',
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
                                    <input type="date" name="customers[{{ $index }}][submitted_at]" class="form-control" value="{{ $customer['submitted_at'] ?? now()->toDateString() }}">
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
                                       
                                    >
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">نام و نام خانوادگی مشتری</label>
                                    <input type="text" name="customers[{{ $index }}][customer_full_name]" class="form-control" value="{{ $customer['customer_full_name'] ?? '' }}">
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
                                    <label class="form-label">ارتباط با اپراتور (از 5)</label>
                                    <select name="customers[{{ $index }}][operator_communication_score]" class="form-select">
                                        <option value="">انتخاب کنید</option>
                                        @for($i = 1; $i <= 5; $i++)
                                            <option value="{{ $i }}" @selected((string) ($customer['operator_communication_score'] ?? '') === (string) $i)>{{ $i }}</option>
                                        @endfor
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">ارسال بار (از 5)</label>
                                    <select name="customers[{{ $index }}][shipment_score]" class="form-select">
                                        <option value="">انتخاب کنید</option>
                                        @for($i = 1; $i <= 5; $i++)
                                            <option value="{{ $i }}" @selected((string) ($customer['shipment_score'] ?? '') === (string) $i)>{{ $i }}</option>
                                        @endfor
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">کیفیت محصول (از 5)</label>
                                    <select name="customers[{{ $index }}][product_quality_score]" class="form-select">
                                        <option value="">انتخاب کنید</option>
                                        @for($i = 1; $i <= 5; $i++)
                                            <option value="{{ $i }}" @selected((string) ($customer['product_quality_score'] ?? '') === (string) $i)>{{ $i }}</option>
                                        @endfor
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">نیاز به مشاوره</label>
                                    <select name="customers[{{ $index }}][needs_consultation]" class="form-select">
                                        <option value="">انتخاب کنید</option>
                                        <option value="yes" @selected(($customer['needs_consultation'] ?? '') === 'yes')>دارد</option>
                                        <option value="no" @selected(($customer['needs_consultation'] ?? '') === 'no')>ندارد</option>
                                    </select>
                                </div>


                                <div class="mb-3">
                                    <label class="form-label">ارتباط با اپراتور (از 5)</label>
                                    <select name="customers[{{ $index }}][operator_communication_score]" class="form-select">
                                        <option value="">انتخاب کنید</option>
                                        @for($i = 1; $i <= 5; $i++)
                                            <option value="{{ $i }}" @selected((string) ($customer['operator_communication_score'] ?? '') === (string) $i)>{{ $i }}</option>
                                        @endfor
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">ارسال بار (از 5)</label>
                                    <select name="customers[{{ $index }}][shipment_score]" class="form-select">
                                        <option value="">انتخاب کنید</option>
                                        @for($i = 1; $i <= 5; $i++)
                                            <option value="{{ $i }}" @selected((string) ($customer['shipment_score'] ?? '') === (string) $i)>{{ $i }}</option>
                                        @endfor
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">کیفیت محصول (از 5)</label>
                                    <select name="customers[{{ $index }}][product_quality_score]" class="form-select">
                                        <option value="">انتخاب کنید</option>
                                        @for($i = 1; $i <= 5; $i++)
                                            <option value="{{ $i }}" @selected((string) ($customer['product_quality_score'] ?? '') === (string) $i)>{{ $i }}</option>
                                        @endfor
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">نیاز به مشاوره</label>
                                    <select name="customers[{{ $index }}][needs_consultation]" class="form-select">
                                        <option value="">انتخاب کنید</option>
                                        <option value="yes" @selected(($customer['needs_consultation'] ?? '') === 'yes')>دارد</option>
                                        <option value="no" @selected(($customer['needs_consultation'] ?? '') === 'no')>ندارد</option>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">تمایل به خرید حضوری</label>
                                    <select name="customers[{{ $index }}][wants_in_person_purchase]" class="form-select">
                                        <option value="">انتخاب کنید</option>
                                        <option value="yes" @selected(($customer['wants_in_person_purchase'] ?? '') === 'yes')>دارد</option>
                                        <option value="no" @selected(($customer['wants_in_person_purchase'] ?? '') === 'no')>ندارد</option>
                                    </select>
                                </div>


                                <div class="mb-3">
                                    <label class="form-label">تمایل به خرید</label>
                                    <select name="customers[{{ $index }}][wants_in_person_purchase]" class="form-select">
                                        <option value="">انتخاب کنید</option>
                                        <option value="in_person" @selected(($customer['wants_in_person_purchase'] ?? '') === 'in_person')>حضوری</option>
                                        <option value="website" @selected(($customer['wants_in_person_purchase'] ?? '') === 'website')>سایت</option>
                                        <option value="phone" @selected(($customer['wants_in_person_purchase'] ?? '') === 'phone')>تلفنی</option>
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
                    <input type="date" name="customers[__INDEX__][submitted_at]" class="form-control" value="{{ now()->toDateString() }}">
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
                       
                    >
                </div>

                <div class="mb-3">
                    <label class="form-label">نام و نام خانوادگی مشتری</label>
                    <input type="text" name="customers[__INDEX__][customer_full_name]" class="form-control">
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
                    <label class="form-label">ارتباط با اپراتور (از 5)</label>
                    <select name="customers[__INDEX__][operator_communication_score]" class="form-select">
                        <option value="">انتخاب کنید</option>
                        @for($i = 1; $i <= 5; $i++)
                            <option value="{{ $i }}">{{ $i }}</option>
                        @endfor
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">ارسال بار (از 5)</label>
                    <select name="customers[__INDEX__][shipment_score]" class="form-select">
                        <option value="">انتخاب کنید</option>
                        @for($i = 1; $i <= 5; $i++)
                            <option value="{{ $i }}">{{ $i }}</option>
                        @endfor
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">کیفیت محصول (از 5)</label>
                    <select name="customers[__INDEX__][product_quality_score]" class="form-select">
                        <option value="">انتخاب کنید</option>
                        @for($i = 1; $i <= 5; $i++)
                            <option value="{{ $i }}">{{ $i }}</option>
                        @endfor
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">نیاز به مشاوره</label>
                    <select name="customers[__INDEX__][needs_consultation]" class="form-select">
                        <option value="">انتخاب کنید</option>
                        <option value="yes">دارد</option>
                        <option value="no">ندارد</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">تمایل به خرید</label>
                    <select name="customers[__INDEX__][wants_in_person_purchase]" class="form-select">
                        <option value="">انتخاب کنید</option>
                        <option value="in_person">حضوری</option>
                        <option value="website">سایت</option>
                        <option value="phone">تلفنی</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">ارتباط با اپراتور (از 5)</label>
                    <select name="customers[__INDEX__][operator_communication_score]" class="form-select">
                        <option value="">انتخاب کنید</option>
                        @for($i = 1; $i <= 5; $i++)
                            <option value="{{ $i }}">{{ $i }}</option>
                        @endfor
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">ارسال بار (از 5)</label>
                    <select name="customers[__INDEX__][shipment_score]" class="form-select">
                        <option value="">انتخاب کنید</option>
                        @for($i = 1; $i <= 5; $i++)
                            <option value="{{ $i }}">{{ $i }}</option>
                        @endfor
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">کیفیت محصول (از 5)</label>
                    <select name="customers[__INDEX__][product_quality_score]" class="form-select">
                        <option value="">انتخاب کنید</option>
                        @for($i = 1; $i <= 5; $i++)
                            <option value="{{ $i }}">{{ $i }}</option>
                        @endfor
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">نیاز به مشاوره</label>
                    <select name="customers[__INDEX__][needs_consultation]" class="form-select">
                        <option value="">انتخاب کنید</option>
                        <option value="yes">دارد</option>
                        <option value="no">ندارد</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">تمایل به خرید حضوری</label>
                    <select name="customers[__INDEX__][wants_in_person_purchase]" class="form-select">
                        <option value="">انتخاب کنید</option>
                        <option value="yes">دارد</option>
                        <option value="no">ندارد</option>
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