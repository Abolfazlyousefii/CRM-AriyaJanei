@php
    $sectionTabs = [
        ['label' => 'مدیریت پرسنل', 'route' => 'admin.users.index', 'pattern' => 'admin.users.*'],
        ['label' => 'بازاریاب', 'route' => 'admin.marketers.index', 'pattern' => 'admin.marketers.*'],
        ['label' => 'فهرست کلاسیک پرسنل', 'route' => 'admin.personnel.index', 'pattern' => 'admin.personnel.*'],
    ];
@endphp

<nav class="mb-3" dir="rtl">
    <ul class="nav nav-tabs">
        @foreach($sectionTabs as $tab)
            @php($isActive = request()->routeIs($tab['pattern']))
            <li class="nav-item">
                <a href="{{ route($tab['route']) }}"
                   class="nav-link {{ $isActive ? 'active fw-semibold' : '' }}"
                   @if($isActive) aria-current="page" @endif>
                    {{ $tab['label'] }}
                </a>
            </li>
        @endforeach
    </ul>
</nav>
