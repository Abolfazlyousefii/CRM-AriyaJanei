<x-app-layout>
    <x-slot name="header">
        <h2 class="h4 font-weight-bold">مدیریت مشتریان</h2>
    </x-slot>

    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- Styles --}}
    <link href="https://lib.arvancloud.ir/bootstrap/5.3.0-alpha1/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('lib/persian-datepicker.min.css') }}">
     <link rel="stylesheet" href="{{ asset('lib/bootstrap-icons.min.css') }}">
    {{-- Scripts --}}
    <script src="https://lib.arvancloud.ir/jquery/3.6.3/jquery.js"></script>
    <script src="https://lib.arvancloud.ir/bootstrap/5.3.0-alpha1/js/bootstrap.bundle.min.js"></script>
    <script src="{{ asset('lib/persian-date.min.js') }}"></script>
    <script src="{{ asset('lib/persian-datepicker.min.js') }}"></script>

    @php
        $user = Auth::user();

        $indexRoute = $user->hasRole('Marketer')
            ? route('customersAdmin2.index')
            : route('admin.customersAdmin.index');

        $canManageCustomers = $user->hasRole('Admin') || $user->hasRole('internalManager') || $user->hasRole('SaleManager');
        $canCommunicate = $user->hasRole('Admin') || $user->hasRole('internalManager');
        $canEditNote = $user->hasRole('Admin') || $user->hasRole('Marketer') || $user->hasRole('internalManager');
        $canDeleteNote = $user->hasRole('Admin') || $user->hasRole('internalManager');
    @endphp

    <style>
        .note-content {
            white-space: pre-wrap;
            word-break: break-word;
        }

        .modal-body .list-group-item {
            text-align: right;
        }

        .customer-actions,
        .note-actions {
            display: flex;
            gap: .5rem;
            flex-wrap: wrap;
            justify-content: center;
            align-items: center;
        }

        .note-meta {
            display: flex;
            gap: .35rem;
            align-items: center;
            flex-wrap: wrap;
            justify-content: flex-end;
        }

        .notes-list .list-group-item {
            gap: 1rem;
        }

        @media (max-width: 768px) {
            .notes-list .list-group-item {
                flex-direction: column;
                align-items: stretch !important;
            }

            .note-meta {
                justify-content: flex-start;
            }
        }
    </style>

    <div class="container mt-4">

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="بستن"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="بستن"></button>
            </div>
        @endif

        <div id="page-alert-area"></div>

        {{-- سرچ و فیلتر --}}
        <div class="card mb-4 shadow-sm">
            <div class="card-body">
                <form method="GET" action="{{ $indexRoute }}" class="row g-2 align-items-center">
                    <div class="col-md-4">
                        <input
                            type="text"
                            name="search"
                            value="{{ request('search') }}"
                            placeholder="جستجو نام یا شماره"
                            class="form-control"
                        >
                    </div>

                    <div class="col-md-2">
                        <button class="btn btn-primary w-100" type="submit">
                            <i class="bi bi-search"></i>
                            جستجو
                        </button>
                    </div>

                    <div class="col-md-3">
                        <select name="sort" class="form-select" onchange="this.form.submit()">
                            <option value="" {{ request('sort') == '' ? 'selected' : '' }}>مرتب‌سازی عادی</option>
                            <option value="last_note" {{ request('sort') == 'last_note' ? 'selected' : '' }}>
                                مرتب‌سازی بر اساس آخرین یادداشت
                            </option>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <a href="{{ route('admin.customersCreate.create') }}" class="btn btn-success w-100">
                            <i class="bi bi-plus-circle"></i>
                            مشتری جدید
                        </a>
                    </div>

                    <div class="col-md-3">
                        <a href="{{ route('admin.customersAdmin.export.excel') }}" class="btn btn-outline-success w-100">
                            <i class="bi bi-file-earmark-excel"></i>
                            خروجی اکسل همه مشتری‌ها
                        </a>
                    </div>

                    @if(request('sort') === 'last_note')
                        <div class="col-md-3">
                            <input
                                type="text"
                                id="note_from"
                                name="note_from"
                                value="{{ request('note_from') }}"
                                class="form-control"
                                placeholder="از تاریخ"
                                autocomplete="off"
                            >
                        </div>

                        <div class="col-md-3">
                            <input
                                type="text"
                                id="note_to"
                                name="note_to"
                                value="{{ request('note_to') }}"
                                class="form-control"
                                placeholder="تا تاریخ"
                                autocomplete="off"
                            >
                        </div>

                        <div class="col-md-2">
                            <button class="btn btn-outline-primary w-100" type="submit">
                                اعمال بازه
                            </button>
                        </div>

                        <div class="col-md-1">
                            <a
                                class="btn btn-outline-secondary w-100"
                                href="{{ $indexRoute . '?' . http_build_query(array_filter([
                                    'search' => request('search'),
                                    'sort' => 'last_note',
                                ])) }}"
                            >
                                پاک
                            </a>
                        </div>
                    @endif
                </form>
            </div>
        </div>

        {{-- جدول مشتریان --}}
        <div class="card shadow-sm">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle text-center">
                        <thead class="table-dark">
                            <tr>
                                <th>شناسه مشتری</th>
                                <th>نام</th>
                                <th>شماره</th>
                                <th>بازاریاب</th>
                                <th>شهر</th>
                                <th>نحوه آشنایی</th>
                                <th>یادداشت‌ها / فاکتورها</th>
                                <th>ارتباط</th>
                                <th>عملیات</th>
                            </tr>
                        </thead>

                        <tbody>
                            @forelse($customers as $customer)
                                <tr>
                                    <td>{{ $customer->display_customer_id }}</td>
                                    <td>{{ $customer->name }}</td>
                                    <td>{{ $customer->phone }}</td>
                                    <td>{{ $customer->marketer?->name ?? '-' }}</td>
                                    <td>{{ $customer->city ?: $customer->address ?: '-' }}</td>
                                    <td>{{ $customer->referenceType?->name ?? '-' }}</td>

                                    <td>
                                        <div class="customer-actions">
                                            <button
                                                class="btn btn-sm btn-info"
                                                type="button"
                                                data-bs-toggle="modal"
                                                data-bs-target="#invoicesModal-{{ $customer->id }}"
                                            >
                                                مشاهده فاکتورها
                                            </button>

                                            <button
                                                class="btn btn-sm btn-primary"
                                                type="button"
                                                data-bs-toggle="modal"
                                                data-bs-target="#notesModal-{{ $customer->id }}"
                                            >
                                                مشاهده یادداشت‌ها
                                            </button>
                                        </div>
                                    </td>

                                    <td>
                                        @if($canCommunicate && $customer->phone)
                                            <div class="btn-group">
                                                <button
                                                    type="button"
                                                    class="btn btn-secondary btn-sm dropdown-toggle"
                                                    data-bs-toggle="dropdown"
                                                    aria-expanded="false"
                                                >
                                                    ارتباط
                                                </button>

                                                <ul class="dropdown-menu">
                                                    <li>
                                                        <a
                                                            class="dropdown-item"
                                                            href="https://wa.me/{{ preg_replace('/^0/', '98', $customer->phone) }}"
                                                            target="_blank"
                                                        >
                                                            <i class="bi bi-whatsapp text-success"></i>
                                                            واتساپ
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item" href="sms:{{ $customer->phone }}">
                                                            <i class="bi bi-chat-dots text-primary"></i>
                                                            پیامک
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item" href="tel:{{ $customer->phone }}">
                                                            <i class="bi bi-telephone text-dark"></i>
                                                            تماس
                                                        </a>
                                                    </li>
                                                </ul>
                                            </div>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>

                                    <td>
                                        @if($canManageCustomers)
                                            <div class="customer-actions">
                                                <a href="{{ route('admin.customersedit.edit', $customer->id) }}" class="btn btn-sm btn-primary">
                                                    <i class="bi bi-pencil-square"></i>
                                                    ویرایش
                                                </a>

                                                <form
                                                    action="{{ route('admin.customersdelete.destroy', $customer) }}"
                                                    method="POST"
                                                    class="d-inline"
                                                    onsubmit="return confirm('آیا مطمئن هستید؟')"
                                                >
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-danger">
                                                        <i class="bi bi-trash"></i>
                                                        حذف
                                                    </button>
                                                </form>
                                            </div>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-muted">هیچ مشتری یافت نشد.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- مودال‌ها --}}
                @foreach($customers as $customer)
                    {{-- مودال فاکتورها --}}
                    <div
                        class="modal fade"
                        id="invoicesModal-{{ $customer->id }}"
                        tabindex="-1"
                        aria-hidden="true"
                    >
                        <div class="modal-dialog modal-dialog-centered modal-lg">
                            <div class="modal-content">
                                <div class="modal-header bg-info text-white">
                                    <h5 class="modal-title">
                                        فاکتورهای {{ $customer->name }} (شناسه: {{ $customer->display_customer_id }})
                                    </h5>
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="بستن"></button>
                                </div>

                                <div class="modal-body" dir="rtl">
                                    @forelse($customer->invoices as $invoice)
                                        <div class="card mb-3">
                                            <div class="card-header">
                                                <strong>شماره فاکتور:</strong>
                                                INV-{{ str_pad($invoice->id, 5, '0', STR_PAD_LEFT) }}
                                                |
                                                <strong>تاریخ:</strong>
                                                {{ \Morilog\Jalali\Jalalian::fromDateTime($invoice->invoice_date)->format('Y/m/d') }}
                                            </div>

                                            <div class="card-body">
                                                @if($invoice->description)
                                                    <p><strong>توضیحات:</strong> {{ $invoice->description }}</p>
                                                @endif

                                                @if($invoice->attachments->count())
                                                    <div class="mb-3">
                                                        <h6 class="mb-3 text-primary">پیوست‌های فاکتور</h6>

                                                        @foreach($invoice->attachments as $attachment)
                                                            @php
                                                                $path = asset('storage/' . $attachment->path);
                                                                $ext = strtolower(pathinfo($attachment->path, PATHINFO_EXTENSION));
                                                            @endphp

                                                            @if(in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif']))
                                                                <img
                                                                    src="{{ $path }}"
                                                                    alt="پیوست فاکتور {{ $loop->iteration }}"
                                                                    class="img-fluid rounded border mb-3"
                                                                    style="max-height: 380px;"
                                                                >
                                                            @elseif($ext === 'pdf')
                                                                <div class="mb-3">
                                                                    <p class="text-muted mb-2">PDF پیوست {{ $loop->iteration }}</p>
                                                                    <iframe src="{{ $path }}" class="w-100 rounded border" style="height: 600px;"></iframe>
                                                                    <a href="{{ $path }}" target="_blank" class="text-primary mt-2 d-inline-block">
                                                                        باز کردن / دانلود PDF
                                                                    </a>
                                                                </div>
                                                            @elseif(in_array($ext, ['html', 'htm']))
                                                                <div class="mb-3">
                                                                    <p class="text-muted mb-2">HTML پیوست {{ $loop->iteration }}</p>
                                                                    <iframe
                                                                        src="{{ $path }}"
                                                                        class="w-100 rounded border"
                                                                        style="height: 600px;"
                                                                        sandbox="allow-same-origin allow-scripts allow-forms allow-popups"
                                                                    ></iframe>

                                                                    <a href="{{ $path }}" target="_blank" class="text-primary mt-2 d-inline-block">
                                                                        باز کردن HTML در تب جدید
                                                                    </a>
                                                                </div>
                                                            @else
                                                                <div class="mb-2">
                                                                    <a href="{{ $path }}" target="_blank" class="text-primary">
                                                                        دانلود فایل پیوست {{ $loop->iteration }} ({{ $ext }})
                                                                    </a>
                                                                </div>
                                                            @endif
                                                        @endforeach
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    @empty
                                        <p class="text-muted mb-0">هیچ فاکتوری ثبت نشده است.</p>
                                    @endforelse
                                </div>

                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">بستن</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- مودال یادداشت‌ها --}}
                    <div
                        class="modal fade notes-modal"
                        id="notesModal-{{ $customer->id }}"
                        tabindex="-1"
                        aria-hidden="true"
                        data-customer-id="{{ $customer->id }}"
                        data-store-url="{{ route('admin.customers.notes.store', $customer->id) }}"
                    >
                        <div class="modal-dialog modal-dialog-centered modal-lg">
                            <div class="modal-content">
                                <div class="modal-header bg-primary text-white">
                                    <h5 class="modal-title">
                                        یادداشت‌های {{ $customer->name }} (شناسه: {{ $customer->display_customer_id }})
                                    </h5>
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="بستن"></button>
                                </div>

                                <div class="modal-body" dir="rtl">
                                    <div class="notes-alert-area mb-3"></div>

                                    <ul class="list-group notes-list">
                                        @forelse($customer->notes as $note)
                                            @php
                                                $creator = $note->user;
                                                $badgeClass = 'bg-secondary';

                                                if ($creator && $creator->hasRole('Admin')) {
                                                    $badgeClass = 'bg-primary';
                                                } elseif ($creator && $creator->hasRole('Marketer')) {
                                                    $badgeClass = 'bg-warning text-dark';
                                                }
                                            @endphp

                                            <li
                                                class="list-group-item d-flex justify-content-between align-items-start note-item"
                                                data-id="{{ $note->id }}"
                                            >
                                                <div class="flex-grow-1">
                                                    <div class="note-content">{{ $note->content }}</div>
                                                </div>

                                                <div class="note-meta">
                                                    <span class="badge {{ $badgeClass }}">
                                                        {{ $creator->name ?? '-' }}
                                                    </span>

                                                    <small class="text-muted">
                                                        {{ \Morilog\Jalali\Jalalian::fromDateTime($note->created_at)->format('Y/m/d H:i') }}
                                                    </small>

                                                    @if($canEditNote)
                                                        <button type="button" class="btn btn-sm btn-warning edit-note">
                                                            <i class="bi bi-pencil"></i>
                                                            ویرایش
                                                        </button>
                                                    @endif

                                                    @if($canDeleteNote)
                                                        <button type="button" class="btn btn-sm btn-danger delete-note">
                                                            <i class="bi bi-trash"></i>
                                                            حذف
                                                        </button>
                                                    @endif
                                                </div>
                                            </li>
                                        @empty
                                            <li class="list-group-item text-muted empty-notes-item">
                                                هنوز یادداشتی ثبت نشده است.
                                            </li>
                                        @endforelse
                                    </ul>

                                    @if($canEditNote)
                                        <form class="note-create-form mt-3">
                                            <textarea
                                                class="form-control mb-2 note-textarea"
                                                name="content"
                                                rows="3"
                                                placeholder="یادداشت جدید"
                                                required
                                            ></textarea>

                                            <button type="submit" class="btn btn-success w-100 note-submit-btn">
                                                ثبت یادداشت
                                            </button>
                                        </form>
                                    @endif
                                </div>

                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">بستن</button>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach

                {{-- صفحه‌بندی --}}
                <div class="d-flex justify-content-center mt-3">
                    {{ $customers->appends([
                        'search'    => request('search'),
                        'sort'      => request('sort'),
                        'note_from' => request('note_from'),
                        'note_to'   => request('note_to'),
                    ])->links('pagination::bootstrap-5') }}
                </div>
            </div>
        </div>
    </div>

    <script>
        $(function () {
            if ($('#note_from').length) {
                $('#note_from, #note_to').persianDatepicker({
                    format: 'YYYY/MM/DD',
                    initialValue: false,
                    autoClose: true
                });
            }
        });
    </script>

    <script>
        window.customerNotesConfig = {
            updateUrlTemplate: @json(route('admin.customers.notes.update', ['note' => '__NOTE_ID__'])),
            destroyUrlTemplate: @json(route('admin.customers.notes.destroy', ['note' => '__NOTE_ID__'])),
            canEditNote: @json($canEditNote),
            canDeleteNote: @json($canDeleteNote),
        };
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

            function buildUrl(template, id) {
                return template.replace('__NOTE_ID__', String(id));
            }

            function escapeHtml(value) {
                return String(value ?? '')
                    .replaceAll('&', '&amp;')
                    .replaceAll('<', '&lt;')
                    .replaceAll('>', '&gt;')
                    .replaceAll('"', '&quot;')
                    .replaceAll("'", '&#039;');
            }

            function getBadgeClass(role) {
                if (role === 'Admin') return 'bg-primary';
                if (role === 'Marketer') return 'bg-warning text-dark';
                return 'bg-secondary';
            }

            function showAlert(container, type, message) {
                if (!container) return;

                container.innerHTML = `
                    <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                        ${escapeHtml(message)}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="بستن"></button>
                    </div>
                `;
            }

            async function requestJson(url, options = {}) {
                const headers = {
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                    ...(options.body ? { 'Content-Type': 'application/json' } : {}),
                    ...(options.headers || {})
                };

                const response = await fetch(url, {
                    credentials: 'same-origin',
                    ...options,
                    headers
                });

                const contentType = response.headers.get('content-type') || '';
                const rawText = await response.text();

                let data = {};
                if (rawText && contentType.includes('application/json')) {
                    try {
                        data = JSON.parse(rawText);
                    } catch (e) {
                        data = {};
                    }
                }

                if (!response.ok) {
                    if (response.status === 419) {
                        throw new Error('نشست شما منقضی شده است. صفحه را رفرش کن و دوباره امتحان کن.');
                    }

                    if (response.status === 422) {
                        const validationErrors = data.errors
                            ? Object.values(data.errors).flat().join(' | ')
                            : 'داده‌های واردشده معتبر نیستند.';
                        throw new Error(validationErrors);
                    }

                    throw new Error(data.message || `خطای سرور (${response.status})`);
                }

                return data;
            }

            function removeEmptyState(list) {
                const emptyItem = list.querySelector('.empty-notes-item');
                if (emptyItem) {
                    emptyItem.remove();
                }
            }

            function noteItemTemplate(note) {
                const badgeClass = getBadgeClass(note.creator_role);

                const editBtn = window.customerNotesConfig.canEditNote
                    ? `
                        <button type="button" class="btn btn-sm btn-warning edit-note">
                            <i class="bi bi-pencil"></i>
                            ویرایش
                        </button>
                    `
                    : '';

                const deleteBtn = window.customerNotesConfig.canDeleteNote
                    ? `
                        <button type="button" class="btn btn-sm btn-danger delete-note">
                            <i class="bi bi-trash"></i>
                            حذف
                        </button>
                    `
                    : '';

                return `
                    <li class="list-group-item d-flex justify-content-between align-items-start note-item" data-id="${note.id}">
                        <div class="flex-grow-1">
                            <div class="note-content">${escapeHtml(note.content)}</div>
                        </div>

                        <div class="note-meta">
                            <span class="badge ${badgeClass}">${escapeHtml(note.creator)}</span>
                            <small class="text-muted">${escapeHtml(note.created_at)}</small>
                            ${editBtn}
                            ${deleteBtn}
                        </div>
                    </li>
                `;
            }

            async function createNote(modal, form) {
                const alertArea = modal.querySelector('.notes-alert-area');
                const list = modal.querySelector('.notes-list');
                const textarea = form.querySelector('.note-textarea');
                const submitBtn = form.querySelector('.note-submit-btn');
                const storeUrl = modal.dataset.storeUrl;
                const content = textarea.value.trim();

                if (!content) {
                    showAlert(alertArea, 'warning', 'متن یادداشت نمی‌تواند خالی باشد.');
                    return;
                }

                submitBtn.disabled = true;

                try {
                    const data = await requestJson(storeUrl, {
                        method: 'POST',
                        body: JSON.stringify({ content })
                    });

                    if (!data.success || !data.note) {
                        throw new Error('ثبت یادداشت ناموفق بود.');
                    }

                    removeEmptyState(list);
                    list.insertAdjacentHTML('afterbegin', noteItemTemplate(data.note));
                    textarea.value = '';
                    showAlert(alertArea, 'success', 'یادداشت با موفقیت ثبت شد.');
                } catch (error) {
                    showAlert(alertArea, 'danger', error.message || 'خطا در ثبت یادداشت.');
                } finally {
                    submitBtn.disabled = false;
                }
            }

            async function updateNote(noteItem, modal) {
                const alertArea = modal.querySelector('.notes-alert-area');
                const noteId = noteItem.dataset.id;
                const contentNode = noteItem.querySelector('.note-content');
                const oldContent = contentNode?.textContent?.trim() || '';
                const newContent = prompt('ویرایش یادداشت:', oldContent);

                if (newContent === null) return;

                const cleanContent = newContent.trim();
                if (!cleanContent) {
                    showAlert(alertArea, 'warning', 'متن یادداشت نمی‌تواند خالی باشد.');
                    return;
                }

                try {
                    const data = await requestJson(
                        buildUrl(window.customerNotesConfig.updateUrlTemplate, noteId),
                        {
                            method: 'PATCH',
                            body: JSON.stringify({ content: cleanContent })
                        }
                    );

                    if (!data.success) {
                        throw new Error('ویرایش یادداشت ناموفق بود.');
                    }

                    contentNode.textContent = data.content ?? cleanContent;
                    showAlert(alertArea, 'success', 'یادداشت با موفقیت ویرایش شد.');
                } catch (error) {
                    showAlert(alertArea, 'danger', error.message || 'خطا در ویرایش یادداشت.');
                }
            }

            async function deleteNote(noteItem, modal, button) {
                const alertArea = modal.querySelector('.notes-alert-area');
                const list = modal.querySelector('.notes-list');
                const noteId = noteItem.dataset.id;

                if (!confirm('آیا از حذف این یادداشت مطمئن هستی؟')) return;

                button.disabled = true;

                try {
                    const data = await requestJson(
                        buildUrl(window.customerNotesConfig.destroyUrlTemplate, noteId),
                        { method: 'DELETE' }
                    );

                    if (!data.success) {
                        throw new Error('حذف یادداشت ناموفق بود.');
                    }

                    noteItem.remove();

                    if (!list.querySelector('.note-item')) {
                        list.innerHTML = `
                            <li class="list-group-item text-muted empty-notes-item">
                                هنوز یادداشتی ثبت نشده است.
                            </li>
                        `;
                    }

                    showAlert(alertArea, 'success', 'یادداشت با موفقیت حذف شد.');
                } catch (error) {
                    button.disabled = false;
                    showAlert(alertArea, 'danger', error.message || 'خطا در حذف یادداشت.');
                }
            }

            document.addEventListener('submit', async (event) => {
                const form = event.target.closest('.note-create-form');
                if (!form) return;

                event.preventDefault();

                const modal = form.closest('.notes-modal');
                if (!modal) return;

                await createNote(modal, form);
            });

            document.addEventListener('click', async (event) => {
                const editButton = event.target.closest('.edit-note');
                const deleteButton = event.target.closest('.delete-note');

                if (editButton) {
                    const noteItem = editButton.closest('.note-item');
                    const modal = editButton.closest('.notes-modal');

                    if (noteItem && modal) {
                        await updateNote(noteItem, modal);
                    }
                }

                if (deleteButton) {
                    const noteItem = deleteButton.closest('.note-item');
                    const modal = deleteButton.closest('.notes-modal');

                    if (noteItem && modal) {
                        await deleteNote(noteItem, modal, deleteButton);
                    }
                }
            });
        });
    </script>
</x-app-layout>