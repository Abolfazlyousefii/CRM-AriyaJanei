<x-layouts.app>
    <x-slot name="header">
        افزودن کاربر جدید
    </x-slot>

    <div class="p-6 bg-white shadow rounded" dir="rtl">
        <form method="POST" action="{{ route('admin.users.store') }}">
            @csrf
            <div class="mt-2">
                <label>نام:</label>
                <input type="text" name="name" value="{{ old('name') }}" class="border p-2 w-full" required>
                @error('name') <p class="text-red-600 text-sm">{{ $message }}</p> @enderror
            </div>

            <div class="mt-2">
                <label>شماره تلفن:</label>
                <input type="text" name="phone" value="{{ old('phone') }}" class="border p-2 w-full" required>
                @error('phone') <p class="text-red-600 text-sm">{{ $message }}</p> @enderror
            </div>

            <div class="mt-2">
                <label>رمز عبور:</label>
                <input type="password" name="password" class="border p-2 w-full" required>
                @error('password') <p class="text-red-600 text-sm">{{ $message }}</p> @enderror
            </div>

            <div class="mt-2">
                <label>تکرار رمز عبور:</label>
                <input type="password" name="password_confirmation" class="border p-2 w-full" required>
            </div>

            @include('admin.users.partials.manager-select', ['selectedManagerId' => old('manager_id')])
            @include('admin.users.partials.role-selector', ['selectedRoles' => $selectedRoles])

            <button type="submit" class="mt-4 bg-green-600 text-white px-4 py-2 rounded">
                ذخیره کاربر
            </button>
        </form>
    </div>
</x-layouts.app>
