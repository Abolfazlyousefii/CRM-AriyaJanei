<x-app-layout>
    @php
        $user = auth()->user();
        $now = \Hekmatinasser\Verta\Verta::now();
        $jDay = $now->day;
        $showEvalCard = ($jDay >= 28 || $jDay <= 3);

        $linksAdmin = [
            ['title' => 'کاربران بازاریاب', 'route' => 'admin.marketers.index', 'icon' => 'users'],
            ['title' => 'کاربران مهمان', 'route' => 'admin.guests.index', 'icon' => 'user'],
            ['title' => 'مدیریت کاربران', 'route' => 'admin.users.index', 'icon' => 'users'],
            ['title' => 'مدیریت محصولات و پورسانت', 'route' => 'admin.products.index', 'icon' => 'archive'],
            ['title' => 'محصولات سایت', 'route' => 'products.index', 'icon' => 'box'],
            ['title' => 'مشتریان و شماره‌ها', 'route' => 'admin.customersAdmin.index', 'icon' => 'user-group'],
            ['title' => 'لاگ فعالیت‌ها', 'route' => 'admin.activity_logs.index', 'icon' => 'clipboard'],
            ['title' => 'دسته‌بندی‌ها', 'route' => 'admin.categories.index', 'icon' => 'tag'],
            ['title' => 'نحوه آشنایی', 'route' => 'admin.referenceType.index', 'icon' => 'question'],
          
         
            ['title' => 'گزارش‌های مدیریتی', 'route' => 'admin.reports', 'icon' => 'chart'],
            ['title' => 'فرم رضایت مشتری', 'route' => 'customer-satisfaction-forms.index', 'icon' => 'doc'],
            ['title' => 'مدیریت اطلاعیه‌ها', 'route' => 'announcements.index', 'icon' => 'megaphone'],
        ];

        $linksMarketer = [
            ['title' => 'مشتریان من', 'route' => 'marketer.customers.index', 'icon' => 'users'],
            ['title' => 'فروش (درحال توسعه)', 'route' => 'dashboard', 'icon' => 'chart'],
            ['title' => 'مشتریان و شماره‌ها', 'route' => 'customersAdmin2.index', 'icon' => 'user-group'],
            ['title' => 'ثبت سفارش', 'route' => 'marketer.orders.create', 'icon' => 'doc'],
        ];

        $linksSales = [
            ['title' => 'اطلاعات ثبت شده در فرم', 'route' => 'admin.contacts', 'icon' => 'users'],
            ['title' => 'ثبت پیش فاکتور', 'url' => 'http://192.168.1.30:8080/preinvoice/create', 'icon' => 'doc'],
            ['title' => 'نمایش کالا ها و موجودی', 'url' => 'http://192.168.1.30:8080/products', 'icon' => 'box'],
        ];

        $linksUser = [
            ['title' => 'گزارش‌های من', 'route' => 'user.reports.index', 'icon' => 'doc'],
            ['title' => 'ثبت مرخصی', 'route' => 'leaves', 'icon' => 'calendar'],
            ['title' => 'یادآورها', 'route' => 'reminders.index', 'icon' => 'bell'],
            ['title' => 'مدیریت درخواست‌ها', 'route' => 'requests.index', 'icon' => 'bolt'],
        ];

        if ($showEvalCard) {
         
        }

        $linksManager = [
            ['title' => 'مدیریت گزارش کارها', 'route' => 'user.reports.reportsManagment', 'icon' => 'doc'],
            ['title' => 'مدیریت مرخصی‌ها', 'route' => 'leaves', 'icon' => 'calendar'],
            ['title' => 'مدیریت تسک‌ها', 'route' => 'admin.tasks.index', 'icon' => 'checklist'],
        ];

        $linksCustomerReview = [
            ['title' => 'فرم رضایت مشتری', 'route' => 'customer-satisfaction-forms.index', 'icon' => 'doc'],
        ];

        $linksInternalManager = [
            ['title' => 'فرم رضایت مشتری', 'route' => 'customer-satisfaction-forms.index', 'icon' => 'doc'],
            ['title' => 'مدیریت اطلاعیه‌ها', 'route' => 'announcements.index', 'icon' => 'megaphone'],
        ];

        $roleLinks = [
            'Admin' => $linksAdmin,
            'Manager' => $linksManager,
            'Marketer' => $linksMarketer,
            'Sales' => $linksSales,
            'User' => $linksUser,
            'customer_review' => $linksCustomerReview,
            'internalManager' => $linksInternalManager,
            'InternalManager' => $linksInternalManager,
        ];

        $allLinks = [];
        foreach ($roleLinks as $role => $links) {
            if ($user->hasRole($role)) {
                foreach ($links as $item) {
                    $linkKey = $item['route'] ?? $item['url'];
                    $allLinks[$linkKey] = $item;
                }
            }
        }
        $allLinks = array_values($allLinks);

        $groups = [
            'customers' => [
                'title' => 'مشتریان و فروش',
                'desc'  => 'مشتریان، سفارش، اطلاعات فرم و پیگیری فروش',
                'tone'  => 'primary',
                'icon'  => 'users',
                'items' => [],
            ],
            'reports' => [
                'title' => 'گزارش‌ها و ارزیابی',
                'desc'  => 'گزارش‌های کاری، فرم‌های ارزیابی و نتایج',
                'tone'  => 'success',
                'icon'  => 'chart',
                'items' => [],
            ],
            'requests' => [
                'title' => 'درخواست‌ها و ارتباطات',
                'desc'  => 'مرخصی، یادآورها و درخواست‌ها',
                'tone'  => 'indigo',
                'icon'  => 'chat',
                'items' => [],
            ],
            'management' => [
                'title' => 'مدیریت سیستم',
                'desc'  => 'کاربران، محصولات، دسته‌بندی‌ها و لاگ‌ها',
                'tone'  => 'warning',
                'icon'  => 'archive',
                'items' => [],
            ],
            'forms' => [
                'title' => 'فرم‌ها و رضایت مشتری',
                'desc'  => 'فرم رضایت مشتری و فرم‌های مرتبط',
                'tone'  => 'teal',
                'icon'  => 'doc',
                'items' => [],
            ],
        ];

        foreach ($allLinks as $link) {
            $route = $link['route'] ?? null;

            if (in_array($route, [
                'marketer.customers.index',
                'admin.customersAdmin.index',
                'customersAdmin2.index',
                'marketer.orders.create',
                'admin.contacts',
                'dashboard',
            ])) {
                $groups['customers']['items'][$route] = $link;
            } elseif (in_array($route, [
                'user.reports.index',
                'user.reports.reportsManagment',
                'admin.evaluations.forms.index',
                'admin.evaluations.monthly',
                'evaluations.index',
                'admin.reports',
            ])) {
                $groups['reports']['items'][$route] = $link;
            } elseif (in_array($route, [
                'leaves',
                'reminders.index',
                'requests.index',
                'admin.tasks.index',
                'announcements.index',
            ])) {
                $groups['requests']['items'][$route] = $link;
            } elseif (in_array($route, [
                'admin.marketers.index',
                'admin.guests.index',
                'admin.users.index',
                'admin.products.index',
                'products.index',
                'admin.activity_logs.index',
                'admin.categories.index',
                'admin.referenceType.index',
            ])) {
                $groups['management']['items'][$route] = $link;
            } elseif (in_array($route, [
                'customer-satisfaction-forms.index',
            ])) {
                $groups['forms']['items'][$route] = $link;
            } else {
                $groups['management']['items'][$route] = $link;
            }
        }

        $groups = array_filter($groups, fn($g) => count($g['items']) > 0);

        $statCards = [];
        if ($user->hasRole('Marketer')) {
            $statCards[] = ['tone' => 'primary', 'title' => 'مشتری جدید', 'value' => $newCustomersCount, 'desc' => '۲۴ ساعت گذشته', 'emoji' => '📌'];
        }
        if ($user->hasRole('User') && !$user->hasAnyRole(['Admin','Manager'])) {
            $statCards[] = ['tone' => 'success', 'title' => 'تسک‌های امروز', 'value' => $todayTasksCount, 'desc' => 'امروز', 'emoji' => '✅'];
        }
        if ($user->hasAnyRole(['Admin','Manager'])) {
            $statCards[] = ['tone' => 'primary', 'title' => 'مشتری جدید', 'value' => $newCustomersCount, 'desc' => '۲۴ ساعت گذشته', 'emoji' => '📌'];
            $statCards[] = ['tone' => 'success', 'title' => 'یادداشت جدید', 'value' => $newNotesCount, 'desc' => '۲۴ ساعت گذشته', 'emoji' => '📝'];
            $statCards[] = ['tone' => 'purple', 'title' => 'گزارش جدید', 'value' => $newReportsCount, 'desc' => '۲۴ ساعت گذشته', 'emoji' => '📑'];
        }

        $showTasksWidget = $user->hasAnyRole(['Marketer','User','Manager']);
        $taskTotal = $tasks->count();
        $taskDone  = $tasks->where('completed', true)->count();
        $taskPct   = $taskTotal > 0 ? (int) round(($taskDone / $taskTotal) * 100) : 0;

        if (!function_exists('dash_icon_pro')) {
            function dash_icon_pro($name){
                $icons = [
                    'users' => '<svg xmlns="http://www.w3.org/2000/svg" class="dash-icon-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a4 4 0 0 0-3-3.87M9 20h6M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8z"/></svg>',
                    'user' => '<svg xmlns="http://www.w3.org/2000/svg" class="dash-icon-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" d="M5.121 17.804A4 4 0 0 1 8 16h8a4 4 0 0 1 2.879 1.804M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8z"/></svg>',
                    'archive' => '<svg xmlns="http://www.w3.org/2000/svg" class="dash-icon-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" d="M20 13V6a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v7M4 13h16M5 20h14a2 2 0 0 0 2-2v-5H3v5a2 2 0 0 0 2 2z"/></svg>',
                    'user-group' => '<svg xmlns="http://www.w3.org/2000/svg" class="dash-icon-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a4 4 0 0 0-3-3.87M9 20h6M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM7 8a4 4 0 1 1 0-8 4 4 0 0 1 0 8z"/></svg>',
                    'clipboard' => '<svg xmlns="http://www.w3.org/2000/svg" class="dash-icon-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 0 0-2 2v14h14V7a2 2 0 0 0-2-2h-2M9 5V3h6v2M9 12h6M9 16h6"/></svg>',
                    'tag' => '<svg xmlns="http://www.w3.org/2000/svg" class="dash-icon-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" d="M7 7a2 2 0 1 1 4 0 2 2 0 0 1-4 0zM5 7h.01M7 7v10m0 0-3 3m3-3h10"/></svg>',
                    'question' => '<svg xmlns="http://www.w3.org/2000/svg" class="dash-icon-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0zM8 10h.01M12 10h.01M16 10h.01M12 14v.01"/></svg>',
                    'doc' => '<svg xmlns="http://www.w3.org/2000/svg" class="dash-icon-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 4H7a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h5l5 5v9a2 2 0 0 1-2 2z"/></svg>',
                    'chart' => '<svg xmlns="http://www.w3.org/2000/svg" class="dash-icon-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" d="M3 3v18h18M9 17V9m4 8V5m4 12v-6"/></svg>',
                    'calendar' => '<svg xmlns="http://www.w3.org/2000/svg" class="dash-icon-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3M5 11h14M5 21h14a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2z"/></svg>',
                    'bell' => '<svg xmlns="http://www.w3.org/2000/svg" class="dash-icon-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.4-1.4A2 2 0 0 1 18 14V11a6 6 0 1 0-12 0v3a2 2 0 0 1-.6 1.4L4 17h5m6 0a3 3 0 1 1-6 0"/></svg>',
                    'chat' => '<svg xmlns="http://www.w3.org/2000/svg" class="dash-icon-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0zM8 10h8M8 14h5"/></svg>',
                    'bolt' => '<svg xmlns="http://www.w3.org/2000/svg" class="dash-icon-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>',
                    'box' => '<svg xmlns="http://www.w3.org/2000/svg" class="dash-icon-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" d="M20 12l-8 5-8-5m16 0-8-5-8 5m16 0v6a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-6"/></svg>',
                    'checklist' => '<svg xmlns="http://www.w3.org/2000/svg" class="dash-icon-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" d="M9 5h12M9 12h12M9 19h12M5 5h.01M5 12h.01M5 19h.01"/></svg>',
                    'megaphone' => '<svg xmlns="http://www.w3.org/2000/svg" class="dash-icon-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" d="M3 11v2a2 2 0 0 0 2 2h2l3 5h2l-1.5-5H15l4 3V6l-4 3H5a2 2 0 0 0-2 2z"/></svg>',
                    'spark' => '<svg xmlns="http://www.w3.org/2000/svg" class="dash-icon-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" d="M12 3l1.7 4.3L18 9l-4.3 1.7L12 15l-1.7-4.3L6 9l4.3-1.7L12 3zM19 16l.8 2.2L22 19l-2.2.8L19 22l-.8-2.2L16 19l2.2-.8L19 16zM5 14l.8 2.2L8 17l-2.2.8L5 20l-.8-2.2L2 17l2.2-.8L5 14z"/></svg>',
                    'arrow-left' => '<svg xmlns="http://www.w3.org/2000/svg" class="dash-icon-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" d="M15 6l-6 6 6 6"/></svg>',
                    'check' => '<svg xmlns="http://www.w3.org/2000/svg" class="dash-icon-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>',
                    'close' => '<svg xmlns="http://www.w3.org/2000/svg" class="dash-icon-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" d="M6 6l12 12M18 6L6 18"/></svg>',
                ];
                return $icons[$name] ?? '';
            }
        }
    @endphp
    @if($todayReminders->count() > 0)
   
    <div class="modal fade" id="reminderModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">یادآورهای امروز</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <ul class="list-group">
              @foreach($todayReminders as $reminder)
                <li class="list-group-item d-flex justify-content-between align-items-center">
                  <div>
                      <strong>{{ $reminder->title }}</strong><br>
                      <small>{{ $reminder->description }}</small><br>
                      <small>{{ \Hekmatinasser\Verta\Verta::instance($reminder->remind_at)->format('Y/m/d H:i') }}</small>
                  </div>
                  <form action="{{ route('reminders.markAsSeen', $reminder->id) }}" method="POST">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="btn btn-success btn-sm">خواندم</button>
                  </form>
                </li>
              @endforeach
            </ul>
          </div>
        </div>
      </div>
    </div>
    @endif
    
    <x-slot name="header">
        <div class="smart-dashboard dashboard-rtl">
            <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-sm-between gap-2">
                <div class="text-end">
                    <h2 class="sd-title fw-bold fs-3 mb-1">داشبورد</h2>
                    <div class="sd-muted small">
                        خوش آمدی <span class="fw-semibold sd-text">{{ Auth::user()->name }}</span>
                    </div>
                </div>
<a href="http://192.168.1.30:8080/"
   id="autoLoginBtn"
   class="btn btn-primary align-items-center gap-2 ">
    <i class="bi bi-speedometer2"></i>
    <span>ورود به سیستم انبار</span>
</a>
                <div class="sd-muted small text-end">
                    {{ \Hekmatinasser\Verta\Verta::now()->format('l، j F Y') }}
                </div>
            </div>
        </div>
    </x-slot>

    <style>
        .smart-dashboard {
            --sd-bg: #f4f7fb;
            --sd-bg-soft: #eef3f9;
            --sd-surface: #ffffff;
            --sd-surface-2: #f8fbff;
            --sd-surface-3: #f1f6fc;
            --sd-border: #e2e8f0;
            --sd-text: #0f172a;
            --sd-muted: #64748b;
            --sd-shadow: 0 10px 28px rgba(15, 23, 42, .06);
            --sd-shadow-hover: 0 16px 42px rgba(15, 23, 42, .12);
            --sd-progress-bg: rgba(148, 163, 184, .18);

            --tone-primary-bg: rgba(59,130,246,.14);
            --tone-primary-text: #2563eb;
            --tone-success-bg: rgba(16,185,129,.14);
            --tone-success-text: #059669;
            --tone-purple-bg: rgba(139,92,246,.14);
            --tone-purple-text: #7c3aed;
            --tone-indigo-bg: rgba(99,102,241,.14);
            --tone-indigo-text: #4f46e5;
            --tone-warning-bg: rgba(245,158,11,.14);
            --tone-warning-text: #d97706;
            --tone-teal-bg: rgba(20,184,166,.14);
            --tone-teal-text: #0f766e;
            --tone-danger-bg: rgba(239,68,68,.12);
            --tone-danger-text: #dc2626;
        }

        html.dark .smart-dashboard,
        body.dark .smart-dashboard,
        .dark .smart-dashboard,
        html[data-bs-theme="dark"] .smart-dashboard,
        body[data-bs-theme="dark"] .smart-dashboard,
        [data-bs-theme="dark"] .smart-dashboard {
            --sd-bg: #08101c;
            --sd-bg-soft: #0d1727;
            --sd-surface: #0f172a;
            --sd-surface-2: #152034;
            --sd-surface-3: #1a263d;
            --sd-border: rgba(148,163,184,.18);
            --sd-text: #e5edf8;
            --sd-muted: #94a3b8;
            --sd-shadow: 0 12px 30px rgba(0,0,0,.28);
            --sd-shadow-hover: 0 18px 40px rgba(0,0,0,.38);
            --sd-progress-bg: rgba(148,163,184,.16);

            --tone-primary-bg: rgba(96,165,250,.18);
            --tone-primary-text: #93c5fd;
            --tone-success-bg: rgba(52,211,153,.18);
            --tone-success-text: #6ee7b7;
            --tone-purple-bg: rgba(167,139,250,.18);
            --tone-purple-text: #c4b5fd;
            --tone-indigo-bg: rgba(129,140,248,.18);
            --tone-indigo-text: #a5b4fc;
            --tone-warning-bg: rgba(251,191,36,.18);
            --tone-warning-text: #fcd34d;
            --tone-teal-bg: rgba(45,212,191,.18);
            --tone-teal-text: #5eead4;
            --tone-danger-bg: rgba(248,113,113,.14);
            --tone-danger-text: #fda4af;
        }

        .dashboard-rtl,
        .dashboard-rtl * {
            direction: rtl;
        }

        .sd-wrap {
            min-height: 100%;
            background:
                radial-gradient(circle at top right, rgba(59,130,246,.05), transparent 22%),
                radial-gradient(circle at top left, rgba(139,92,246,.05), transparent 18%),
                linear-gradient(180deg, var(--sd-bg) 0%, var(--sd-bg-soft) 50%, var(--sd-bg) 100%);
            color: var(--sd-text);
        }

        .sd-title,
        .sd-text,
        .sd-section-title,
        .sd-modal-title,
        .sd-stat-number,
        .sd-link-title,
        .smart-dashboard .form-label,
        .smart-dashboard .btn,
        .smart-dashboard .modal-title {
            color: var(--sd-text) !important;
        }

        .sd-muted,
        .sd-link-subtitle,
        .sd-group-desc,
        .sd-stat-label,
        .smart-dashboard small,
        .smart-dashboard .text-muted {
            color: var(--sd-muted) !important;
        }

        .sd-card,
        .smart-dashboard .card,
        .smart-dashboard .modal-content,
        .smart-dashboard .list-group-item,
        .smart-dashboard .dropdown-menu {
            background: var(--sd-surface) !important;
            border: 1px solid var(--sd-border) !important;
            color: var(--sd-text) !important;
            border-radius: 22px;
            box-shadow: var(--sd-shadow);
            transition: .25s ease;
        }

        .sd-card:hover {
            box-shadow: var(--sd-shadow-hover);
        }

        .sd-surface-soft {
            background: var(--sd-surface-2) !important;
            color: var(--sd-text) !important;
            border: 1px solid var(--sd-border) !important;
        }

        .sd-border {
            border-color: var(--sd-border) !important;
        }

        .smart-dashboard .modal-header,
        .smart-dashboard .modal-body,
        .smart-dashboard .modal-footer {
            background: var(--sd-surface) !important;
            color: var(--sd-text) !important;
            border-color: var(--sd-border) !important;
        }

        .smart-dashboard .list-group-item {
            border-radius: 16px !important;
            margin-bottom: .6rem;
        }

        .smart-dashboard .form-control,
        .smart-dashboard .form-select,
        .smart-dashboard .dash-input {
            background: var(--sd-surface-2) !important;
            color: var(--sd-text) !important;
            border: 1px solid var(--sd-border) !important;
            border-radius: 14px !important;
        }

        .smart-dashboard .form-control::placeholder,
        .smart-dashboard .form-select::placeholder,
        .smart-dashboard .dash-input::placeholder {
            color: var(--sd-muted) !important;
        }

        .smart-dashboard .form-control:focus,
        .smart-dashboard .form-select:focus,
        .smart-dashboard .dash-input:focus {
            background: var(--sd-surface-2) !important;
            color: var(--sd-text) !important;
            border-color: rgba(59,130,246,.45) !important;
            box-shadow: 0 0 0 .2rem rgba(59,130,246,.12) !important;
        }

        .smart-dashboard .btn-outline-primary {
            border-color: rgba(59,130,246,.35);
        }

        .smart-dashboard .btn-close {
            opacity: 1;
        }

        html.dark .smart-dashboard .btn-close,
        body.dark .smart-dashboard .btn-close,
        .dark .smart-dashboard .btn-close,
        html[data-bs-theme="dark"] .smart-dashboard .btn-close,
        body[data-bs-theme="dark"] .smart-dashboard .btn-close,
        [data-bs-theme="dark"] .smart-dashboard .btn-close {
            filter: invert(1) grayscale(100%);
        }

        .dash-icon-svg {
            width: 22px;
            height: 22px;
        }

        .sd-icon-wrap {
            width: 50px;
            height: 50px;
            min-width: 50px;
            border-radius: 16px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 1px solid transparent;
        }

        .sd-icon-wrap.sm {
            width: 40px;
            height: 40px;
            min-width: 40px;
            border-radius: 14px;
        }

        .sd-mini-btn {
            width: 42px;
            height: 42px;
            min-width: 42px;
            border-radius: 14px;
            border: 1px solid var(--sd-border);
            background: var(--sd-surface-2);
            color: var(--sd-text);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: .2s ease;
        }

        .sd-mini-btn:hover {
            background: var(--sd-surface-3);
            transform: translateY(-1px);
        }

        .tone-primary { background: var(--tone-primary-bg) !important; color: var(--tone-primary-text) !important; border-color: var(--tone-primary-bg) !important; }
        .tone-success { background: var(--tone-success-bg) !important; color: var(--tone-success-text) !important; border-color: var(--tone-success-bg) !important; }
        .tone-purple  { background: var(--tone-purple-bg) !important; color: var(--tone-purple-text) !important; border-color: var(--tone-purple-bg) !important; }
        .tone-indigo  { background: var(--tone-indigo-bg) !important; color: var(--tone-indigo-text) !important; border-color: var(--tone-indigo-bg) !important; }
        .tone-warning { background: var(--tone-warning-bg) !important; color: var(--tone-warning-text) !important; border-color: var(--tone-warning-bg) !important; }
        .tone-teal    { background: var(--tone-teal-bg) !important; color: var(--tone-teal-text) !important; border-color: var(--tone-teal-bg) !important; }
        .tone-danger  { background: var(--tone-danger-bg) !important; color: var(--tone-danger-text) !important; border-color: var(--tone-danger-bg) !important; }

        .sd-pill {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 1px solid transparent;
            border-radius: 999px;
            padding: .42rem .82rem;
            font-size: 12px;
            font-weight: 800;
            line-height: 1;
            white-space: nowrap;
        }

        .sd-section-title {
            font-weight: 900;
        }

        .sd-progress {
            background: var(--sd-progress-bg);
            height: 8px;
            border-radius: 999px;
            overflow: hidden;
        }

        .sd-progress .progress-bar {
            background: linear-gradient(90deg, #3b82f6, #6366f1) !important;
        }

        .sd-task-item,
        .sd-stat-box,
        .sd-link-item,
        .sd-list-item,
        .sd-notice-item {
            background: var(--sd-surface) !important;
            border: 1px solid var(--sd-border) !important;
            border-radius: 16px;
            color: var(--sd-text) !important;
        }

        .sd-task-item {
            padding: .95rem 1rem;
        }

        .sd-stat-box {
            padding: 16px;
            height: 100%;
        }

        .sd-stat-number {
            font-size: 1.55rem;
            font-weight: 900;
            line-height: 1;
            margin-top: .9rem;
        }

        .sd-stat-label {
            font-size: 13px;
            margin-top: 6px;
        }

        .sd-summary-board {
            background: linear-gradient(135deg, var(--sd-surface) 0%, var(--sd-surface-2) 100%) !important;
        }

        .sd-group-card {
            cursor: pointer;
            min-height: 175px;
            text-align: right;
            position: relative;
            overflow: hidden;
        }

        .sd-group-card::after {
            content: "";
            position: absolute;
            left: -30px;
            bottom: -30px;
            width: 110px;
            height: 110px;
            background: radial-gradient(circle, rgba(59,130,246,.10), transparent 65%);
            pointer-events: none;
        }

        .sd-link-item {
            display: flex;
            flex-direction: row-reverse;
            align-items: center;
            justify-content: space-between;
            gap: .9rem;
            text-decoration: none;
            padding: 14px 16px;
            transition: .2s ease;
        }

        .sd-link-item:hover {
            background: var(--sd-surface-2) !important;
            color: var(--sd-text) !important;
            transform: translateY(-1px);
        }

        .sd-badge-list {
            display: flex;
            flex-wrap: wrap;
            gap: .45rem;
            justify-content: flex-start;
        }

        .sd-tag {
            background: var(--sd-surface-2);
            color: var(--sd-text);
            border: 1px solid var(--sd-border);
            border-radius: 999px;
            padding: .34rem .72rem;
            font-size: 12px;
            font-weight: 700;
        }

        .sd-alert {
            background: var(--sd-surface) !important;
            color: var(--sd-text) !important;
            border: 1px solid rgba(239,68,68,.25) !important;
        }

        .sd-soft-scroll::-webkit-scrollbar {
            width: 8px;
        }

        .sd-soft-scroll::-webkit-scrollbar-thumb {
            background: rgba(100,116,139,.35);
            border-radius: 999px;
        }

        .sd-soft-scroll::-webkit-scrollbar-track {
            background: transparent;
        }

        .sd-row-reverse {
            display: flex;
            flex-direction: row-reverse;
            align-items: center;
        }

        .sd-row-reverse-start {
            display: flex;
            flex-direction: row-reverse;
            align-items: flex-start;
        }

        .sd-between-rtl {
            display: flex;
            flex-direction: row-reverse;
            align-items: center;
            justify-content: space-between;
            gap: .75rem;
        }

        .sd-between-rtl-start {
            display: flex;
            flex-direction: row-reverse;
            align-items: flex-start;
            justify-content: space-between;
            gap: .75rem;
        }

        .sd-text-end {
            text-align: right !important;
        }

        .sd-notice-launcher {
            background:
                radial-gradient(circle at top left, rgba(99,102,241,.08), transparent 24%),
                radial-gradient(circle at bottom right, rgba(20,184,166,.08), transparent 22%),
                var(--sd-surface) !important;
        }

        .sd-notice-open-btn {
            width: 62px;
            height: 62px;
            min-width: 62px;
            border: 0;
            border-radius: 18px;
            background: linear-gradient(135deg, #2563eb, #7c3aed);
            color: #fff;
            position: relative;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 18px 40px rgba(59,130,246,.22);
            transition: .2s ease;
        }

        .sd-notice-open-btn:hover {
            transform: translateY(-1px) scale(1.01);
        }

        .sd-notice-open-btn .dash-icon-svg {
            width: 28px;
            height: 28px;
        }

        .sd-notice-open-badge {
            position: absolute;
            top: -7px;
            right: -7px;
            min-width: 24px;
            height: 24px;
            padding: 0 .4rem;
            border-radius: 999px;
            background: #ef4444;
            color: #fff;
            border: 2px solid #fff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            font-weight: 900;
        }

        .sd-notice-item {
            padding: 1rem;
            transition: .2s ease;
            display: flex;
            flex-direction: row-reverse;
            align-items: flex-start;
            justify-content: space-between;
            gap: .85rem;
        }

        .sd-notice-item:hover {
            background: var(--sd-surface-2) !important;
        }

        .sd-notice-title {
            font-size: 14px;
            font-weight: 900;
            margin-bottom: .25rem;
            color: var(--sd-text);
        }

        .sd-notice-desc {
            font-size: 13px;
            color: var(--sd-muted);
            line-height: 1.85;
        }

        .sd-notice-meta {
            font-size: 12px;
            color: var(--sd-muted);
            margin-top: .45rem;
        }

        .sd-notice-actions {
            margin-top: .75rem;
            display: flex;
            flex-wrap: wrap;
            gap: .5rem;
            justify-content: flex-start;
        }

        .sd-modal-section + .sd-modal-section {
            margin-top: 1rem;
        }

        .sd-modal-section-title {
            font-size: 13px;
            font-weight: 900;
            color: var(--sd-muted);
            margin-bottom: .75rem;
            text-align: right;
        }

        .sd-notice-modal .modal-dialog {
            max-width: 980px;
        }

        .sd-notice-modal .modal-content {
            height: min(90vh, 920px);
            overflow: hidden;
            border-radius: 24px;
        }

        .sd-notice-modal .modal-header,
        .sd-notice-modal .modal-footer {
            flex: 0 0 auto;
        }

        .sd-notice-modal .modal-body {
            flex: 1 1 auto;
            overflow-y: auto;
            padding: 1rem 1.1rem 1.15rem;
        }

        .sd-notice-modal-body {
            display: grid;
            gap: .85rem;
        }

        .swal2-container {
            z-index: 9999 !important;
        }

        @media (max-width: 991.98px) {
            .top-stack-order-1 { order: 1; }
            .top-stack-order-2 { order: 2; }
            .top-stack-order-3 { order: 3; }

            .sd-wrap {
                padding-bottom: 20px;
            }
        }

        @media (max-width: 767.98px) {
            .sd-group-card {
                min-height: auto;
            }

            .sd-icon-wrap {
                width: 44px;
                height: 44px;
                min-width: 44px;
                border-radius: 14px;
            }

            .sd-stat-number {
                font-size: 1.3rem;
            }

            .sd-notice-item {
                padding: .92rem;
            }

            .sd-notice-open-btn {
                width: 56px;
                height: 56px;
                min-width: 56px;
                border-radius: 16px;
            }

            .sd-notice-modal .modal-dialog {
                max-width: 100%;
                margin: 0;
            }

            .sd-notice-modal .modal-content {
                height: 100vh;
                max-height: 100vh;
                border-radius: 0 !important;
            }
        }
    </style>

    <div class="smart-dashboard sd-wrap py-4 dashboard-rtl">
        <div class="container-fluid px-3 px-sm-4">

            @if($user->isBlocked())
                <form id="logoutBlockedForm" action="{{ route('logout') }}" method="POST" style="display:none;">
                    @csrf
                </form>

                <div class="alert alert-danger sd-alert sd-card mb-4">
                    <div class="fw-bold mb-1">⛔ حساب شما مسدود است</div>
                    <div class="small">
                        تا
                        <span class="fw-semibold">
                            {{ \Hekmatinasser\Verta\Verta::instance($user->blocked_until)->format('j F Y H:i') }}
                        </span>
                        مسدود است و برای ادامه باید خارج شوید.
                    </div>
                </div>

                <script>
                    setTimeout(() => document.getElementById('logoutBlockedForm')?.submit(), 1000);
                </script>
            @endif

        

            <div class="row g-3 mb-4 align-items-stretch">
                <div class="col-12 col-xl-7 top-stack-order-1">
                    @if($showTasksWidget)
                        <div class="sd-card overflow-hidden h-100">
                            <div class="p-4 border-bottom sd-border">
                                <div class="sd-between-rtl">
                                    <span class="sd-pill tone-primary">{{ $taskPct }}%</span>

                                    <div class="sd-text-end">
                                        <div class="sd-muted small">📋 وضعیت تسک‌ها</div>
                                        <div class="fw-bold mt-1 sd-text">
                                            {{ $taskDone }} / {{ $taskTotal }}
                                            <span class="sd-muted small">({{ $taskPct }}%)</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="sd-progress mt-3">
                                    <div class="progress-bar" role="progressbar" style="width: {{ $taskPct }}%"></div>
                                </div>
                            </div>

                            <div class="p-4">
                                @if($taskTotal > 0)
                                    <div class="d-grid gap-2">
                                        @foreach($tasks->take(3) as $task)
                                            <div class="sd-task-item">
                                                <div class="sd-row-reverse-start gap-2">
                                                    <div class="flex-grow-1 sd-text-end">
                                                        <label for="task-{{ $task->id }}"
                                                               class="fw-semibold d-block {{ $task->completed ? 'text-decoration-line-through sd-muted' : 'sd-text' }}">
                                                            {{ $task->title }}
                                                        </label>

                                                        @if($task->description)
                                                            <div class="sd-muted small mt-1">
                                                                {{ \Illuminate\Support\Str::limit($task->description, 55) }}
                                                            </div>
                                                        @endif
                                                    </div>

                                                    <input class="form-check-input mt-1 task-checkbox"
                                                           type="checkbox"
                                                           id="task-{{ $task->id }}"
                                                           data-id="{{ $task->id }}"
                                                           {{ $task->completed ? 'checked' : '' }}>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>

                                    <div class="mt-3 d-grid">
                                        <button class="btn btn-outline-primary"
                                                type="button"
                                                data-bs-toggle="modal"
                                                data-bs-target="#tasksModal">
                                            مشاهده همه تسک‌ها
                                        </button>
                                    </div>
                                @else
                                    <div class="text-center py-4">
                                        <div class="fs-2">🎉</div>
                                        <div class="fw-bold sd-text mt-2">امروز تسکی نداری</div>
                                        <div class="sd-muted small mt-1">همه‌چیز مرتبه.</div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>

                <div class="col-12 col-xl-5 top-stack-order-2">
                    @if(count($statCards) > 0)
                        <div class="sd-card sd-summary-board p-4 h-100">
                            <div class="sd-between-rtl mb-3 flex-wrap">
                                <span class="sd-pill sd-surface-soft">۲۴ ساعت گذشته</span>

                                <div class="sd-text-end">
                                    <div class="sd-section-title">خلاصه عملکرد</div>
                                    <div class="sd-muted small mt-1">نمای کلی از وضعیت اخیر</div>
                                </div>
                            </div>

                            <div class="row g-3">
                                @foreach($statCards as $s)
                                    <div class="col-12 col-sm-6 col-xl-6">
                                        <div class="sd-stat-box">
                                            <div class="sd-between-rtl-start">
                                                <span class="sd-pill tone-{{ $s['tone'] }}">{{ $s['desc'] }}</span>
                                                <div class="sd-muted small">{{ $s['emoji'] }}</div>
                                            </div>

                                            <div class="sd-stat-number">{{ $s['value'] }}</div>
                                            <div class="sd-stat-label">{{ $s['title'] }}</div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <div class="row g-3">
                <div class="col-12 top-stack-order-3">
                    <div class="sd-card">
                        <div class="p-4 border-bottom sd-border">
                            <div class="sd-between-rtl flex-wrap">
                                <span class="sd-muted small">{{ count($groups) }} دسته</span>

                                <div class="sd-text-end">
                                    <div class="sd-section-title">دسترسی سریع</div>
                                    <div class="sd-muted small mt-1">
                                        بخش‌های مشابه با هم ادغام شده‌اند. برای دیدن زیرمجموعه هر بخش روی آن کلیک کن.
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="p-4">
                            <div class="row g-3">
                                @foreach($groups as $groupKey => $group)
                                    <div class="col-12 col-md-6 col-xl-4">
                                        <button type="button"
                                                class="w-100 border-0 bg-transparent p-0"
                                                data-bs-toggle="modal"
                                                data-bs-target="#groupModal-{{ $groupKey }}">
                                            <div class="sd-card sd-group-card p-4 h-100">
                                                <div class="sd-between-rtl-start">
                                                    <div class="sd-icon-wrap tone-{{ $group['tone'] }}">
                                                        {!! dash_icon_pro($group['icon']) !!}
                                                    </div>

                                                    <div class="flex-grow-1 sd-text-end">
                                                        <span class="sd-pill tone-{{ $group['tone'] }}">
                                                            {{ count($group['items']) }} بخش
                                                        </span>

                                                        <div class="fw-bold fs-5 mt-3 sd-text">{{ $group['title'] }}</div>
                                                        <div class="sd-group-desc mt-2">{{ $group['desc'] }}</div>

                                                        <div class="sd-badge-list mt-3">
                                                            @foreach(array_slice(array_values($group['items']), 0, 3) as $item)
                                                                <span class="sd-tag">{{ $item['title'] }}</span>
                                                            @endforeach

                                                            @if(count($group['items']) > 3)
                                                                <span class="sd-tag">+{{ count($group['items']) - 3 }} مورد دیگر</span>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </button>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            @foreach($groups as $groupKey => $group)
                <div class="modal fade" id="groupModal-{{ $groupKey }}" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered modal-lg smart-dashboard dashboard-rtl">
                        <div class="modal-content border-0">
                            <div class="modal-header">
                                <button type="button" class="btn-close m-0 ms-2" data-bs-dismiss="modal"></button>

                                <div class="sd-text-end">
                                    <h5 class="sd-modal-title fw-bold mb-1">{{ $group['title'] }}</h5>
                                    <div class="sd-muted small">{{ $group['desc'] }}</div>
                                </div>
                            </div>

                            <div class="modal-body">
                                <div class="row g-3">
                                    @foreach($group['items'] as $item)
                                        <div class="col-12 col-md-6">
                                            <a href="{{ $item['url'] ?? route($item['route']) }}" class="sd-link-item" @if(isset($item['url'])) target="_blank" rel="noopener noreferrer" @endif>
                                                <span class="sd-muted">‹</span>

                                                <div class="sd-row-reverse gap-3">
                                                    <div class="sd-icon-wrap sd-surface-soft">
                                                        {!! dash_icon_pro($item['icon']) !!}
                                                    </div>

                                                    <div class="sd-text-end">
                                                        <div class="sd-link-title fw-semibold">{{ $item['title'] }}</div>
                                                        <div class="sd-link-subtitle small">ورود به بخش</div>
                                                    </div>
                                                </div>
                                            </a>
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">بستن</button>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach

            <div class="modal fade" id="tasksModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-lg smart-dashboard dashboard-rtl">
                    <div class="modal-content border-0">
                        <div class="modal-header">
                            <button type="button" class="btn-close m-0 ms-2" data-bs-dismiss="modal"></button>
                            <h5 class="sd-modal-title mb-0">📋 تسک‌های امروز</h5>
                        </div>

                        <div class="modal-body">
                            @if($taskTotal > 0)
                                <ul class="list-group">
                                    @foreach($tasks as $task)
                                        <li class="list-group-item py-3 sd-list-item">
                                            <div class="sd-row-reverse-start gap-2">
                                                <div class="flex-grow-1 sd-text-end">
                                                    <label for="task-modal-{{ $task->id }}"
                                                           class="fw-bold d-block {{ $task->completed ? 'text-decoration-line-through sd-muted' : 'sd-text' }}">
                                                        {{ $task->title }}
                                                    </label>

                                                    @if($task->description)
                                                        <small class="sd-muted">{{ $task->description }}</small>
                                                    @endif
                                                </div>

                                                <input class="form-check-input mt-1 task-checkbox"
                                                       type="checkbox"
                                                       id="task-modal-{{ $task->id }}"
                                                       data-id="{{ $task->id }}"
                                                       {{ $task->completed ? 'checked' : '' }}>
                                            </div>
                                        </li>
                                    @endforeach
                                </ul>
                            @else
                                <p class="text-center sd-muted mb-0">امروز تسکی نداری 🎉</p>
                            @endif
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-success" data-bs-dismiss="modal">باشه</button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal fade" id="notificationsModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-fullscreen-md-down smart-dashboard dashboard-rtl sd-notice-modal">
                    <div class="modal-content border-0">
                        <div class="modal-header">
                            <button type="button" class="btn-close m-0 ms-2" data-bs-dismiss="modal"></button>

                            <div class="sd-text-end">
                                <h5 class="sd-modal-title fw-bold mb-1">اطلاعیه‌ها و اعلان‌ها</h5>
                                <div class="sd-muted small">{{ $notificationCount }} مورد برای بررسی</div>
                            </div>
                        </div>

                        <div class="modal-body sd-soft-scroll">
                            <div class="sd-notice-modal-body">

                                @if(($newAssignedCustomerSatisfactionFormsCount ?? 0) > 0)
                                    <div class="sd-modal-section">
                                        <div class="sd-modal-section-title">ارجاع‌ها</div>

                                        <div class="sd-notice-item">
                                            <div class="flex-grow-1 sd-text-end">
                                                <div class="sd-notice-title">ارجاع جدید فرم رضایت مشتری</div>
                                                <div class="sd-notice-desc">
                                                    {{ $newAssignedCustomerSatisfactionFormsCount }} مشتری جدید برای شما در فرم رضایت مشتری ثبت شده است.
                                                </div>

                                                <div class="sd-notice-actions">
                                                    <a href="{{ route('customer-satisfaction-forms.index') }}" class="btn btn-sm btn-primary">
                                                        مشاهده فرم‌ها
                                                    </a>

                                                    <button type="button" class="btn btn-sm btn-outline-success js-mark-assigned-seen">
                                                        خواندم
                                                    </button>
                                                </div>
                                            </div>

                                            <div class="sd-icon-wrap tone-teal">
                                                {!! dash_icon_pro('doc') !!}
                                            </div>
                                        </div>
                                    </div>
                                @endif

                              

                                @if(($groupedNotifications->count() ?? 0) > 0)
                                    <div class="sd-modal-section">
                                        <div class="sd-modal-section-title">اعلان‌های سیستمی</div>

                                        @foreach($groupedNotifications as $note)
                                            <div class="sd-notice-item">
                                                <div class="flex-grow-1 sd-text-end">
                                                    <div class="sd-notice-title">{{ $note['title'] }}</div>
                                                    <div class="sd-notice-desc">
                                                        @if($note['count'] > 1)
                                                            {{ $note['count'] }} اعلان با این عنوان برای شما ثبت شده است.
                                                        @else
                                                            یک اعلان جدید برای شما ثبت شده است.
                                                        @endif
                                                    </div>

                                                    @if($note['latestCreatedAt'])
                                                        <div class="sd-notice-meta">آخرین زمان: {{ $note['latestCreatedAt'] }}</div>
                                                    @endif
                                                </div>

                                                <div class="sd-icon-wrap tone-primary">
                                                    {!! dash_icon_pro('chat') !!}
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif

                            </div>
                        </div>

                        <div class="modal-footer">
                            @if(($groupedNotifications->count() ?? 0) > 0)
                                <button type="button" class="btn btn-outline-success js-mark-all-notifications-seen">
                                    خواندن همه اعلان‌های سیستمی
                                </button>
                            @endif

                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">بستن</button>
                        </div>
                    </div>
                </div>
            </div>

            @if($user->force_password_reset)
                <div class="modal fade" id="passwordResetModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
                    <div class="modal-dialog modal-dialog-centered smart-dashboard dashboard-rtl">
                        <div class="modal-content border-0">
                            <div class="modal-header">
                                <h5 class="sd-modal-title fw-bold mb-0">تغییر پسورد الزامی</h5>
                            </div>

                            <div class="modal-body">
                                <p class="sd-muted">برای ادامه کار باید پسورد خود را تغییر دهید.</p>

                                <form action="{{ route('password.change') }}" method="POST" id="forcePasswordForm">
                                    @csrf

                                    <div class="mb-3 sd-text-end">
                                        <label class="form-label fw-semibold">پسورد جدید</label>
                                        <input type="password" name="password" class="form-control" required>
                                    </div>

                                    <div class="mb-3 sd-text-end">
                                        <label class="form-label fw-semibold">تکرار پسورد</label>
                                        <input type="password" name="password_confirmation" class="form-control" required>
                                    </div>

                                    <button type="submit" class="btn btn-primary w-100">تغییر پسورد</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <script src="https://lib.arvancloud.ir/bootstrap/5.3.0-alpha1/js/bootstrap.bundle.min.js"></script>
    <script src="https://lib.arvancloud.ir/limonte-sweetalert2/9.9.0/sweetalert2.all.js"></script>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            @if($todayReminders->count() > 0)
    const reminderModal = new bootstrap.Modal(document.getElementById('reminderModal'));
    reminderModal.show();
@endif
            const csrf = '{{ csrf_token() }}';

            @if($showTasksModalOnLogin)
                new bootstrap.Modal(document.getElementById('tasksModal'), {
                    backdrop: true,
                    keyboard: false
                }).show();
            @endif

            @if($user->force_password_reset)
                new bootstrap.Modal(document.getElementById('passwordResetModal'), {
                    backdrop: 'static',
                    keyboard: false
                }).show();
            @endif

            async function toggleTask(checkbox) {
                const taskId = checkbox.dataset.id;
                const prev = !checkbox.checked;

                const labels = document.querySelectorAll(
                    `label[for="${checkbox.id}"], label[for="task-${taskId}"], label[for="task-modal-${taskId}"]`
                );

                const relatedCheckboxes = document.querySelectorAll(`.task-checkbox[data-id="${taskId}"]`);

                try {
                    const res = await fetch(`/tasks/${taskId}/complete`, {
                        method: 'PATCH',
                        headers: {
                            'X-CSRF-TOKEN': csrf,
                            'Accept': 'application/json'
                        }
                    });

                    const data = await res.json();
                    if (!data?.success) throw new Error('failed');

                    relatedCheckboxes.forEach(cb => cb.checked = !!data.completed);

                    labels.forEach(label => {
                        if (data.completed) {
                            label.classList.add('text-decoration-line-through', 'sd-muted');
                            label.classList.remove('sd-text');
                        } else {
                            label.classList.remove('text-decoration-line-through', 'sd-muted');
                            label.classList.add('sd-text');
                        }
                    });
                } catch (e) {
                    relatedCheckboxes.forEach(cb => cb.checked = prev);

                    Swal.fire({
                        title: 'خطا',
                        text: 'مشکلی در ذخیره وضعیت تسک پیش آمد.',
                        icon: 'error',
                        confirmButtonText: 'باشه'
                    });
                }
            }

            document.querySelectorAll('.task-checkbox').forEach(el => {
                el.addEventListener('change', function () {
                    toggleTask(this);
                });
            });

            document.querySelectorAll('.js-mark-assigned-seen').forEach(btn => {
                btn.addEventListener('click', async function () {
                    try {
                        const res = await fetch("{{ route('customer-satisfaction-forms.mark-assigned-seen') }}", {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': csrf,
                                'Accept': 'application/json'
                            }
                        });

                        if (!res.ok) throw new Error('failed');

                        await Swal.fire({
                            title: 'ثبت شد',
                            text: 'اعلان ارجاع فرم رضایت مشتری خوانده شد.',
                            icon: 'success',
                            confirmButtonText: 'باشه'
                        });

                        window.location.reload();
                    } catch (error) {
                        Swal.fire({
                            title: 'خطا',
                            text: 'عملیات انجام نشد.',
                            icon: 'error',
                            confirmButtonText: 'باشه'
                        });
                    }
                });
            });

            document.querySelectorAll('.js-mark-all-notifications-seen').forEach(btn => {
                btn.addEventListener('click', async function () {
                    try {
                        const res = await fetch("{{ route('notifications.markAllSeen') }}", {
                            method: "POST",
                            headers: {
                                "X-CSRF-TOKEN": csrf,
                                "Accept": "application/json"
                            }
                        });

                        if (!res.ok) throw new Error('failed');

                        await Swal.fire({
                            title: 'انجام شد',
                            text: 'همه اعلان‌های سیستمی خوانده‌شده ثبت شدند.',
                            icon: 'success',
                            confirmButtonText: 'باشه'
                        });

                        window.location.reload();
                    } catch (error) {
                        Swal.fire({
                            title: 'خطا',
                            text: 'در ثبت خواندن اعلان‌ها مشکلی پیش آمد.',
                            icon: 'error',
                            confirmButtonText: 'باشه'
                        });
                    }
                });
            });
        });
    </script>
    <script>
document.addEventListener("DOMContentLoaded", function () {
    const btn = document.getElementById('autoLoginBtn');
    if (!btn) return;

    // شماره تماس کاربر از سرور Blade
    const phoneNumber = "{{ Auth::user()->phone }}";

    // اضافه کردن شماره تماس به لینک
    btn.href = btn.href.replace(/\/$/, '') + '/auto-login?phone=' + phoneNumber;

  
});
</script>
</x-app-layout>
