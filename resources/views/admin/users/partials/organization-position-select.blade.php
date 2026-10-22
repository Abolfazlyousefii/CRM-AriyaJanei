@php
    $positions = [
        'staff' => 'کارشناس / کارمند',
        'supervisor' => 'سرپرست',
        'department_manager' => 'مدیر واحد',
        'internal_manager' => 'مدیر داخلی',
        'executive_manager' => 'مدیر اجرایی',
        'ceo' => 'مدیرعامل',
    ];
    $selectedPosition = old('org_position', $selectedPosition ?? null);
@endphp

<div class="mt-3">
    <label for="org_position" class="block text-sm font-bold mb-2">سمت سازمانی</label>
    <select name="org_position" id="org_position" class="border rounded p-2 w-full">
        <option value="">بدون سمت مشخص</option>
        @foreach($positions as $value => $title)
            <option value="{{ $value }}" @selected($selectedPosition === $value)>{{ $title }}</option>
        @endforeach
    </select>
    <p class="text-xs text-muted mt-1">سمت سازمانی با نقش دسترسی نرم‌افزار متفاوت است.</p>
    @error('org_position') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
</div>
