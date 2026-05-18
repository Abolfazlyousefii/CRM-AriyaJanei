<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight" dir="rtl">جزئیات فرم رضایت مشتری</h2>
    </x-slot>

    <div class="py-6 max-w-4xl mx-auto px-4 sm:px-6 lg:px-8" dir="rtl">
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <div class="bg-white shadow-sm rounded-lg p-6 mb-4">
            <table class="table table-bordered align-middle">
                <tbody>
                <tr>
                    <th>تاریخ</th>
                    <td>{{ \Hekmatinasser\Verta\Verta::instance($form->submitted_at)->format('Y/m/d') }}</td>
                </tr>
                <tr>
                    <th>تاریخ ارسال بار</th>
                    <td>{{ $form->shipment_sent_at ? \Hekmatinasser\Verta\Verta::instance($form->shipment_sent_at)->format('Y/m/d') : '—' }}</td>
                </tr>
                <tr>
                    <th>نام و نام خانوادگی مشتری</th>
                    <td>{{ $form->customer_full_name }}</td>
                </tr>
                <tr>
                    <th>وضعیت رضایت</th>
                    <td>{{ $form->satisfaction_status === 'satisfied' ? 'راضی' : ($form->satisfaction_status === 'unsatisfied' ? 'ناراضی' : '—') }}</td>
                </tr>

                <tr>
                    <th>ارتباط با اپراتور</th>
                    <td>{{ $form->operator_communication_score ? $form->operator_communication_score . ' از 5' : '—' }}</td>
                </tr>
                <tr>
                    <th>ارسال بار</th>
                    <td>{{ $form->shipment_score ? $form->shipment_score . ' از 5' : '—' }}</td>
                </tr>
                <tr>
                    <th>کیفیت محصول</th>
                    <td>{{ $form->product_quality_score ? $form->product_quality_score . ' از 5' : '—' }}</td>
                </tr>
                <tr>
                    <th>نیاز به مشاوره</th>
                    <td>{{ $form->needs_consultation === 'yes' ? 'دارد' : ($form->needs_consultation === 'no' ? 'ندارد' : '—') }}</td>
                </tr>
                <tr>
                    <th>تمایل به خرید حضوری</th>
                    <td>{{ $form->wants_in_person_purchase === 'yes' ? 'دارد' : ($form->wants_in_person_purchase === 'no' ? 'ندارد' : '—') }}</td>
                </tr>

                <tr>
                    <th>ثبت‌کننده</th>
                    <td>{{ optional($form->createdByUser)->name ?? '—' }}</td>
                </tr>
                <tr>
                    <th>ارجاع به</th>
                    <td>{{ optional($form->assignedToUser)->name ?? '—' }}</td>
                </tr>
                <tr>
                    <th>توضیحات</th>
                    <td>{{ $form->description ?? '—' }}</td>
                </tr>
                <tr>
                    <th>نتیجه ثبت‌شده</th>
                    <td>{{ $form->result ?? 'هنوز ثبت نشده است.' }}</td>
                </tr>
                </tbody>
            </table>
        </div>

        @if(auth()->id() === $form->assigned_to_user_id)
            <div class="bg-white shadow-sm rounded-lg p-6 mb-4">
                <h3 class="text-lg mb-3">ثبت نتیجه توسط شخص ارجاع‌گرفته</h3>
                <form action="{{ route('customer-satisfaction-forms.submit-result', $form) }}" method="POST">
                    @csrf
                    @method('PATCH')
                    <div class="mb-3">
                        <label class="form-label">نتیجه</label>
                        <textarea name="result" rows="4" class="form-control" required>{{ old('result', $form->result) }}</textarea>
                    </div>
                    <button type="submit" class="btn btn-success">ثبت نتیجه</button>
                </form>
            </div>
        @endif

        <div class="d-flex gap-2">
            @if(auth()->id() === $form->created_by_user_id)
                <form action="{{ route('customer-satisfaction-forms.destroy', $form) }}" method="POST" onsubmit="return confirm('از حذف این فرم مطمئن هستید؟');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">حذف فرم</button>
                </form>
            @endif
            <a href="{{ route('customer-satisfaction-forms.index') }}" class="btn btn-secondary">بازگشت</a>
        </div>
    </div>
</x-app-layout>