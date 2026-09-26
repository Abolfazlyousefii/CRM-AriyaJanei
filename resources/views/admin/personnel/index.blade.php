<x-layouts.app>
    <x-slot name="header">
        @include('admin.users.partials.section-tabs')

        <div class="d-flex flex-wrap gap-3 align-items-center justify-content-between" dir="rtl">
            <h2 class="fw-semibold fs-4 mb-0">پرسنل</h2>
            <a href="{{ route('admin.users.create') }}" class="btn btn-success btn-sm">افزودن کاربر</a>
        </div>
    </x-slot>

    <div class="py-4" dir="rtl">
        <div class="container">
            <div class="card shadow-sm rounded-3">
                <div class="card-body">
                    <form method="GET" action="{{ route('admin.personnel.index') }}" class="row g-2 align-items-end mb-3">
                        <div class="col-12 col-md-4">
                            <label for="filter-role" class="form-label small mb-1">نقش</label>
                            <select name="role" id="filter-role" class="form-select form-select-sm">
                                <option value="">همه‌ی نقش‌ها</option>
                                @foreach($roles as $role)
                                    <option value="{{ $role->name }}" @selected($selectedRole === $role->name)>{{ $role->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12 col-md-4">
                            <label for="filter-department" class="form-label small mb-1">دپارتمان</label>
                            <select name="department_id" id="filter-department" class="form-select form-select-sm">
                                <option value="">همه‌ی دپارتمان‌ها</option>
                                @foreach($departments as $department)
                                    <option value="{{ $department->id }}" @selected($selectedDepartmentId === $department->id)>{{ $department->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12 col-md-4 d-flex gap-2">
                            <button type="submit" class="btn btn-primary btn-sm">اعمال فیلتر</button>
                            @if($selectedRole || $selectedDepartmentId)
                                <a href="{{ route('admin.personnel.index') }}" class="btn btn-outline-secondary btn-sm">حذف فیلتر</a>
                            @endif
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-bordered table-hover table-striped align-middle text-start">
                            <thead class="table-light">
                                <tr>
                                    <th scope="col">نام</th>
                                    <th scope="col">شماره</th>
                                    <th scope="col">نقش‌ها</th>
                                    <th scope="col">دپارتمان</th>
                                    <th scope="col">مدیر مستقیم</th>
                                    <th scope="col">عملیات</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($personnel as $person)
                                    @php
                                        $isManagerial = $person->roles->contains(fn ($role) => \App\Models\User::isManagerialRole($role->name));
                                    @endphp
                                    <tr>
                                        <td>{{ $person->name }}</td>
                                        <td dir="ltr" class="text-end">{{ $person->phone }}</td>
                                        <td>
                                            @forelse($person->roles as $role)
                                                <span class="badge bg-secondary">{{ $role->name }}</span>
                                            @empty
                                                <span class="text-muted">—</span>
                                            @endforelse
                                        </td>
                                        <td>{{ $person->department?->name ?? '—' }}</td>
                                        <td>{{ $person->manager?->name ?? '—' }}</td>
                                        <td>
                                            <a href="{{ $isManagerial ? route('admin.users.editManager', $person) : route('admin.users.editEmployee', $person) }}"
                                               class="btn btn-outline-primary btn-sm">ویرایش</a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted">کاربری یافت نشد.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-3">
                        {{ $personnel->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-layouts.app>
