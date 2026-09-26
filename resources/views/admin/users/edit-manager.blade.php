<x-layouts.app>
    <x-slot name="header">ویرایش مدیر</x-slot>

    <div class="p-6 bg-white shadow rounded" dir="rtl">
        <form method="POST" action="{{ route('admin.users.updateManager',$manager->id) }}">
            @csrf @method('PUT')

            <div>
                <label>نام:</label>
                <input type="text" name="name" value="{{ $manager->name }}" class="border p-2 w-full">
            </div>

            <div class="mt-2">
                <label>شماره تلفن:</label>
                <input type="text" name="phone" value="{{ $manager->phone }}" class="border p-2 w-full">
            </div>

            @include('admin.users.partials.manager-select', ['selectedManagerId' => old('manager_id', $manager->manager_id)])
            @include('admin.users.partials.department-select', ['selectedDepartmentId' => $manager->department_id])
            @include('admin.users.partials.role-selector', ['selectedRoles' => $manager->getRoleNames()])

            <button type="submit" class="mt-4 bg-blue-600 text-white px-4 py-2 rounded">بروزرسانی</button>
        </form>
    </div>
</x-layouts.app>
