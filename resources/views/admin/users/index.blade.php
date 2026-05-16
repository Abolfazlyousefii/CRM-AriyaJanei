<x-layouts.app>
    @php
        $sortedManagers = $managers->sortBy(function ($manager) {
            $roleNames = $manager->roles->pluck('name');

            if ($roleNames->contains('Owner')) {
                return 0;
            }

            if ($roleNames->contains('InternalManager')) {
                return 1;
            }

            return 2;
        })->values();

        $ownerManagers = $sortedManagers
            ->filter(fn ($manager) => $manager->roles->contains('name', 'Owner'))
            ->values();

        $internalManagers = $sortedManagers
            ->filter(fn ($manager) =>
                !$manager->roles->contains('name', 'Owner') &&
                $manager->roles->contains('name', 'InternalManager')
            )
            ->values();

        $otherManagers = $sortedManagers
            ->reject(fn ($manager) =>
                $manager->roles->contains('name', 'Owner') ||
                $manager->roles->contains('name', 'InternalManager')
            )
            ->values();

        $managerBadgeMeta = function ($manager) {
            $roleNames = $manager->roles->pluck('name');

            if ($roleNames->contains('Owner')) {
                return [
                    'label' => 'مدیر کل',
                    'class' => 'owner',
                ];
            }

            if ($roleNames->contains('InternalManager')) {
                return [
                    'label' => 'مدیر داخلی',
                    'class' => 'internal',
                ];
            }

            return [
                'label' => 'مدیر',
                'class' => 'default',
            ];
        };

        $otherManagersEmployeesCount = $otherManagers->sum(fn ($manager) => $manager->employees->count());
    @endphp

    <x-slot name="header">
        <div class="users-header">
            <div>
                <h2 class="users-title mb-0">مدیریت کاربران</h2>
                <div class="users-subtitle">نمایش ساختار سازمانی به‌صورت درخت افقی</div>
            </div>

            <a href="{{ route('admin.users.createManager') }}" class="btn btn-primary users-add-btn">
                <span>➕</span>
                <span>ایجاد مدیر جدید</span>
            </a>
        </div>
    </x-slot>

    <div class="container py-4 users-tree-page" dir="rtl">
        @if(session('success'))
            <div class="alert alert-success border-0 shadow-sm rounded-4 mb-4">
                {{ session('success') }}
            </div>
        @endif

        @if($sortedManagers->isEmpty())
            <div class="users-empty">
                <div class="users-empty__icon">🌳</div>
                <h4 class="fw-bold mb-2">هنوز مدیری ثبت نشده</h4>
                <p class="text-muted mb-4">برای ساخت درخت سازمانی، ابتدا یک مدیر ایجاد کنید.</p>

                <a href="{{ route('admin.users.createManager') }}" class="btn btn-primary rounded-pill px-4">
                    ایجاد مدیر جدید
                </a>
            </div>
        @else
            <div class="tree-board">
                <div class="tree-board__head">
                    <div>
                        <h5 class="fw-bold mb-1">ساختار کاربران</h5>
                       
                    </div>
                </div>

                <div class="org-tree-horizontal">
                    {{-- ستون مدیر کل --}}
                    <div class="tree-column tree-column--root">
                        <div class="tree-column__title">
                            <span class="tree-column__emoji">👑</span>
                            <span>مدیر کل</span>
                        </div>

                        <div class="tree-column__body">
                            @forelse($ownerManagers as $manager)
                                @php $badgeMeta = $managerBadgeMeta($manager); @endphp

                                <div class="tree-node tree-node--root">
                                    <div class="tree-node__head">
                                        <div class="tree-avatar">
                                            {{ mb_substr($manager->name, 0, 1) }}
                                        </div>

                                        <div class="tree-node__meta">
                                            <div class="tree-node__name">{{ $manager->name }}</div>
                                            <div class="tree-node__phone">{{ $manager->phone }}</div>

                                            @if($manager->roles->count())
                                                <div class="role-badges mt-2">
                                                    @foreach($manager->roles as $role)
                                                        <span class="role-badge">{{ $role->name }}</span>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="tree-node__footer">
                                        <span class="soft-badge role-state {{ $badgeMeta['class'] }}">
                                            {{ $badgeMeta['label'] }}
                                        </span>

                                        <span class="soft-badge primary">
                                            {{ $manager->employees->count() }} کارمند
                                        </span>
                                    </div>

                                    <div class="tree-node__actions">
                                        <button
                                            type="button"
                                            class="btn btn-primary btn-sm rounded-pill px-3"
                                            data-bs-toggle="modal"
                                            data-bs-target="#employeesModal{{ $manager->id }}"
                                        >
                                            زیرمجموعه
                                        </button>

                                        <button
                                            type="button"
                                            class="btn btn-outline-secondary btn-sm rounded-pill px-3"
                                            data-bs-toggle="modal"
                                            data-bs-target="#rolesModalManager{{ $manager->id }}"
                                        >
                                            نقش‌ها
                                        </button>

                                        <div class="dropdown">
                                            <button
                                                class="btn btn-user-action btn-sm rounded-pill px-3 dropdown-toggle"
                                                type="button"
                                                data-bs-toggle="dropdown"
                                                aria-expanded="false"
                                            >
                                                عملیات
                                            </button>

                                            <ul class="dropdown-menu dropdown-menu-end text-end shadow-sm border-0 rounded-4">
                                                <li>
                                                    <a class="dropdown-item py-2" href="{{ route('admin.users.editManager', $manager->id) }}">
                                                        ✏️ ویرایش مدیر
                                                    </a>
                                                </li>
                                                <li>
                                                    <button
                                                        class="dropdown-item py-2"
                                                        type="button"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#resetManagerModal{{ $manager->id }}"
                                                    >
                                                        🔑 ریست پسورد
                                                    </button>
                                                </li>
                                                <li><hr class="dropdown-divider"></li>
                                                <li>
                                                    <button
                                                        class="dropdown-item text-danger py-2"
                                                        type="button"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#deleteManagerModal{{ $manager->id }}"
                                                    >
                                                        🗑 حذف مدیر
                                                    </button>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="tree-node tree-node--placeholder">
                                    <div class="tree-node__placeholder-text">
                                        مدیر کل تعریف نشده است
                                    </div>
                                </div>
                            @endforelse
                        </div>
                    </div>

                    {{-- ستون مدیر داخلی --}}
                    <div class="tree-column tree-column--internal">
                        <div class="tree-column__title">
                            <span class="tree-column__emoji">🏢</span>
                            <span>مدیر داخلی</span>
                        </div>

                        <div class="tree-column__body">
                            @forelse($internalManagers as $manager)
                                @php $badgeMeta = $managerBadgeMeta($manager); @endphp

                                <div class="tree-node tree-node--internal">
                                    <div class="tree-node__head">
                                        <div class="tree-avatar">
                                            {{ mb_substr($manager->name, 0, 1) }}
                                        </div>

                                        <div class="tree-node__meta">
                                            <div class="tree-node__name">{{ $manager->name }}</div>
                                            <div class="tree-node__phone">{{ $manager->phone }}</div>

                                            @if($manager->roles->count())
                                                <div class="role-badges mt-2">
                                                    @foreach($manager->roles as $role)
                                                        <span class="role-badge">{{ $role->name }}</span>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="tree-node__footer">
                                        <span class="soft-badge role-state {{ $badgeMeta['class'] }}">
                                            {{ $badgeMeta['label'] }}
                                        </span>

                                        <span class="soft-badge primary">
                                            {{ $manager->employees->count() }} کارمند
                                        </span>
                                    </div>

                                    <div class="tree-node__actions">
                                        <button
                                            type="button"
                                            class="btn btn-primary btn-sm rounded-pill px-3"
                                            data-bs-toggle="modal"
                                            data-bs-target="#employeesModal{{ $manager->id }}"
                                        >
                                            زیرمجموعه
                                        </button>

                                        <button
                                            type="button"
                                            class="btn btn-outline-secondary btn-sm rounded-pill px-3"
                                            data-bs-toggle="modal"
                                            data-bs-target="#rolesModalManager{{ $manager->id }}"
                                        >
                                            نقش‌ها
                                        </button>

                                        <div class="dropdown">
                                            <button
                                                class="btn btn-user-action btn-sm rounded-pill px-3 dropdown-toggle"
                                                type="button"
                                                data-bs-toggle="dropdown"
                                                aria-expanded="false"
                                            >
                                                عملیات
                                            </button>

                                            <ul class="dropdown-menu dropdown-menu-end text-end shadow-sm border-0 rounded-4">
                                                <li>
                                                    <a class="dropdown-item py-2" href="{{ route('admin.users.editManager', $manager->id) }}">
                                                        ✏️ ویرایش مدیر
                                                    </a>
                                                </li>
                                                <li>
                                                    <button
                                                        class="dropdown-item py-2"
                                                        type="button"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#resetManagerModal{{ $manager->id }}"
                                                    >
                                                        🔑 ریست پسورد
                                                    </button>
                                                </li>
                                                <li><hr class="dropdown-divider"></li>
                                                <li>
                                                    <button
                                                        class="dropdown-item text-danger py-2"
                                                        type="button"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#deleteManagerModal{{ $manager->id }}"
                                                    >
                                                        🗑 حذف مدیر
                                                    </button>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="tree-node tree-node--placeholder">
                                    <div class="tree-node__placeholder-text">
                                        مدیر داخلی ثبت نشده است
                                    </div>
                                </div>
                            @endforelse
                        </div>
                    </div>

                    {{-- ستون سایر مدیران به‌صورت خلاصه --}}
                    <div class="tree-column tree-column--others">
                        <div class="tree-column__title">
                            <span class="tree-column__emoji">👥</span>
                            <span>سایر مدیران</span>
                        </div>

                        <div class="tree-column__body">
                            @if($otherManagers->isNotEmpty())
                                <div class="tree-node tree-node--group">
                                    <div class="tree-node__head">
                                        <div class="tree-avatar tree-avatar--group">
                                            {{ $otherManagers->count() }}
                                        </div>

                                        <div class="tree-node__meta">
                                            <div class="tree-node__name"> سایر مدیران</div>

                                            <div class="group-preview mt-2">
                                                @foreach($otherManagers->take(3) as $manager)
                                                    <span class="role-badge">{{ $manager->name }}</span>
                                                @endforeach

                                                @if($otherManagers->count() > 3)
                                                    <span class="role-badge">+{{ $otherManagers->count() - 3 }} نفر دیگر</span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>

                                    <div class="tree-node__footer">
                                        <span class="soft-badge role-state default">
                                            {{ $otherManagers->count() }} مدیر
                                        </span>

                                        <span class="soft-badge primary">
                                            {{ $otherManagersEmployeesCount }} کارمند
                                        </span>
                                    </div>

                                    <div class="tree-node__actions">
                                        <button
                                            type="button"
                                            class="btn btn-primary btn-sm rounded-pill px-3"
                                            data-bs-toggle="modal"
                                            data-bs-target="#otherManagersModal"
                                        >
                                            مشاهده سایر مدیران
                                        </button>
                                    </div>
                                </div>
                            @else
                                <div class="tree-node tree-node--placeholder">
                                    <div class="tree-node__placeholder-text">
                                        مدیر دیگری ثبت نشده است
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- مودال سایر مدیران --}}
            @if($otherManagers->isNotEmpty())
                <div class="modal fade" id="otherManagersModal" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
                        <div class="modal-content border-0 shadow rounded-4 users-modal">
                            <div class="modal-header">
                                <div>
                                    <h5 class="modal-title mb-1">سایر مدیران</h5>
                                    <div class="text-muted small">
                                        تعداد مدیران: {{ $otherManagers->count() }} |
                                        مجموع کارمندان: {{ $otherManagersEmployeesCount }}
                                    </div>
                                </div>

                                <button type="button" class="btn-close m-0" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>

                            <div class="modal-body">
                                <div class="row g-3">
                                    @foreach($otherManagers as $manager)
                                        @php $badgeMeta = $managerBadgeMeta($manager); @endphp

                                        <div class="col-12 col-md-6">
                                            <div class="employee-popup-card h-100 other-manager-card">
                                                <div class="employee-popup-card__top">
                                                    <div class="employee-avatar lg">
                                                        {{ mb_substr($manager->name, 0, 1) }}
                                                    </div>

                                                    <div class="employee-popup-card__meta">
                                                        <div class="employee-name">{{ $manager->name }}</div>
                                                        <div class="employee-phone">{{ $manager->phone }}</div>

                                                        <div class="mt-2 d-flex flex-wrap gap-2">
                                                            <span class="soft-badge role-state {{ $badgeMeta['class'] }}">
                                                                {{ $badgeMeta['label'] }}
                                                            </span>

                                                            <span class="soft-badge primary">
                                                                {{ $manager->employees->count() }} کارمند
                                                            </span>
                                                        </div>

                                                        @if($manager->roles->count())
                                                            <div class="role-badges mt-2">
                                                                @foreach($manager->roles as $role)
                                                                    <span class="role-badge">{{ $role->name }}</span>
                                                                @endforeach
                                                            </div>
                                                        @endif
                                                    </div>
                                                </div>

                                                <div class="employee-popup-card__actions">
                                                    <button
                                                        type="button"
                                                        class="btn btn-primary btn-sm rounded-pill px-3"
                                                        data-bs-dismiss="modal"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#employeesModal{{ $manager->id }}"
                                                    >
                                                        زیرمجموعه
                                                    </button>

                                                    <button
                                                        type="button"
                                                        class="btn btn-outline-secondary btn-sm rounded-pill px-3"
                                                        data-bs-dismiss="modal"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#rolesModalManager{{ $manager->id }}"
                                                    >
                                                        نقش‌ها
                                                    </button>

                                                    <a href="{{ route('admin.users.editManager', $manager->id) }}"
                                                       class="btn btn-outline-primary btn-sm rounded-pill px-3">
                                                        ویرایش
                                                    </a>

                                                    <button
                                                        type="button"
                                                        class="btn btn-outline-warning btn-sm rounded-pill px-3"
                                                        data-bs-dismiss="modal"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#resetManagerModal{{ $manager->id }}"
                                                    >
                                                        ریست پسورد
                                                    </button>

                                                    <button
                                                        type="button"
                                                        class="btn btn-outline-danger btn-sm rounded-pill px-3"
                                                        data-bs-dismiss="modal"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#deleteManagerModal{{ $manager->id }}"
                                                    >
                                                        حذف
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            <div class="modal-footer">
                                <button type="button" class="btn btn-light rounded-pill px-3" data-bs-dismiss="modal">
                                    بستن
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            {{-- مودال‌های مدیران و کارمندان --}}
            @foreach($sortedManagers as $manager)
                @php $badgeMeta = $managerBadgeMeta($manager); @endphp

                {{-- مودال زیرمجموعه مدیر --}}
                <div class="modal fade" id="employeesModal{{ $manager->id }}" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
                        <div class="modal-content border-0 shadow rounded-4 users-modal">
                            <div class="modal-header">
                                <div>
                                    <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                                        <h5 class="modal-title mb-0">زیرمجموعه‌های {{ $manager->name }}</h5>
                                        <span class="soft-badge role-state {{ $badgeMeta['class'] }}">
                                            {{ $badgeMeta['label'] }}
                                        </span>
                                    </div>

                                    <div class="text-muted small">
                                        تعداد کارمندان: {{ $manager->employees->count() }}
                                    </div>
                                </div>

                                <button type="button" class="btn-close m-0" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>

                            <div class="modal-body">
                                <div class="d-flex flex-wrap gap-2 mb-4">
                                    <a href="{{ route('admin.users.createEmployee', $manager->id) }}"
                                       class="btn btn-primary rounded-pill px-3">
                                        افزودن کارمند
                                    </a>

                                    <a href="{{ route('admin.users.editManager', $manager->id) }}"
                                       class="btn btn-outline-secondary rounded-pill px-3">
                                        ویرایش مدیر
                                    </a>
                                </div>

                                @if($manager->employees->isEmpty())
                                    <div class="users-empty users-empty--sm">
                                        <div class="users-empty__icon">👤</div>
                                        <h6 class="fw-bold mb-2">برای این مدیر کارمندی ثبت نشده</h6>
                                        <p class="text-muted mb-0">از دکمه «افزودن کارمند» استفاده کنید.</p>
                                    </div>
                                @else
                                    <div class="row g-3">
                                        @foreach($manager->employees as $employee)
                                            <div class="col-12 col-md-6">
                                                <div class="employee-popup-card h-100">
                                                    <div class="employee-popup-card__top">
                                                        <div class="employee-avatar lg">👤</div>

                                                        <div class="employee-popup-card__meta">
                                                            <div class="employee-name">{{ $employee->name }}</div>
                                                            <div class="employee-phone">{{ $employee->phone }}</div>

                                                            @if($employee->roles->count())
                                                                <div class="role-badges mt-2">
                                                                    @foreach($employee->roles as $role)
                                                                        <span class="role-badge">{{ $role->name }}</span>
                                                                    @endforeach
                                                                </div>
                                                            @endif
                                                        </div>
                                                    </div>

                                                    <div class="employee-popup-card__actions">
                                                        <button
                                                            type="button"
                                                            class="btn btn-outline-secondary btn-sm rounded-pill px-3"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#rolesModal{{ $employee->id }}"
                                                        >
                                                            نقش‌ها
                                                        </button>

                                                        <a href="{{ route('admin.users.editEmployee', $employee->id) }}"
                                                           class="btn btn-outline-primary btn-sm rounded-pill px-3">
                                                            ویرایش
                                                        </a>

                                                        <button
                                                            type="button"
                                                            class="btn btn-outline-warning btn-sm rounded-pill px-3"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#resetEmployeeModal{{ $employee->id }}"
                                                        >
                                                            ریست پسورد
                                                        </button>

                                                        <button
                                                            type="button"
                                                            class="btn btn-outline-danger btn-sm rounded-pill px-3"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#deleteEmployeeModal{{ $employee->id }}"
                                                        >
                                                            حذف
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>

                            <div class="modal-footer">
                                <button type="button" class="btn btn-light rounded-pill px-3" data-bs-dismiss="modal">
                                    بستن
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- مودال نقش‌های مدیر --}}
                <div class="modal fade" id="rolesModalManager{{ $manager->id }}" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
                        <div class="modal-content border-0 shadow rounded-4 users-modal">
                            <div class="modal-header">
                                <h5 class="modal-title">مدیریت نقش‌ها: {{ $manager->name }}</h5>
                                <button type="button" class="btn-close m-0" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>

                            <form action="{{ route('admin.users.updateRoles', $manager->id) }}" method="POST">
                                @csrf
                                <div class="modal-body">
                                    <p class="text-muted small mb-3">نقش‌های موردنظر را انتخاب کنید.</p>

                                    <div class="row g-2">
                                        @foreach($roles as $role)
                                            <div class="col-12 col-sm-6">
                                                <label class="role-check">
                                                    <input
                                                        type="checkbox"
                                                        name="roles[]"
                                                        value="{{ $role->name }}"
                                                        @if($manager->roles->contains('name', $role->name)) checked @endif
                                                    >
                                                    <span>{{ $role->name }}</span>
                                                </label>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>

                                <div class="modal-footer">
                                    <button type="button" class="btn btn-light rounded-pill px-3" data-bs-dismiss="modal">
                                        بستن
                                    </button>
                                    <button type="submit" class="btn btn-primary rounded-pill px-4">
                                        ذخیره
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                {{-- مودال حذف مدیر --}}
                <div class="modal fade" id="deleteManagerModal{{ $manager->id }}" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content border-0 shadow rounded-4 users-modal">
                            <div class="modal-header">
                                <h5 class="modal-title">حذف مدیر</h5>
                                <button type="button" class="btn-close m-0" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>

                            <div class="modal-body">
                                آیا از حذف <strong>{{ $manager->name }}</strong> و کارمندان زیرمجموعه‌اش مطمئن هستید؟
                            </div>

                            <div class="modal-footer">
                                <button type="button" class="btn btn-light rounded-pill px-3" data-bs-dismiss="modal">
                                    انصراف
                                </button>

                                <form action="{{ route('admin.users.destroyManager', $manager->id) }}" method="POST">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger rounded-pill px-4">
                                        حذف
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- مودال ریست پسورد مدیر --}}
                <div class="modal fade" id="resetManagerModal{{ $manager->id }}" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content border-0 shadow rounded-4 users-modal">
                            <div class="modal-header">
                                <h5 class="modal-title">ریست پسورد</h5>
                                <button type="button" class="btn-close m-0" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>

                            <div class="modal-body">
                                پسورد <strong>{{ $manager->name }}</strong> ریست شود؟
                            </div>

                            <div class="modal-footer">
                                <button type="button" class="btn btn-light rounded-pill px-3" data-bs-dismiss="modal">
                                    انصراف
                                </button>

                                <form action="{{ route('admin.users.resetPassword', $manager->id) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="btn btn-info text-white rounded-pill px-4">
                                        ریست پسورد
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- مودال‌های کارمندان --}}
                @foreach($manager->employees as $employee)
                    <div class="modal fade" id="rolesModal{{ $employee->id }}" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
                            <div class="modal-content border-0 shadow rounded-4 users-modal">
                                <div class="modal-header">
                                    <h5 class="modal-title">مدیریت نقش‌ها: {{ $employee->name }}</h5>
                                    <button type="button" class="btn-close m-0" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>

                                <form action="{{ route('admin.users.updateRoles', $employee->id) }}" method="POST">
                                    @csrf
                                    <div class="modal-body">
                                        <p class="text-muted small mb-3">نقش‌های موردنظر را انتخاب کنید.</p>

                                        <div class="row g-2">
                                            @foreach($roles as $role)
                                                <div class="col-12 col-sm-6">
                                                    <label class="role-check">
                                                        <input
                                                            type="checkbox"
                                                            name="roles[]"
                                                            value="{{ $role->name }}"
                                                            @if($employee->roles->contains('name', $role->name)) checked @endif
                                                        >
                                                        <span>{{ $role->name }}</span>
                                                    </label>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>

                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-light rounded-pill px-3" data-bs-dismiss="modal">
                                            بستن
                                        </button>
                                        <button type="submit" class="btn btn-primary rounded-pill px-4">
                                            ذخیره
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="modal fade" id="deleteEmployeeModal{{ $employee->id }}" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content border-0 shadow rounded-4 users-modal">
                                <div class="modal-header">
                                    <h5 class="modal-title">حذف کارمند</h5>
                                    <button type="button" class="btn-close m-0" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>

                                <div class="modal-body">
                                    آیا از حذف <strong>{{ $employee->name }}</strong> مطمئن هستید؟
                                </div>

                                <div class="modal-footer">
                                    <button type="button" class="btn btn-light rounded-pill px-3" data-bs-dismiss="modal">
                                        انصراف
                                    </button>

                                    <form action="{{ route('admin.users.destroyEmployee', $employee->id) }}" method="POST">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger rounded-pill px-4">
                                            حذف
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="modal fade" id="resetEmployeeModal{{ $employee->id }}" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content border-0 shadow rounded-4 users-modal">
                                <div class="modal-header">
                                    <h5 class="modal-title">ریست پسورد</h5>
                                    <button type="button" class="btn-close m-0" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>

                                <div class="modal-body">
                                    پسورد <strong>{{ $employee->name }}</strong> ریست شود؟
                                </div>

                                <div class="modal-footer">
                                    <button type="button" class="btn btn-light rounded-pill px-3" data-bs-dismiss="modal">
                                        انصراف
                                    </button>

                                    <form action="{{ route('admin.users.resetPassword', $employee->id) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="btn btn-info text-white rounded-pill px-4">
                                            ریست پسورد
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            @endforeach
        @endif
    </div>

    @push('styles')
        <style>
            .users-tree-page {
                --u-bg: #f6f8fc;
                --u-card: #ffffff;
                --u-card-2: #fbfcff;
                --u-border: #e6ebf3;
                --u-line: #d7e0ea;
                --u-line-strong: #9aa8bc;
                --u-text: #111827;
                --u-muted: #6b7280;
                --u-soft: #f3f6fb;
                --u-primary-soft: rgba(13, 110, 253, .10);
                --u-shadow: 0 14px 35px rgba(15, 23, 42, .07);
                --u-owner-soft: rgba(220, 53, 69, .12);
                --u-internal-soft: rgba(245, 158, 11, .16);
                --u-owner-border: rgba(99, 102, 241, .24);
                --u-internal-border: rgba(245, 158, 11, .24);
                --u-group-border: rgba(59, 130, 246, .18);
            }

            .users-header {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 16px;
                flex-wrap: wrap;
            }

            .users-title {
                font-size: 1.45rem;
                font-weight: 900;
                color: var(--u-text);
            }

            .users-subtitle {
                color: var(--u-muted);
                margin-top: 4px;
                font-size: .95rem;
            }

            .users-add-btn {
                border-radius: 999px;
                padding: .7rem 1.25rem;
                display: inline-flex;
                align-items: center;
                gap: .5rem;
                font-weight: 700;
            }

            .users-empty {
                background: var(--u-card);
                border: 1px solid var(--u-border);
                border-radius: 28px;
                padding: 48px 24px;
                text-align: center;
                box-shadow: var(--u-shadow);
            }

            .users-empty--sm {
                padding: 28px 18px;
                border-radius: 22px;
            }

            .users-empty__icon {
                font-size: 46px;
                margin-bottom: 12px;
            }

            .tree-board {
                background: linear-gradient(180deg, #f8fbff 0%, #ffffff 100%);
                border: 1px solid var(--u-border);
                border-radius: 30px;
                padding: 24px;
                box-shadow: var(--u-shadow);
                overflow: hidden;
            }

            .tree-board__head {
                margin-bottom: 18px;
            }

            .org-tree-horizontal {
                direction: ltr;
                display: flex;
                align-items: flex-start;
                gap: 72px;
                overflow-x: auto;
                padding: 8px 8px 20px;
            }

            .tree-column {
                position: relative;
                flex: 0 0 310px;
                min-width: 310px;
            }

            .tree-column:not(:first-child)::before {
                content: '';
                position: absolute;
                top: 76px;
                left: -72px;
                width: 72px;
                height: 2px;
                background: linear-gradient(90deg, var(--u-line), var(--u-line-strong));
            }

            .tree-column__title {
                background: var(--u-card);
                border: 1px solid var(--u-border);
                border-radius: 20px;
                padding: 14px 16px;
                min-height: 72px;
                text-align: center;
                font-weight: 900;
                color: var(--u-text);
                box-shadow: var(--u-shadow);
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 10px;
            }

            .tree-column__emoji {
                font-size: 1.2rem;
            }

            .tree-column__body {
                position: relative;
                margin-top: 24px;
                padding-top: 20px;
                display: flex;
                flex-direction: column;
                align-items: center;
                gap: 22px;
            }

            .tree-column__body::before {
                content: '';
                position: absolute;
                top: 0;
                bottom: 12px;
                left: 50%;
                transform: translateX(-50%);
                width: 2px;
                background: linear-gradient(180deg, var(--u-line-strong), var(--u-line));
                border-radius: 999px;
            }

            .tree-node {
                width: 100%;
                position: relative;
                background: linear-gradient(180deg, var(--u-card) 0%, var(--u-card-2) 100%);
                border: 1px solid var(--u-border);
                border-radius: 24px;
                padding: 16px;
                box-shadow: var(--u-shadow);
                direction: rtl;
            }

            .tree-node::before {
                content: '';
                position: absolute;
                top: -20px;
                left: 50%;
                transform: translateX(-50%);
                width: 2px;
                height: 20px;
                background: var(--u-line-strong);
                border-radius: 999px;
            }

            .tree-node--root {
                border-color: var(--u-owner-border);
                background: linear-gradient(180deg, #ffffff 0%, #f5f7ff 100%);
            }

            .tree-node--internal {
                border-color: var(--u-internal-border);
                background: linear-gradient(180deg, #ffffff 0%, #fff9ef 100%);
            }

            .tree-node--group {
                border-color: var(--u-group-border);
                background: linear-gradient(180deg, #ffffff 0%, #f6fbff 100%);
            }

            .tree-node--placeholder {
                border-style: dashed;
                background: #fbfcfe;
                min-height: 110px;
                display: flex;
                align-items: center;
                justify-content: center;
                text-align: center;
            }

            .tree-node__placeholder-text {
                color: var(--u-muted);
                font-weight: 700;
                line-height: 1.9;
            }

            .tree-node__head {
                display: flex;
                align-items: center;
                gap: 14px;
                min-width: 0;
            }

            .tree-avatar {
                width: 56px;
                height: 56px;
                min-width: 56px;
                border-radius: 18px;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 22px;
                font-weight: 900;
                color: #fff;
                background: linear-gradient(135deg, #2563eb, #3b82f6);
                box-shadow: 0 10px 24px rgba(37, 99, 235, .22);
            }

            .tree-avatar--group {
                background: linear-gradient(135deg, #0f766e, #14b8a6);
                box-shadow: 0 10px 24px rgba(20, 184, 166, .20);
            }

            .tree-node__meta {
                min-width: 0;
                flex: 1;
            }

            .tree-node__name {
                font-size: 1.02rem;
                font-weight: 900;
                color: var(--u-text);
                margin-bottom: 4px;
            }

            .tree-node__phone {
                color: var(--u-muted);
                font-size: .92rem;
            }

            .tree-node__footer {
                margin-top: 14px;
                display: flex;
                flex-wrap: wrap;
                gap: 8px;
            }

            .tree-node__actions {
                margin-top: 14px;
                display: flex;
                flex-wrap: wrap;
                gap: 8px;
            }

            .soft-badge {
                display: inline-flex;
                align-items: center;
                padding: 8px 14px;
                border-radius: 999px;
                background: var(--u-soft);
                color: var(--u-text);
                font-size: .82rem;
                font-weight: 700;
                border: 1px solid var(--u-border);
            }

            .soft-badge.primary {
                background: var(--u-primary-soft);
                color: #2563eb;
                border-color: transparent;
            }

            .soft-badge.role-state.default {
                background: var(--u-soft);
                color: var(--u-text);
            }

            .soft-badge.role-state.owner {
                background: var(--u-owner-soft);
                color: #dc3545;
                border-color: transparent;
            }

            .soft-badge.role-state.internal {
                background: var(--u-internal-soft);
                color: #b45309;
                border-color: transparent;
            }

            .role-badges,
            .group-preview {
                display: flex;
                flex-wrap: wrap;
                gap: 8px;
            }

            .role-badge {
                display: inline-flex;
                align-items: center;
                padding: 6px 12px;
                border-radius: 999px;
                background: var(--u-soft);
                border: 1px solid var(--u-border);
                color: var(--u-text);
                font-size: .78rem;
                font-weight: 700;
            }

            .btn-user-action {
                background: var(--u-card);
                color: var(--u-text);
                border: 1px solid var(--u-border);
            }

            .btn-user-action:hover,
            .btn-user-action:focus,
            .btn-user-action:active,
            .btn-user-action.show {
                background: var(--u-soft) !important;
                color: var(--u-text) !important;
                border-color: var(--u-border) !important;
                box-shadow: none !important;
            }

            .dropdown-menu {
                border: 1px solid var(--u-border) !important;
            }

            .dropdown-item {
                color: var(--u-text);
            }

            .dropdown-item:hover,
            .dropdown-item:focus {
                background: var(--u-soft);
                color: var(--u-text);
            }

            .dropdown-divider {
                border-color: var(--u-border);
            }

            .users-modal {
                background: var(--u-card) !important;
                color: var(--u-text);
            }

            .users-modal .modal-header,
            .users-modal .modal-footer {
                border-color: var(--u-border);
            }

            .users-modal .text-muted {
                color: var(--u-muted) !important;
            }

            .role-check {
                display: flex;
                align-items: center;
                gap: 10px;
                background: var(--u-soft);
                border: 1px solid var(--u-border);
                border-radius: 14px;
                padding: 12px 14px;
                cursor: pointer;
                width: 100%;
                color: var(--u-text);
            }

            .role-check input {
                margin: 0;
            }

            .employee-avatar {
                width: 44px;
                height: 44px;
                min-width: 44px;
                border-radius: 14px;
                display: flex;
                align-items: center;
                justify-content: center;
                background: var(--u-soft);
                border: 1px solid var(--u-border);
                font-size: 18px;
                font-weight: 900;
                color: var(--u-text);
            }

            .employee-avatar.lg {
                width: 58px;
                height: 58px;
                min-width: 58px;
                border-radius: 18px;
                font-size: 22px;
            }

            .employee-popup-card {
                background: linear-gradient(180deg, var(--u-card) 0%, var(--u-card-2) 100%);
                border: 1px solid var(--u-border);
                border-radius: 22px;
                padding: 16px;
                box-shadow: var(--u-shadow);
            }

            .other-manager-card {
                border-color: rgba(59, 130, 246, .12);
            }

            .employee-popup-card__top {
                display: flex;
                align-items: center;
                gap: 14px;
                min-width: 0;
            }

            .employee-popup-card__meta {
                min-width: 0;
                flex: 1;
            }

            .employee-name {
                font-weight: 900;
                color: var(--u-text);
                margin-bottom: 4px;
            }

            .employee-phone {
                color: var(--u-muted);
                font-size: .92rem;
            }

            .employee-popup-card__actions {
                margin-top: 14px;
                display: flex;
                flex-wrap: wrap;
                gap: 8px;
            }

            @media (max-width: 992px) {
                .org-tree-horizontal {
                    gap: 48px;
                }

                .tree-column {
                    flex-basis: 280px;
                    min-width: 280px;
                }

                .tree-column:not(:first-child)::before {
                    left: -48px;
                    width: 48px;
                }
            }

            @media (max-width: 768px) {
                .tree-board {
                    padding: 18px;
                }

                .tree-avatar {
                    width: 50px;
                    height: 50px;
                    min-width: 50px;
                    font-size: 20px;
                }

                .tree-node__actions > *,
                .employee-popup-card__actions > * {
                    flex: 1 1 100%;
                }
            }

            @media (max-width: 576px) {
                .users-header {
                    align-items: stretch;
                }

                .users-add-btn {
                    width: 100%;
                    justify-content: center;
                }
            }
        </style>
    @endpush

    @stack('styles')
</x-layouts.app>