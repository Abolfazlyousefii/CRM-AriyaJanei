<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight" dir="rtl">فرم‌های رضایت مشتری</h2>
    </x-slot>

    <div class="py-6 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 dash-wrap" dir="rtl">
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        @if(auth()->user()->hasRole('customer_review'))
            <div class="mb-4">
                <a href="{{ route('customer-satisfaction-forms.create') }}" class="btn btn-primary">+ فرم رضایت مشتری جدید</a>
            </div>
        @endif

        <div class="bg-white card-soft shadow-sm rounded-lg p-4">
            <div class="table-responsive">
                <table class="table table-bordered table-striped align-middle satisfaction-table">
                    <thead class="table-light satisfaction-table-head">
                    <tr class="text-dark">
                        <th>#</th>
                        <th>تاریخ فرم</th>
                        <th>تاریخ ارسال بار</th>
                        <th>مشتری</th>
                                                <th>رضایت</th>
                        <th>ارجاع به</th>
                        <th>ثبت‌کننده</th>
                        <th>نتیجه</th>
                        <th>عملیات</th>
                    </tr>
                    </thead>
                    <tbody>
@forelse($groupedForms as $date => $dayForms)

    {{-- ردیف جداکننده/تیتر تاریخ --}}
    <tr style="background-color: #6d6d6d;">
        <td colspan="9" class="fw-bold">
            تاریخ فرم: {{ $date }}
        </td>
    </tr>

    @foreach($dayForms as $form)
        <tr>
            <td>{{ $form->id }}</td>
            <td>{{ \Hekmatinasser\Verta\Verta::instance($form->submitted_at)->format('Y/m/d') }}</td>
            <td>{{ $form->shipment_sent_at ? \Hekmatinasser\Verta\Verta::instance($form->shipment_sent_at)->format('Y/m/d') : '—' }}</td>
            <td>{{ $form->customer_full_name }}</td>
            <td>
                @if($form->satisfaction_status === 'satisfied')
                    <span class="badge bg-success">راضی</span>
                @elseif($form->satisfaction_status === 'ussatisfied')
                    <span class="badge bg-danger">ناراضی</span>
                @else
                 <span class="badge bg-danger">...</span>
                 @endif
            </td>
            <td>{{ optional($form->assignedToUser)->name ?? '—' }}</td>
            <td>{{ optional($form->createdByUser)->name ?? '—' }}</td>
            <td>{{ $form->result ? 'ثبت شده' : 'ثبت نشده' }}</td>
            <td class="d-flex gap-2">
                <a href="{{ route('customer-satisfaction-forms.show', $form) }}" class="btn btn-sm btn-outline-primary">مشاهده</a>

                @if(auth()->id() === $form->created_by_user_id)
                    <form action="{{ route('customer-satisfaction-forms.destroy', $form) }}" method="POST" onsubmit="return confirm('از حذف این فرم مطمئن هستید؟');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-outline-danger">حذف</button>
                    </form>
                @endif
            </td>
        </tr>
    @endforeach

@empty
    <tr>
        <td colspan="9" class="text-center text-muted">فرمی ثبت نشده است.</td>
    </tr>
@endforelse
</tbody>

                </table>
            </div>

            {{ $forms->links() }}
        </div>
    </div>


<style>
    .satisfaction-table-head th {
        font-weight: 700;
        white-space: nowrap;
    }

    [data-bs-theme="dark"] .satisfaction-table-head th {
        background-color: rgba(148,163,184,.14) !important;
        color: var(--text) !important;
    }

    [data-bs-theme="dark"] .satisfaction-date-row > td {
        background-color: rgba(59,130,246,.16) !important;
        color: #dbeafe !important;
        border-color: var(--border) !important;
    }
</style>

</x-app-layout>
