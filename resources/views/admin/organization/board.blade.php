<x-layouts.app>
    <x-slot name="header">
        @include('admin.users.partials.section-tabs')
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2" dir="rtl">
            <h1 class="fs-4 fw-bold mb-0">ساختار سازمانی و مدیریت پرسنل</h1>
            <a href="{{ route('admin.users.create') }}" class="btn btn-primary btn-sm">+ افزودن پرسنل</a>
        </div>
    </x-slot>

    <link rel="stylesheet" href="{{ asset('css/org-personnel.css') }}">
    <section
        id="org-board"
        class="org2"
        dir="rtl"
        data-api="{{ route('admin.organization.index') }}"
        data-departments-url="{{ url('/admin/organization/departments') }}"
        data-people-url="{{ url('/admin/organization/personnel') }}"
        data-create-user-url="{{ route('admin.users.create') }}"
        data-edit-manager-url="{{ url('/admin/users') }}"
        data-activate-url="{{ url('/admin/users') }}"
        data-current-user="{{ auth()->id() }}"
        data-csrf="{{ csrf_token() }}"
    >
        <header class="org2-hero">
            <div>
                <p class="org2-kicker">CRM آریا گستر / ساختار سازمانی</p>
                <h2>مدیریت سازمان، ساده و شفاف</h2>
                <p>دپارتمان‌ها، مدیران، سرپرستان و کارکنان را در یک فضای یکپارچه مدیریت کنید.</p>
            </div>
            <div class="org2-actions">
                <button type="button" id="org2-add-dept" class="org2-btn org2-btn-muted">+ ایجاد واحد</button>
                <a href="{{ route('admin.users.create') }}" class="org2-btn org2-btn-primary">+ افزودن پرسنل</a>
            </div>
        </header>

        @if(session('success'))
            <div class="org2-alert" role="status">{{ session('success') }}</div>
        @endif
        @if(config('report_review.enabled', false))
            <p class="org2-footnote"><a href="{{ route('report-review.inbox') }}">ورود به صندوق بررسی گزارش‌های من ←</a></p>
        @endif
        <div id="org2-alert" class="org2-alert" role="alert" hidden></div>

        <div class="org2-stats" aria-label="آمار سازمان">
            <div class="org2-stat"><span>کل پرسنل</span><strong id="org2-stat-total">—</strong><small>همه حساب‌ها</small></div>
            <div class="org2-stat"><span>پرسنل فعال</span><strong id="org2-stat-active">—</strong><small>فعال و در دسترس</small></div>
            <div class="org2-stat"><span>واحدهای سازمانی</span><strong id="org2-stat-depts">—</strong><small>دپارتمان‌های تعریف‌شده</small></div>
            <div class="org2-stat"><span>بدون واحد</span><strong id="org2-stat-unassigned">—</strong><small>نیازمند تعیین جایگاه</small></div>
        </div>

        <div class="org2-headline">
            <h3>واحدهای سازمان</h3>
            <button type="button" id="org2-manage-depts" class="org2-link">مدیریت واحدها <span aria-hidden="true">←</span></button>
        </div>
        <div id="org2-dept-cards" class="org2-units" aria-live="polite">
            <p class="org2-empty">در حال دریافت واحدهای سازمانی…</p>
        </div>

        <div class="org2-layout">
            <main class="org2-panel org2-panel-main">
                <div class="org2-panel-head">
                    <div>
                        <h3>چارت سازمانی</h3>
                        <p>ساختار فعلی، شامل مدیر مستقیم و سرپرستان اختیاری</p>
                    </div>
                    <div class="org2-switch" role="group" aria-label="نحوه نمایش">
                        <button type="button" data-view="tree" class="is-current" aria-pressed="true">درختی</button>
                        <button type="button" data-view="list" aria-pressed="false">لیستی</button>
                    </div>
                </div>
                <form class="org2-filters" id="org2-filter-form" role="search">
                    <label class="org2-sr" for="org2-search">جستجوی پرسنل</label>
                    <input id="org2-search" type="search" placeholder="جستجوی نام، موبایل، سمت یا نقش…" autocomplete="off">
                    <label class="org2-sr" for="org2-dept-filter">فیلتر واحد</label>
                    <select id="org2-dept-filter"><option value="">همه واحدها</option></select>
                    <label class="org2-sr" for="org2-role-filter">فیلتر نقش</label>
                    <select id="org2-role-filter"><option value="">همه دسترسی‌ها</option></select>
                    <button type="reset" class="org2-btn org2-btn-muted">پاک‌کردن</button>
                </form>
                <div class="org2-content" id="org2-tree" aria-live="polite"></div>
                <div class="org2-content" id="org2-list" hidden></div>
            </main>

            <aside class="org2-panel org2-detail" aria-label="اطلاعات شخص انتخاب‌شده">
                <div class="org2-panel-head">
                    <h3>اطلاعات و جایگاه فرد</h3>
                </div>
                <div id="org2-detail-body">
                    <div class="org2-empty">یک نفر را از چارت سازمانی انتخاب کنید تا اطلاعات و مسیر گزارش‌دهی او نمایش داده شود.</div>
                </div>
            </aside>
        </div>

        @isset($unassignedUsers)
            <details class="org2-legacy-unassigned">
                <summary>کاربران بدون مدیر مستقیم ({{ $unassignedUsers->count() }})</summary>
                <p>افرادی که مدیر مستقیم صریح ندارند ممکن است از مدیر پیش‌فرض واحد سازمانی استفاده کنند.</p>
                <div class="org2-chips">
                    @forelse($unassignedUsers as $unassignedUser)
                        <button type="button" class="org2-chip" data-person-id="{{ $unassignedUser->id }}">{{ $unassignedUser->name }}</button>
                    @empty
                        <span>موردی وجود ندارد.</span>
                    @endforelse
                </div>
            </details>
        @endisset

        <p class="org2-footnote">نقش‌های نرم‌افزاری (Role) مستقل از سمت سازمانی هستند. تغییر سمت به‌تنهایی مجوزهای حساب را تغییر نمی‌دهد. گردش تأیید گزارش‌کار در فاز بعدی به این ساختار متصل می‌شود.</p>

        <div id="org2-dialog-backdrop" class="org2-backdrop" hidden>
            <div class="org2-dialog" role="dialog" aria-modal="true" aria-labelledby="org2-dialog-title">
                <div class="org2-dialog-top">
                    <h3 id="org2-dialog-title">ویرایش</h3>
                    <button type="button" class="org2-close" id="org2-close" aria-label="بستن">×</button>
                </div>
                <form id="org2-edit-person" hidden>
                    <label for="org2-person-name">کارمند</label>
                    <input id="org2-person-name" readonly>
                    <label for="org2-person-dept">واحد سازمانی</label>
                    <select id="org2-person-dept" name="department_id"></select>
                    <label for="org2-person-position">سمت سازمانی (مستقل از دسترسی)</label>
                    <select id="org2-person-position" name="org_position">
                        <option value="">بدون سمت</option>
                        <option value="staff">کارشناس / کارمند</option>
                        <option value="supervisor">سرپرست</option>
                        <option value="department_manager">مدیر واحد</option>
                        <option value="internal_manager">مدیر داخلی</option>
                        <option value="executive_manager">مدیر اجرایی</option>
                        <option value="ceo">مدیرعامل</option>
                    </select>
                    <label for="org2-person-manager">مدیر مستقیم</label>
                    <select id="org2-person-manager" name="manager_id"></select>
                    <p class="org2-help">«تبعیت از واحد» یعنی اگر سرپرست یا مدیر واحد تعریف شده باشد، مسیر به‌صورت خودکار از آن محاسبه شود. انتساب صریح بر این تنظیمات اولویت دارد.</p>
                    <button class="org2-btn org2-btn-primary org2-submit" type="submit">ذخیره جایگاه سازمانی</button>
                </form>

                <form id="org2-edit-dept" hidden>
                    <input type="hidden" name="department_id" id="org2-dept-id">
                    <label for="org2-dept-name">نام واحد *</label>
                    <input id="org2-dept-name" name="name" required maxlength="255" placeholder="مثلاً پشتیبانی">
                    <label for="org2-dept-supervisor">سرپرست واحد (اختیاری)</label>
                    <select id="org2-dept-supervisor" name="supervisor_id"></select>
                    <label for="org2-dept-manager">مدیر واحد (اختیاری)</label>
                    <select id="org2-dept-manager" name="manager_id"></select>
                    <label for="org2-dept-parent">مسئول بالادستی (اختیاری)</label>
                    <select id="org2-dept-parent" name="parent_manager_id"></select>
                    <p class="org2-help">سرپرست و مدیر باید عضو همین واحد باشند؛ مسئول بالادستی می‌تواند از مدیریت مرکزی انتخاب شود. سه مسئول باید افراد متفاوتی باشند.</p>
                    <button class="org2-btn org2-btn-primary org2-submit" type="submit">ذخیره واحد سازمانی</button>
                </form>
                <p id="org2-form-error" class="org2-form-error" role="alert" hidden></p>
            </div>
        </div>
    </section>
    <script src="{{ asset('js/org-personnel.js') }}" defer></script>
</x-layouts.app>
