<x-layouts.app>
    <x-slot name="header">ایجاد مدیر جدید</x-slot>
    <div class="p-6 bg-white shadow rounded" dir="rtl">
        <form method="POST" action="{{ route('admin.users.storeManager') }}">
            @csrf
            <div>
                <label>نام:</label>
                <input type="text" name="name" class="border p-2 w-full">
            </div>
            <div class="mt-2">
                <label>شماره تلفن:</label>
                <input type="text" name="phone" class="border p-2 w-full">
            </div>
            <div class="mt-2">
                <label>رمز عبور:</label>
                <input type="password" name="password" class="border p-2 w-full">
            </div>
            <div class="mt-2">
                <label>تکرار رمز عبور:</label>
                <input type="password" name="password_confirmation" class="border p-2 w-full">
            </div>
            @include('admin.users.partials.manager-select', ['selectedManagerId' => old('manager_id')])
            @include('admin.users.partials.role-selector', ['selectedRoles' => ['Manager']])
            <button type="submit" class="mt-4 bg-blue-600 text-white px-4 py-2 rounded">ذخیره</button>
        </form>
    </div>
</x-layouts.app>
