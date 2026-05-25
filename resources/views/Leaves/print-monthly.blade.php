<!doctype html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>گزارش مرخصی ماه {{ $month }}</title>
    <style>
        body { font-family: Tahoma, sans-serif; direction: rtl; margin: 20px; }
        h1 { margin-bottom: 4px; }
        .meta { color:#555; margin-bottom: 14px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border:1px solid #ddd; padding:8px; font-size: 12px; vertical-align: top; }
        th { background:#f7f7f7; }
        @media print { .no-print { display:none; } }
    </style>
</head>
<body>
    <h1>گزارش کامل مرخصی‌ها</h1>
    <div class="meta">ماه شمسی: {{ $month }} | تعداد: {{ $leaves->count() }}</div>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>کاربر</th>
                <th>جایگزین</th>
                <th>مدیر</th>
                <th>نوع مرخصی</th>
                <th>نوع ثبت</th>
                <th>از تاریخ</th>
                <th>تا تاریخ</th>
                <th>از ساعت</th>
                <th>تا ساعت</th>
                <th>وضعیت</th>
                <th>توضیحات</th>
                <th>تاریخ ثبت</th>
            </tr>
        </thead>
        <tbody>
            @forelse($leaves as $leave)
                <tr>
                    <td>{{ $leave->id }}</td>
                    <td>{{ $leave->user?->name ?? '-' }}</td>
                    <td>{{ $leave->substituteUser?->name ?? '-' }}</td>
                    <td>{{ $leave->manager?->name ?? '-' }}</td>
                    <td>{{ $leave->leave_type ?? '-' }}</td>
                    <td>{{ $leave->leave_unit ?? '-' }}</td>
                    <td>{{ $leave->start_date ? verta($leave->start_date)->format('Y/m/d') : '-' }}</td>
                    <td>{{ $leave->end_date ? verta($leave->end_date)->format('Y/m/d') : '-' }}</td>
                    <td>{{ $leave->start_time ? substr($leave->start_time,0,5) : '-' }}</td>
                    <td>{{ $leave->end_time ? substr($leave->end_time,0,5) : '-' }}</td>
                    <td>{{ $leave->status }}</td>
                    <td>{{ $leave->reason ?? '-' }}</td>
                    <td>{{ $leave->created_at ? verta($leave->created_at)->format('Y/m/d H:i') : '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="13" style="text-align:center;">برای این ماه مرخصی‌ای ثبت نشده است.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="no-print" style="margin-top:16px; text-align:center;">
        <button onclick="window.print()">چاپ</button>
    </div>

    <script>
        window.addEventListener('load', function(){ window.print(); });
    </script>
</body>
</html>
