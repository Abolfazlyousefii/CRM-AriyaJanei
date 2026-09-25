<x-layouts.app>
    <x-slot name="header">
        <div class="flex gap-4">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                ایجاد کاربر بازاریاب
            </h2>
            |
            <p>
                <a href="{{ route('admin.marketers.index') }}"
                   class=" hover:underline">
                    بازکشت به لیست بازاریابان
                </a>
            </p>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-md rounded px-8 pt-6 pb-8 mb-4" dir="rtl">
                <form method="POST" action="{{ route('admin.marketers.store') }}">
                    @csrf
                    @method('POST')

                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2 text-right" for="name">
                            نام کامل
                        </label>
                        <input name="name" type="text" value=""
                               class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline text-right"
                               dir="rtl">
                        @error('name')<p class="text-red-500 text-xs mt-1 text-right">{{ $message }}</p>@enderror
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2 text-right" for="phone">
                            شماره موبایل
                        </label>
                        <input name="phone" type="text" value=""
                               class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline text-right"
                               dir="rtl">
                        @error('phone')<p class="text-red-500 text-xs mt-1 text-right">{{ $message }}</p>@enderror
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2 text-right" for="password">
                            رمز عبور
                        </label>
                        <input name="password" type="password"
                               class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline text-right"
                               dir="rtl">
                        @error('password')<p class="text-red-500 text-xs mt-1 text-right">{{ $message }}</p>@enderror
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2 text-right" for="password_confirmation">
                            تکرار رمز عبور
                        </label>
                        <input name="password_confirmation" type="password"
                               class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline text-right"
                               dir="rtl">
                        </div>

                    @include('admin.users.partials.manager-select', [
                        'selectedManagerId' => old('manager_id'),
                    ])

                    <div class="flex items-center gap-4 justify-end">
                        <button class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline"
                                type="submit">
                            ثبت کاربر
                        </button>
                        <a href="{{ route('admin.marketers.index') }}">
                            <button class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline"
                                    type="button">
                                بازگشت
                            </button>
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-layouts.app>
