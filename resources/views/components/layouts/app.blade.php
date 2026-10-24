<!DOCTYPE html>
<html lang="fa" dir="rtl" data-bs-theme="light">

<link href="https://lib.arvancloud.ir/bootstrap/5.3.0-alpha1/css/bootstrap.rtl.min.css" rel="stylesheet">
    <script src="https://lib.arvancloud.ir/bootstrap/5.3.0-alpha1/js/bootstrap.bundle.min.js"></script>

<script>
document.addEventListener("DOMContentLoaded", function() {
    // اگر لودر وجود دارد
    const loader = document.getElementById("global-loader");
    if(loader) {
        // بعد از کوتاه‌ترین زمان، لودر را مخفی کن
        setTimeout(() => {
            loader.style.opacity = "0";           // نرمال محو شود
            loader.style.pointerEvents = "none";  // کلیک‌پذیری حذف شود
            loader.style.transition = "opacity 0.3s ease";

            // بعد از انیمیشن، display:none کن
            setTimeout(() => {
                loader.style.setProperty("display", "none", "important");
                loader.setAttribute("aria-hidden", "true");
            }, 300); // 300ms مطابق transition
        }, 100); // 100ms تا مطمئن شود DOM آماده است
    }
});

document.addEventListener("DOMContentLoaded", function() {
    // اگر لودر وجود دارد
    const loader = document.getElementById("loader");
    if(loader) {
        // بعد از کوتاه‌ترین زمان، لودر را مخفی کن
        setTimeout(() => {
            loader.style.opacity = "0";           // نرمال محو شود
            loader.style.pointerEvents = "none";  // کلیک‌پذیری حذف شود
            loader.style.transition = "opacity 0.3s ease";

            // بعد از انیمیشن، display:none کن
            setTimeout(() => {
                loader.style.setProperty("display", "none", "important");
                loader.setAttribute("aria-hidden", "true");
            }, 300); // 300ms مطابق transition
        }, 100); // 100ms تا مطمئن شود DOM آماده است
    }
});




    </script>


<head><meta charset="utf-8">
<script>
    (function () {
        const storedTheme = localStorage.getItem('theme');
        const systemTheme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
        const theme = storedTheme || systemTheme;

        document.documentElement.setAttribute('data-bs-theme', theme);
    })();
</script>

    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>آریا جانبی CRM</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <!-- Fonts -->
{{--    <link rel="preconnect" href="https://fonts.bunny.net">--}}
{{--    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />--}}
    <link rel="stylesheet" href="{{ asset('css/instrument-sans.css') }}">
    <link rel="stylesheet" href="{{ asset('css/Vazirmatn.css') }}">
    <!-- Scripts -->
{{--    <script src="//unpkg.com/alpinejs" defer></script>--}}

    @vite(['resources/css/app.css', 'resources/js/app.js'])

<style>
    :root {
        --surface: #ffffff;
        --border: rgba(15,23,42,.08);
        --text: #0f172a;
        --muted: #64748b;
        --hover-bg: rgba(15,23,42,.06);
        --input-bg: #ffffff;
        --input-border: rgba(15,23,42,.16);
        --table-striped: rgba(15,23,42,.03);
        --dash-bg: #f6f7fb;
    }

    [data-bs-theme="dark"] {
        --surface: rgba(255,255,255,.04);
        --border: rgba(148,163,184,.18);
        --text: #e2e8f0;
        --muted: #94a3b8;
        --hover-bg: rgba(148,163,184,.10);
        --input-bg: rgba(15,23,42,.45);
        --input-border: rgba(148,163,184,.28);
        --table-striped: rgba(148,163,184,.08);
        --dash-bg: #0b1220;
    }

    html, body, .min-h-screen, #global-loader {
        background-color: var(--dash-bg) !important;
        color: var(--text);
    }

    #global-loader { transition: opacity .3s ease; }

    .dash-wrap {
        background: linear-gradient(180deg, var(--dash-bg) 0%, var(--surface) 50%, var(--dash-bg) 100%);
    }

    .card-soft {
        border: 1px solid var(--border) !important;
        border-radius: 16px;
        background: var(--surface) !important;
        transition: .2s ease;
    }

    .card-soft:hover {
        box-shadow: 0 12px 30px rgba(0,0,0,.12);
        transform: translateY(-1px);
    }

    [data-bs-theme="dark"] .card-soft:hover {
        box-shadow: 0 12px 30px rgba(0,0,0,.35);
    }

    .bg-white, .bg-light, .bg-gray-50, .bg-gray-100, .bg-gray-200, .card, .modal-content, .offcanvas {
        background-color: var(--surface) !important;
        color: var(--text) !important;
    }

    .text-dark, .link-dark, .text-muted, .text-gray-900, .text-gray-800, .text-gray-700, .text-gray-600, .text-gray-500 {
        color: var(--text) !important;
    }

    [data-bs-theme="dark"] .text-blue-600 { color: #93c5fd !important; }
    [data-bs-theme="dark"] .text-green-600 { color: #86efac !important; }
    [data-bs-theme="dark"] .text-purple-600 { color: #c4b5fd !important; }

    .border, .border-top, .border-bottom, .border-start, .border-end, .table, .table > :not(caption) > * > * {
        border-color: var(--border) !important;
        color: var(--text);
    }

    .table-light,
    .table thead,
    .table-striped > tbody > tr:nth-of-type(odd) > * {
        --bs-table-bg: transparent;
        --bs-table-striped-bg: var(--table-striped);
        background-color: var(--hover-bg) !important;
        color: var(--text) !important;
    }

    .hover\:bg-gray-50:hover, .hover\:bg-gray-100:hover, tr:hover {
        background-color: var(--hover-bg) !important;
    }

    .form-control, .form-select, .input-group-text, textarea, input, select {
        background-color: var(--input-bg) !important;
        color: var(--text) !important;
        border-color: var(--input-border) !important;
    }

    .form-control::placeholder, textarea::placeholder, input::placeholder { color: var(--muted) !important; }

    [data-bs-theme="dark"] .btn-close { filter: invert(1) grayscale(100%) brightness(180%); }
</style>

</head>
<body class="font-sans antialiased">
<!-- Loader -->
<div id="global-loader" class="position-fixed top-0 start-0 w-100 h-100 d-flex justify-content-center align-items-center" style="z-index: 1050;">
    <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
        <span class="visually-hidden">در حال بارگذاری...</span>
    </div>
</div>

<div class="min-h-screen bg-gray-100">
    @include('layouts.navigation')

    <!-- Page Heading -->
    @isset($header)
        <header class="bg-white shadow">
            <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                {{ $header }}
            </div>
        </header>
    @endisset

    <!-- Page Content -->
    <main>
        {{ $slot }}
    </main>
</div>
</body>
<script>

document.addEventListener('DOMContentLoaded', function() {
    const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    document.addEventListener('click', function(e){
        const modal = e.target.closest('.modal');

        // افزودن یادداشت
        if(e.target.classList.contains('add-note') && modal){
            const customerId = modal.dataset.customer;
            const textarea = modal.querySelector('textarea');
            const content = textarea.value.trim();
            if(!content) return alert('محتوا نمی‌تواند خالی باشد');

            fetch(`/admin/customers/${customerId}/notes`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': token,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({content})
            })
            .then(res => res.json())
            .then(data => {
                if(data.success){
                    const ul = modal.querySelector('ul.list-group');
                    const li = document.createElement('li');
                    li.className = 'list-group-item d-flex justify-content-between';
                    li.dataset.id = data.note.id;
                    li.innerHTML = `
                        <span class="note-content">${data.note.content}</span>
                        <div>
                            <small class="text-muted">${data.note.creator} - ${data.note.created_at}</small>
                            <button class="btn btn-sm btn-warning edit-note">ویرایش</button>
                            <button class="btn btn-sm btn-danger delete-note">حذف</button>
                        </div>
                    `;
                    ul.prepend(li);
                    textarea.value = '';
                } else {
                    alert('ثبت یادداشت موفقیت‌آمیز نبود.');
                }
            }).catch(err => console.error(err));
        }

        // حذف یادداشت
        if(e.target.classList.contains('delete-note')){
            const li = e.target.closest('li[data-id]');
            if(!li) return;
            const noteId = li.dataset.id;
            if(!confirm('آیا مطمئن هستید؟')) return;

            fetch(`/admin/customers/notes/${noteId}`, {
                method: 'DELETE',
                headers: {'X-CSRF-TOKEN': token}
            }).then(res => res.json()).then(data => {
                if(data.success) li.remove();
                else alert('حذف یادداشت موفقیت‌آمیز نبود.');
            });
        }

        // ویرایش یادداشت
        if(e.target.classList.contains('edit-note')){
            const li = e.target.closest('li[data-id]');
            if(!li) return;
            const noteId = li.dataset.id;
            const oldContent = li.querySelector('.note-content').textContent;
            const newContent = prompt('ویرایش یادداشت:', oldContent);
            if(newContent === null) return;

            fetch(`/admin/customers/notes/${noteId}`, {
                method: 'PATCH',
                headers: {
                    'X-CSRF-TOKEN': token,
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({content: newContent})
            }).then(res => res.json()).then(data => {
                if(data.success) li.querySelector('.note-content').textContent = data.content;
                else alert('ویرایش یادداشت موفقیت‌آمیز نبود.');
            });
        }
    });
});

document.addEventListener('DOMContentLoaded', function () {
    const switchers = document.querySelectorAll('[data-theme-switcher]');
    const currentTheme = document.documentElement.getAttribute('data-bs-theme') || 'light';

    const syncSwitchers = (theme) => {
        switchers.forEach((switcher) => {
            switcher.value = theme;
        });
    };

    syncSwitchers(currentTheme);

    switchers.forEach((switcher) => {
        switcher.addEventListener('change', function (event) {
            const selectedTheme = event.target.value;

            document.documentElement.setAttribute('data-bs-theme', selectedTheme);
            localStorage.setItem('theme', selectedTheme);
            syncSwitchers(selectedTheme);
        });
    });
});

    </script>
</html>
