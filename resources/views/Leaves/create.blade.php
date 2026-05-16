@php
    $leave = $leave ?? null;
    $action = $action ?? '#';
    $method = $method ?? 'POST';
    $submitText = $submitText ?? 'ثبت مرخصی';

    $selectedLeaveType = old('leave_type', $leave?->leave_type);
    $selectedLeaveUnit = old('leave_unit', $leave?->leave_unit ?? 'روزانه');

    $startDateValue = old('start_date', isset($leave?->start_date) ? \Hekmatinasser\Verta\Verta::instance($leave->start_date)->format('Y/m/d') : '');
    $endDateValue   = old('end_date', isset($leave?->end_date) ? \Hekmatinasser\Verta\Verta::instance($leave->end_date)->format('Y/m/d') : '');

    $startTimeValue = old('start_time', $leave?->start_time ? substr($leave->start_time, 0, 5) : '');
    $endTimeValue   = old('end_time', $leave?->end_time ? substr($leave->end_time, 0, 5) : '');
@endphp
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight" dir="rtl">
            ثبت مرخصی جدید
        </h2>
    </x-slot>
    <div class="bg-white shadow-sm rounded-lg p-6" dir="rtl">
        <form action="{{ route('leaves.store') }}" method="POST">
            @csrf
            @if(strtoupper($method) !== 'POST')
                @method($method)
            @endif

            @if(session('success'))
                <div class="mb-4 rounded-md bg-green-100 text-green-700 p-3 text-center">
                    {{ session('success') }}
                </div>
            @endif

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label for="leave_type" class="block mb-2 font-medium text-gray-700">نوع مرخصی</label>
                    <select name="leave_type" id="leave_type" class="w-full border-gray-300 rounded-md">
                        <option value="">انتخاب کنید</option>
                        <option value="اضطراری" @selected($selectedLeaveType === 'اضطراری')>اضطراری</option>
                        <option value="استعلاجی" @selected($selectedLeaveType === 'استعلاجی')>استعلاجی</option>
                        <option value="استحقاقی" @selected($selectedLeaveType === 'استحقاقی')>استحقاقی</option>
                    </select>
                    @error('leave_type')
                        <span class="text-red-600 text-sm">{{ $message }}</span>
                    @enderror
                </div>

                <div>
                    <label for="leave_unit" class="block mb-2 font-medium text-gray-700">نوع ثبت مرخصی</label>
                    <select name="leave_unit" id="leave_unit" class="w-full border-gray-300 rounded-md">
                        <option value="روزانه" @selected($selectedLeaveUnit === 'روزانه')>روزانه</option>
                        <option value="ساعتی" @selected($selectedLeaveUnit === 'ساعتی')>ساعتی</option>
                    </select>
                    @error('leave_unit')
                        <span class="text-red-600 text-sm">{{ $message }}</span>
                    @enderror
                </div>
            </div>

            <div class="mb-4">
                <label for="substitute_user_id" class="block mb-2 font-medium text-gray-700">فرد جایگزین (از واحد شما)</label>
                <select name="substitute_user_id" id="substitute_user_id" class="w-full border-gray-300 rounded-md">
                    <option value="">بدون جایگزین</option>
                    @foreach($substitutes as $substitute)
                        <option value="{{ $substitute->id }}" @selected(old('substitute_user_id', $leave?->substitute_user_id) == $substitute->id)>
                            {{ $substitute->name }}
                        </option>
                    @endforeach
                </select>
                @error('substitute_user_id')
                    <span class="text-red-600 text-sm">{{ $message }}</span>
                @enderror
            </div>

            <div id="daily-limit-message" class="mb-4 rounded-md bg-yellow-50 text-yellow-700 p-3 text-sm">
                در مرخصی روزانه، انتخاب امروز و فردا مجاز نیست.
            </div>

            <div id="daily-date-error" class="hidden mb-4 rounded-md bg-red-100 text-red-700 p-3 text-sm"></div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label for="start_date" class="block mb-2 font-medium text-gray-700">تاریخ شروع</label>
                    <input
                        data-jdp
                        type="text"
                        id="start_date"
                        name="start_date"
                        autocomplete="off"
                        readonly
                        class="w-full border-gray-300 rounded-md"
                        value="{{ $startDateValue }}"
                    >
                    @error('start_date')
                        <span class="text-red-600 text-sm">{{ $message }}</span>
                    @enderror
                </div>

                <div>
                    <label for="end_date" class="block mb-2 font-medium text-gray-700">تاریخ پایان</label>
                    <input
                        data-jdp
                        type="text"
                        id="end_date"
                        name="end_date"
                        autocomplete="off"
                        readonly
                        class="w-full border-gray-300 rounded-md"
                        value="{{ $endDateValue }}"
                    >
                    @error('end_date')
                        <span class="text-red-600 text-sm">{{ $message }}</span>
                    @enderror
                </div>
            </div>

            <div id="time-fields" class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label for="start_time" class="block mb-2 font-medium text-gray-700">ساعت شروع</label>
                    <input type="text" name="start_time" id="start_time" class="w-full border-gray-300 rounded-md" value="{{ $startTimeValue }}">
                    @error('start_time')
                        <span class="text-red-600 text-sm">{{ $message }}</span>
                    @enderror
                </div>

                <div>
                    <label for="end_time" class="block mb-2 font-medium text-gray-700">ساعت پایان</label>
                    <input type="text" name="end_time" id="end_time" class="w-full border-gray-300 rounded-md" value="{{ $endTimeValue }}">
                    @error('end_time')
                        <span class="text-red-600 text-sm">{{ $message }}</span>
                    @enderror
                </div>
            </div>

            <div class="mb-4">
                <label for="reason" class="block mb-2 font-medium text-gray-700">توضیحات</label>
                <textarea name="reason" id="reason" rows="4" class="w-full border-gray-300 rounded-md">{{ old('reason', $leave?->reason) }}</textarea>
                @error('reason')
                    <span class="text-red-600 text-sm">{{ $message }}</span>
                @enderror
            </div>

            <div class="flex justify-start gap-4 mt-6">
                <a href="{{ route('leaves') }}" class="px-4 py-2 bg-gray-500 text-white rounded-md hover:bg-gray-600">بازگشت</a>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">{{ $submitText }}</button>
            </div>
        </form>
    </div>
</x-app-layout>

<link rel="stylesheet" href="{{ asset('lib/flatpickr.min.css') }}">
<link rel="stylesheet" href="{{ asset('lib/jalalidatepicker.min.css') }}">

<script src="{{ asset('lib/flatpickr.min.js') }}"></script>
<script src="{{ asset('lib/jalalidatepicker.min.js') }}"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const leaveUnit = document.getElementById('leave_unit');
    const timeFields = document.getElementById('time-fields');
    const startTime = document.getElementById('start_time');
    const endTime = document.getElementById('end_time');
    const startDate = document.getElementById('start_date');
    const endDate = document.getElementById('end_date');
    const form = document.getElementById('leave-form');
    const dailyMessage = document.getElementById('daily-limit-message');
    const dailyError = document.getElementById('daily-date-error');

    flatpickr("#start_time", {
        enableTime: true,
        noCalendar: true,
        dateFormat: "H:i",
        time_24hr: true
    });

    flatpickr("#end_time", {
        enableTime: true,
        noCalendar: true,
        dateFormat: "H:i",
        time_24hr: true
    });

    function toEnglishDigits(str) {
        if (!str) return '';
        const persian = ['۰','۱','۲','۳','۴','۵','۶','۷','۸','۹'];
        const arabic  = ['٠','١','٢','٣','٤','٥','٦','٧','٨','٩'];

        return String(str)
            .replace(/[۰-۹]/g, d => persian.indexOf(d))
            .replace(/[٠-٩]/g, d => arabic.indexOf(d));
    }

    function normalizeJalaliDate(value) {
        const clean = toEnglishDigits(value).replace(/-/g, '/').trim();
        const parts = clean.split('/');

        if (parts.length !== 3) return '';

        const year = parts[0];
        const month = String(parts[1]).padStart(2, '0');
        const day = String(parts[2]).padStart(2, '0');

        return `${year}/${month}/${day}`;
    }

    function getPersianDateAfter(days) {
        const date = new Date();
        date.setHours(0, 0, 0, 0);
        date.setDate(date.getDate() + days);

        const parts = new Intl.DateTimeFormat('en-US-u-ca-persian', {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit'
        }).formatToParts(date);

        const year = parts.find(p => p.type === 'year')?.value;
        const month = parts.find(p => p.type === 'month')?.value;
        const day = parts.find(p => p.type === 'day')?.value;

        return `${year}/${month}/${day}`;
    }

    function toggleLeaveUnitFields() {
        const isDaily = leaveUnit.value === 'روزانه';

        if (isDaily) {
            timeFields.classList.remove('hidden');
            startTime.disabled = false;
            endTime.disabled = false;
            dailyMessage.classList.add('hidden');

            const minDate = getPersianDateAfter(2);
            startDate.setAttribute('data-jdp-min-date', minDate);
            endDate.setAttribute('data-jdp-min-date', minDate);
        } else {
            timeFields.classList.remove('hidden');
            startTime.disabled = false;
            endTime.disabled = false;
            dailyMessage.classList.add('hidden');
            startDate.removeAttribute('data-jdp-min-date');
            endDate.removeAttribute('data-jdp-min-date');
        }

        dailyError.classList.add('hidden');
        dailyError.innerText = '';
    }

    jalaliDatepicker.startWatch({
        selector: '[data-jdp]',
        time: false,
        format: 'YYYY/MM/DD'
    });

    toggleLeaveUnitFields();

    leaveUnit.addEventListener('change', function () {
        toggleLeaveUnitFields();
    });

    form.addEventListener('submit', function (e) {
        dailyError.classList.add('hidden');
        dailyError.innerText = '';

        if (leaveUnit.value === 'روزانه') {
            const minDate = getPersianDateAfter(2);
            const startVal = normalizeJalaliDate(startDate.value);
            const endVal = normalizeJalaliDate(endDate.value);

            if (startVal && startVal < minDate) {
                e.preventDefault();
                dailyError.innerText = 'برای مرخصی روزانه، تاریخ شروع نمی‌تواند امروز یا فردا باشد.';
                dailyError.classList.remove('hidden');
                return;
            }

            if (endVal && endVal < minDate) {
                e.preventDefault();
                dailyError.innerText = 'برای مرخصی روزانه، تاریخ پایان نمی‌تواند امروز یا فردا باشد.';
                dailyError.classList.remove('hidden');
                return;
            }
        }
    });
});
</script>