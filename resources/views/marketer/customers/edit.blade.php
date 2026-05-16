<x-layouts.app>
    <x-slot name="header">
        <div class="flex gap-4" dir="rtl">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                ویرایش مشتری: {{ $customer->name }} (شناسه: {{ $customer->display_customer_id }})
            </h2>
            |
            <a href="{{ route('marketer.customers.index') }}">
                <p>بازگشت</p>
            </a>
        </div>
    </x-slot>

    <div class="py-12" dir="rtl">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">

                <form action="{{ route('marketer.customers.update', $customer) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="grid grid-cols-1 gap-6">

                        <!-- نام -->
                        <div>
                            <label class="block text-sm font-medium">نام</label>
                            <input type="text" name="name"
                                   value="{{ old('name', $customer->name) }}"
                                   class="mt-1 w-full border rounded-md text-right">
                        </div>

                        <!-- تلفن -->
                        <div>
                            <label class="block text-sm font-medium">تلفن</label>
                            <input type="text" name="phone"
                                   value="{{ old('phone', $customer->phone) }}"
                                   class="mt-1 w-full border rounded-md text-right">
                        </div>

                        <!-- DISC -->
                        <div>
                            <label class="block text-sm font-medium">DISC</label>
                            <select name="DISC" id="DISC"
                                    class="mt-1 w-full border rounded-md text-right"
                                    onchange="discBadge.innerText=this.value || 'انتخاب نشده'">
                                <option value="">هیچکدام</option>
                                @foreach(['D','I','S','C'] as $item)
                                    <option value="{{ $item }}"
                                        {{ old('DISC', $customer->DISC) == $item ? 'selected' : '' }}>
                                        {{ $item }}
                                    </option>
                                @endforeach
                            </select>

                            <span id="discBadge"
                                  class="inline-block mt-2 px-3 py-1 text-white bg-blue-600 rounded-full">
                                {{ $customer->DISC ?? 'انتخاب نشده' }}
                            </span>
                        </div>

                        <!-- استان -->
                        <div>
                            <label class="block text-sm font-medium">استان</label>
                            <select name="province" id="province"
                                    class="mt-1 w-full border rounded-md">
                                <option>در حال بارگذاری...</option>
                            </select>
                        </div>

                        <!-- شهر -->
                        <div>
                            <label class="block text-sm font-medium">شهر</label>
                            <select name="city" id="city"
                                    class="mt-1 w-full border rounded-md" disabled>
                                <option>ابتدا استان را انتخاب کنید</option>
                            </select>
                        </div>

                        <!-- آدرس -->
                        <div>
                            <label class="block text-sm font-medium">آدرس</label>
                            <textarea name="address"
                                      class="mt-1 w-full border rounded-md text-right">{{ old('address', $customer->address) }}</textarea>
                        </div>

                        <!-- دسته بندی -->
                        <div>
                            <label class="block text-sm font-medium">دسته‌بندی</label>
                            <select name="category_id" class="mt-1 w-full border rounded-md">
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}"
                                        {{ old('category_id', $customer->category_id) == $category->id ? 'selected' : '' }}>
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- منبع -->
                        <div>
                            <label class="block text-sm font-medium">منبع</label>
                            <select name="reference_type_id" class="mt-1 w-full border rounded-md">
                                @foreach($referenceTypes as $ref)
                                    <option value="{{ $ref->id }}"
                                        {{ old('reference_type_id', $customer->reference_type_id) == $ref->id ? 'selected' : '' }}>
                                        {{ $ref->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                    </div>

                    <!-- دکمه ها -->
                    <div class="mt-6 flex justify-end gap-4">
                        <a href="{{ route('marketer.customers.index') }}">
                            <button type="button" class="px-4 py-2 bg-gray-500 text-white rounded">
                                بازگشت
                            </button>
                        </a>

                        <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded">
                            ذخیره تغییرات
                        </button>
                    </div>

                </form>

            </div>
        </div>
    </div>
</x-layouts.app>

<script>
    const oldProvince = @json(old('province', $customer->province));
    const oldCity = @json(old('city', $customer->city));

    document.addEventListener('DOMContentLoaded', async () => {
        const provinceSelect = document.getElementById('province');
        const citySelect = document.getElementById('city');

        let provinces = [];

        const setCities = (provinceName) => {
            citySelect.innerHTML = '<option value="">انتخاب شهر</option>';

            const province = provinces.find(p => p.name === provinceName);
            const cities = province?.cities || [];

            cities.forEach(c => {
                const name = typeof c === 'string' ? c : (c.name || c.city);

                const option = document.createElement('option');
                option.value = name;
                option.textContent = name;

                if (oldCity === name) option.selected = true;

                citySelect.appendChild(option);
            });

            citySelect.disabled = cities.length === 0;
        };

        try {
            const res = await fetch('/data/iran-provinces-cities.json');
            const data = await res.json();
            provinces = data.provinces || [];

            provinceSelect.innerHTML = '<option value="">انتخاب استان</option>';

            provinces.forEach(p => {
                const option = document.createElement('option');
                option.value = p.name;
                option.textContent = p.name;

                if (oldProvince === p.name) option.selected = true;

                provinceSelect.appendChild(option);
            });

            if (oldProvince) setCities(oldProvince);

        } catch (e) {
            provinceSelect.innerHTML = '<option>خطا در دریافت</option>';
        }

        provinceSelect.addEventListener('change', e => {
            setCities(e.target.value);
        });
    });
</script>