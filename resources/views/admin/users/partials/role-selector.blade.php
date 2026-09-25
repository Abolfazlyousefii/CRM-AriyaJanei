@php
    $checkedRoles = collect(old('roles', $selectedRoles ?? []));
@endphp

<fieldset class="mt-3 border rounded p-3">
    <legend class="text-sm font-bold px-2">نقش‌ها و دسترسی‌ها</legend>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2">
        @foreach($roles as $roleOption)
            <label class="flex items-center gap-2 border rounded p-2 cursor-pointer">
                <input type="checkbox" name="roles[]" value="{{ $roleOption->name }}"
                       @checked($checkedRoles->contains($roleOption->name))>
                <span>{{ $roleOption->name }}</span>
            </label>
        @endforeach
    </div>
    @error('roles') <p class="text-red-600 text-sm mt-2">{{ $message }}</p> @enderror
    @error('roles.*') <p class="text-red-600 text-sm mt-2">{{ $message }}</p> @enderror
</fieldset>
