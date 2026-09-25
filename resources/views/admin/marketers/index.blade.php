<x-layouts.app>
    <x-slot name="header">
        <!-- Loader -->
        <div id="loader" class="d-flex justify-content-center align-items-center position-fixed top-0 start-0 w-100 h-100 bg-white" style="z-index: 1050;">
            <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
                <span class="visually-hidden">در حال بارگذاری...</span>
            </div>
        </div>

        <div class="d-flex gap-3 align-items-center">
            <h2 class="fw-semibold fs-4 mb-0">مدیریت بازاریاب‌ها</h2>
            <span class="text-muted">|</span>
            <h3 class="fs-6 mb-0">
                <a href="{{ route('admin.users.create', ['role' => 'Marketer']) }}" class="text-decoration-none">ایجاد کاربر بازاریاب</a>
            </h3>
        </div>
    </x-slot>

    <div class="py-4">
        <div class="container">
            <div class="card shadow-sm rounded-3">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover table-striped align-middle text-start">
                            <thead class="table-light">
                                <tr>
                                    <th scope="col">#</th>
                                    <th scope="col">نام</th>
                                    <th scope="col">تلفن</th>
                                    <th scope="col">نقش</th>
                                    <th scope="col">وضعیت</th>
                                    <th scope="col">گزارشات</th>
                                    <th scope="col">مشتریان</th>
                                    <th scope="col">عملیات</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($marketers as $item)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>{{ $item->name }}</td>
                                        <td>{{ $item->phone }}</td>
                                        <td>
                                            @foreach($item->getRoleNames() as $role)
                                                <span class="badge rounded-pill bg-primary-subtle text-primary-emphasis fw-semibold me-1">
                                                    @if ($role == "Marketer")
                                                        بازاریاب
                                                    @else
                                                        {{ $role }}
                                                    @endif
                                                </span>
                                            @endforeach
                                        </td>
                                        <td>
                                            @if(! $item->is_active)
                                                <span class="badge bg-secondary">غیرفعال</span>
                                            @elseif($item->isBlocked())
                                                @php
                                                    $blockedUntil = new \Hekmatinasser\Verta\Verta($item->blocked_until);
                                                @endphp
                                                <span class="badge bg-danger">
                                                    مسدود تا {{ $blockedUntil->format('j F Y H:i') }}
                                                </span>
                                            @else
                                                <span class="badge bg-success">فعال</span>
                                            @endif
                                        </td>

                                        <td>
                                            <a href="{{ route('user.reports.reportsManagment', ['user_id' => $item->id]) }}" class="btn btn-sm btn-outline-dark">
                                                مشاهده گزارش
                                            </a>
                                        </td>
                                        <td>
                                            <a href="{{ route('admin.marketers.customers.index', $item->id) }}" class="btn btn-sm btn-outline-dark">
                                                مشاهده مشتریان
                                            </a>
                                        </td>
                                        <td class="d-flex flex-wrap gap-1">
                                            <a href="{{ route('admin.users.editEmployee', $item->id) }}" class="btn btn-sm btn-primary">ویرایش</a>

                                            @include('admin.users.partials.active-toggle', ['user' => $item])

                                            <form action="{{ route('admin.marketers.destroy', $item->id) }}" method="POST" onsubmit="return confirm('آیا مطمئن هستید؟')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger">آرشیو</button>
                                            </form>

                                            <!-- دکمه مسدودسازی -->
                                            <button type="button" class="btn btn-sm btn-warning"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#blockUserModal{{ $item->id }}">
                                                مسدود کن
                                            </button>

                                            <!-- دکمه آزادسازی -->
                                            @if($item->isBlocked())
                                                <form action="{{ route('admin.users.unblock', $item) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-success">
                                                        آزادسازی
                                                    </button>
                                                </form>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    @if($marketers->isEmpty())
                        <div class="alert alert-secondary text-center mb-0">
                            کاربری یافت نشد.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- همه Modal ها بعد از جدول -->
    @foreach($marketers as $item)
        <div class="modal fade" id="blockUserModal{{ $item->id }}" tabindex="-1" aria-labelledby="blockUserLabel{{ $item->id }}" aria-hidden="true">
            <div class="modal-dialog">
                <form action="{{ route('admin.users.block', $item) }}" method="POST" class="modal-content">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="blockUserLabel{{ $item->id }}">
                            مسدودسازی کاربر: {{ $item->name }}
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="بستن"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">مدت مسدودسازی (ساعت)</label>
                            <input type="number" name="hours" class="form-control" min="1" max="8760" placeholder="مثلاً 24" required>
                        </div>
                        <div class="d-flex gap-2 mb-3">
                            <button type="button" class="btn btn-outline-primary"
                                    onclick="this.closest('form').querySelector('[name=hours]').value=24">
                                24 ساعت
                            </button>
                            <button type="button" class="btn btn-outline-primary"
                                    onclick="this.closest('form').querySelector('[name=hours]').value=72">
                                72 ساعت
                            </button>
                            <button type="button" class="btn btn-outline-primary"
                                    onclick="this.closest('form').querySelector('[name=hours]').value=168">
                                7 روز
                            </button>
                        </div>
                        <div class="alert alert-info">
                            کاربر در مدت مسدودسازی امکان ورود یا دسترسی به پنل را نخواهد داشت.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">انصراف</button>
                        <button type="submit" class="btn btn-danger">مسدود کن</button>
                    </div>
                </form>
            </div>
        </div>
    @endforeach
</x-layouts.app>
