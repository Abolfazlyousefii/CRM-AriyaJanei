<x-layouts.app>
    <x-slot name="header">ویرایش کارمند</x-slot>

    <div class="p-6 bg-white shadow rounded" dir="rtl">
        <form method="POST" action="{{ route('admin.users.updateEmployee',$employee->id) }}">
            @csrf @method('PUT')

            <div>
                <label>نام:</label>
                <input type="text" name="name" value="{{ $employee->name }}" class="border p-2 w-full">
            </div>

            <div class="mt-2">
                <label>شماره تلفن:</label>
                <input type="text" name="phone" value="{{ $employee->phone }}" class="border p-2 w-full">
            </div>

            @include('admin.users.partials.manager-select', ['selectedManagerId' => old('manager_id', $employee->manager_id)])
            @include('admin.users.partials.department-select', ['selectedDepartmentId' => $employee->department_id])
            @include('admin.users.partials.role-selector', ['selectedRoles' => $employee->getRoleNames()])

            <button type="submit" class="mt-4 bg-green-600 text-white px-4 py-2 rounded">بروزرسانی</button>
        </form>
    </div>
</x-layouts.app>
