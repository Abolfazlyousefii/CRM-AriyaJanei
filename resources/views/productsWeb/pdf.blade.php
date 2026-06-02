<!doctype html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>خروجی PDF محصولات</title>
    <style>
        @font-face {
            font-family: Vazirmatn;
            src: url("{{ asset('css/fonts/vazirmatn/Vazirmatn-Regular.woff2') }}") format('woff2');
            font-weight: 400;
        }
        @font-face {
            font-family: Vazirmatn;
            src: url("{{ asset('css/fonts/vazirmatn/Vazirmatn-Bold.woff2') }}") format('woff2');
            font-weight: 700;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            padding: 24px;
            background: #f3f4f6;
            color: #111827;
            font-family: Vazirmatn, Tahoma, sans-serif;
            font-size: 12px;
        }
        .sheet {
            max-width: 1120px;
            margin: 0 auto;
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 10px 30px rgba(15, 23, 42, .08);
        }
        .toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            margin-bottom: 16px;
        }
        .title h1 { margin: 0 0 8px; font-size: 20px; }
        .title p { margin: 0; color: #6b7280; }
        .btn {
            border: 0;
            border-radius: 8px;
            background: #dc2626;
            color: #fff;
            cursor: pointer;
            padding: 10px 16px;
            font-family: inherit;
            font-weight: 700;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            overflow: hidden;
        }
        th, td {
            border: 1px solid #d1d5db;
            padding: 8px;
            text-align: center;
            vertical-align: middle;
        }
        th { background: #e5e7eb; font-weight: 700; }
        .product-heading td {
            background: #dbeafe;
            text-align: right;
            font-weight: 700;
        }
        .product-image {
            width: 120px;
            height: 120px;
            object-fit: cover;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            background: #f9fafb;
        }
        .image-placeholder {
            width: 120px;
            height: 120px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 1px dashed #cbd5e1;
            border-radius: 8px;
            color: #94a3b8;
            background: #f8fafc;
        }
        .muted { color: #6b7280; }
        @page { size: A4 landscape; margin: 10mm; }
        @media print {
            body { padding: 0; background: #fff; }
            .sheet { max-width: none; border: 0; box-shadow: none; border-radius: 0; padding: 0; }
            .no-print { display: none !important; }
            tr { break-inside: avoid; page-break-inside: avoid; }
        }
    </style>
</head>
<body>
    <main class="sheet">
        <div class="toolbar">
            <div class="title">
                <h1>خروجی PDF لیست محصولات</h1>
                <p>
                    تاریخ تولید: {{ optional($generatedAt)->format('Y-m-d H:i') }}
                    @if(!empty($query)) | جستجو: {{ $query }} @endif
                    @if(!empty($category)) | دسته: {{ $category }} @endif
                    | صفحه: {{ $pagination['current_page'] ?? 1 }} از {{ $pagination['last_page'] ?? 1 }}
                    | تعداد انتخاب‌شده: {{ count($selected ?? []) }}
                </p>
            </div>
            <button type="button" class="btn no-print" onclick="window.print()">چاپ / ذخیره PDF</button>
        </div>

        <table>
            <thead>
                <tr>
                    <th>عکس</th>
                    <th>نام محصول</th>
                    <th>تنوع‌ها / ویژگی</th>
                    <th>قیمت پایه</th>
                    <th>تخفیف</th>
                    <th>قیمت نهایی</th>
                    <th>موجودی</th>
                </tr>
            </thead>
            <tbody>
                @forelse($products as $product)
                    <tr class="product-heading">
                        <td colspan="7">
                            {{ $product['title'] ?? '—' }}
                            <span class="muted">({{ $product['slug'] ?? '' }})</span>
                        </td>
                    </tr>
                    @php
                        $pBase  = data_get($product, '__pricing.base', 0);
                        $pFinal = data_get($product, '__pricing.final', $pBase);
                        $pDisc  = data_get($product, '__pricing.discount', max(0, $pBase - $pFinal));
                    @endphp
                    <tr>
                        <td>
                            @if(!empty($product['__image_url']))
                                <img class="product-image" src="{{ $product['__image_url'] }}" alt="{{ $product['title'] ?? 'تصویر محصول' }}">
                            @else
                                <span class="image-placeholder">بدون عکس</span>
                            @endif
                        </td>
                        <td>{{ $product['title'] ?? '—' }}</td>
                        <td>—</td>
                        <td>{{ number_format($pBase) }} تومان</td>
                        <td>{{ $pDisc > 0 ? number_format($pDisc).' تومان' : '—' }}</td>
                        <td>{{ number_format($pFinal) }} تومان</td>
                        <td>—</td>
                    </tr>

                    @forelse($product['varieties'] ?? [] as $variety)
                        @php
                            $vBase  = data_get($variety, '__pricing.base', 0);
                            $vFinal = data_get($variety, '__pricing.final', $vBase);
                            $vDisc  = data_get($variety, '__pricing.discount', max(0, $vBase - $vFinal));
                        @endphp
                        <tr>
                            <td></td>
                            <td>—</td>
                            <td>
                                @if(!empty($variety['name']))
                                    {{ $variety['name'] }}
                                @elseif(!empty($variety['attributes']))
                                    @foreach($variety['attributes'] as $attribute)
                                        {{ $attribute['label'] ?? $attribute['name'] ?? '—' }}:
                                        {{ data_get($attribute, 'pivot.value') ?? ($attribute['value'] ?? '—') }}
                                        @if(!$loop->last) | @endif
                                    @endforeach
                                @else
                                    —
                                @endif
                            </td>
                            <td>{{ number_format($vBase) }} تومان</td>
                            <td>{{ $vDisc > 0 ? number_format($vDisc).' تومان' : '—' }}</td>
                            <td>{{ number_format($vFinal) }} تومان</td>
                            <td>{{ $variety['quantity'] ?? 0 }}</td>
                        </tr>
                    @empty
                    @endforelse
                @empty
                    <tr><td colspan="7">هیچ محصولی برای پرینت انتخاب نشده است.</td></tr>
                @endforelse
            </tbody>
        </table>
    </main>
</body>
</html>
