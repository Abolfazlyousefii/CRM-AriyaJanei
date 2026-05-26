<!doctype html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <title>گزارش مرخصی ماه {{ $month }}</title>

    <style>
        @page {
            size: A4 landscape;
            margin: 8mm;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: Tahoma, Arial, sans-serif;
            direction: rtl;
            margin: 0;
            color: #111;
            background: #fff;
            font-size: 11px;
        }

        .report-box {
            width: 100%;
            border: 2px solid #222;
            border-radius: 6px;
            padding: 10px;
        }

        .header {
            text-align: center;
            border-bottom: 2px solid #222;
            padding-bottom: 8px;
            margin-bottom: 10px;
        }

        .header h1 {
            margin: 0 0 6px 0;
            font-size: 24px;
            font-weight: bold;
        }

        .meta {
            font-size: 13px;
            font-weight: bold;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        th, td {
            border: 1px solid #999;
            padding: 4px 3px;
            text-align: center;
            vertical-align: middle;
            word-wrap: break-word;
            overflow-wrap: anywhere;
            line-height: 1.6;
        }

        th {
            background: #e9e9e9;
            font-weight: bold;
            font-size: 10.5px;
        }

        td {
            font-size: 10px;
        }

        .col-id { width: 4%; }
        .col-user { width: 9%; }
        .col-sub { width: 9%; }
        .col-manager { width: 8%; }
        .col-type { width: 7%; }
        .col-unit { width: 6%; }
        .col-date { width: 8%; }
        .col-time { width: 6%; }
        .col-status { width: 7%; }
        .col-reason { width: 14%; }
        .col-created { width: 10%; }

        .status-approved {
            color: #087f23;
            font-weight: bold;
        }

        .status-rejected {
            color: #b00020;
            font-weight: bold;
        }

        .status-pending {
            color: #b26a00;
            font-weight: bold;
        }

        .footer {
            margin-top: 8px;
            text-align: left;
            font-size: 10px;
            color: #555;
        }

        .no-print {
            margin-top: 16px;
            text-align: center;
        }

        .no-print button {
            padding: 8px 22px;
            border: 1px solid #333;
            background: #f5f5f5;
            cursor: pointer;
            border-radius: 4px;
        }

        @media print {
            .no-print {
                display: none;
            }

            body {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>
</head>

<body>
@php
  function faLeaveStatus($status) {
        $status = trim((string) $status);

        return match ($status) {
            'final_approved'    => 'تأیید نهایی',
            'internal_approved' => 'تأیید مدیر داخلی',
            'manager_approved' => 'تأیید مدیر واحد',
            'final_rejected'    => 'رد نهایی',
            'internal_rejected' => 'رد مدیر داخلی',
              'manager_rejected' => 'رد مدیر واحد',
            'pending'           => 'در انتظار بررسی',
            'cancelled'         => 'لغو شده',
            default             => $status ?: '-',
        };
    }
    function statusClass($status) {
        return match($status) {
            'approved' => 'status-approved',
            'rejected' => 'status-rejected',
            'pending' => 'status-pending',
            default => '',
        };
    }

    function onlyTime($value) {
        if (!$value) return '-';

        try {
            return \Carbon\Carbon::parse($value)->format('H:i');
        } catch (\Exception $e) {
            return substr($value, -8, 5);
        }
    }
@endphp

<div class="report-box">
    <div class="header">
        <h1>گزارش کامل مرخصی‌ها</h1>
        <div class="meta">
            ماه شمسی: {{ $month }} |
            تعداد: {{ $leaves->count() }}
        </div>
    </div>

    <table>
        <thead>
        <tr>
            <th class="col-id">#</th>
            <th class="col-user">کاربر</th>
            <th class="col-sub">جایگزین</th>
            <th class="col-manager">مدیر</th>
            <th class="col-type">نوع مرخصی</th>
            <th class="col-unit">نوع ثبت</th>
            <th class="col-date">از تاریخ</th>
            <th class="col-date">تا تاریخ</th>
            <th class="col-time">از ساعت</th>
            <th class="col-time">تا ساعت</th>
            <th class="col-status">وضعیت</th>
            <th class="col-reason">توضیحات</th>
            <th class="col-created">تاریخ ثبت</th>
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
                <td>{{ onlyTime($leave->start_time) }}</td>
                <td>{{ onlyTime($leave->end_time) }}</td>
                <td class="{{ statusClass($leave->status) }}">
                    {{ faLeaveStatus($leave->status) }}
                </td>
                <td>{{ $leave->reason ?? '-' }}</td>
                <td>{{ $leave->created_at ? verta($leave->created_at)->format('Y/m/d H:i') : '-' }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="13">برای این ماه مرخصی‌ای ثبت نشده است.</td>
            </tr>
        @endforelse
        </tbody>
    </table>

    <div class="footer">
        تاریخ چاپ: {{ verta(now())->format('Y/m/d H:i') }}
    </div>
</div>

<div class="no-print">
    <button onclick="window.print()">چاپ گزارش</button>
</div>

<script>
    window.addEventListener('load', function () {
        window.print();
    });
</script>
</body>
</html>