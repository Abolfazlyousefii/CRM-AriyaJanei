@php
    $currentDepartmentId = old('department_id', $selectedDepartmentId ?? null);
@endphp

<div class="mt-3">
    <label for="department_id" class="block text-sm font-bold mb-2">دپارتمان</label>
    <select name="department_id" id="department_id" class="border rounded p-2 w-full">
        <option value="">بدون دپارتمان</option>
        @foreach($departments as $departmentOption)
            <option value="{{ $departmentOption->id }}" @selected((int) $currentDepartmentId === $departmentOption->id)>
                {{ $departmentOption->name }}
            </option>
        @endforeach
    </select>
    @error('department_id') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
</div>
