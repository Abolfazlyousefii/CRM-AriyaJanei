@php
    $currentManagerId = old('manager_id', $selectedManagerId ?? null);
@endphp

<div class="mt-3">
    <label for="manager_id" class="block text-sm font-bold mb-2">مدیر مستقیم</label>
    <select name="manager_id" id="manager_id" class="border rounded p-2 w-full">
        @unless($required ?? false)
            <option value="">بدون مدیر مستقیم</option>
        @endunless
        @foreach($managers as $managerOption)
            <option value="{{ $managerOption->id }}" @selected((int) $currentManagerId === $managerOption->id)>
                {{ $managerOption->name }} — {{ $managerOption->getRoleNames()->join('، ') }}
            </option>
        @endforeach
    </select>
    @error('manager_id') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
</div>
