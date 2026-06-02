<x-layouts.app>
    <x-slot name="header">
        <h2 class="fw-bold h4">مدیریت محصولات</h2>
    </x-slot>

    <div class="container mt-4" dir="rtl">
        @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
        @if(session('error'))   <div class="alert alert-danger">{{ session('error') }}</div>   @endif

        <style>
            .product-thumb {
                width: 72px;
                height: 72px;
                object-fit: cover;
                border-radius: .5rem;
                border: 1px solid #dee2e6;
                background: #f8f9fa;
            }
            .product-thumb-placeholder {
                width: 72px;
                height: 72px;
                border-radius: .5rem;
                border: 1px dashed #ced4da;
                background: #f8f9fa;
                color: #adb5bd;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                font-size: 1.5rem;
            }
        </style>

        <div class="row">
            {{-- سایدبار دسته‌ها --}}
            <aside class="col-12 col-lg-3 mb-3 mb-lg-0">
                <div class="card shadow-sm">
                    <div class="card-header bg-light fw-bold">دسته‌بندی‌ها</div>
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item {{ empty($category) ? 'active' : '' }}">
                            <a class="text-decoration-none {{ empty($category) ? 'text-white' : '' }}"
                               href="{{ route('products.index', array_filter(['q'=>$query])) }}">
                                همه
                            </a>
                        </li>
                        @foreach($categories as $cat)
                            @php
                                $val = !empty($cat['slug']) ? $cat['slug'] : ($cat['id'] ?? '');
                                $isActive = ($category ?? '') == $val;
                            @endphp
                            <li class="list-group-item {{ $isActive ? 'active' : '' }}">
                                <a class="text-decoration-none {{ $isActive ? 'text-white' : '' }}"
                                   href="{{ route('products.index', array_filter(['q'=>$query, 'category'=>$val])) }}">
                                    {{ $cat['name'] ?? 'بدون دسته' }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </aside>

            {{-- جدول محصولات --}}
            <section class="col-12 col-lg-9">
                <div class="card shadow-sm">
                    <div class="card-header bg-light fw-bold d-flex justify-content-between align-items-center gap-2 flex-wrap">
                        <div>
                            <span>لیست محصولات</span>
                            @if(!empty($category))
                                <small class="text-primary me-2">فیلتر دسته: {{ $category }}</small>
                            @endif
                        </div>
                        <a class="btn btn-sm btn-danger" target="_blank" href="{{ route('products.pdf', request()->only(['q', 'category', 'page'])) }}">
                            خروجی PDF
                        </a>
                    </div>

                    <div class="card-body p-0">
                        <table class="table table-bordered table-hover mb-0 text-center align-middle">
                            <thead class="table-secondary">
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
                                    <tr class="table-primary">
                                        <td colspan="7" class="text-start">
                                            <strong>{{ $product['title'] ?? '—' }}</strong>
                                            <small class="text-muted">({{ $product['slug'] ?? '' }})</small>
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
                                                <img class="product-thumb" src="{{ $product['__image_url'] }}" alt="{{ $product['title'] ?? 'تصویر محصول' }}" loading="lazy">
                                            @else
                                                <span class="product-thumb-placeholder" title="تصویر موجود نیست">🖼️</span>
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
                                        {{-- اگر تنوعی نبود، همان ردیف کلی کفایت می‌کند --}}
                                    @endforelse
                                @empty
                                    <tr><td colspan="7">هیچ محصولی موجود نیست.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- صفحه‌بندی --}}
                <div class="mt-3 d-flex justify-content-center">
                    <nav>
                        <ul class="pagination">
                            <li class="page-item {{ ($pagination['current_page'] ?? 1) <= 1 ? 'disabled' : '' }}">
                                <a class="page-link" href="{{ request()->fullUrlWithQuery(['page' => ($pagination['current_page'] ?? 1) - 1]) }}">قبلی</a>
                            </li>
                            @for($i = 1; $i <= ($pagination['last_page'] ?? 1); $i++)
                                <li class="page-item {{ ($pagination['current_page'] ?? 1) == $i ? 'active' : '' }}">
                                    <a class="page-link" href="{{ request()->fullUrlWithQuery(['page' => $i]) }}">{{ $i }}</a>
                                </li>
                            @endfor
                            <li class="page-item {{ ($pagination['current_page'] ?? 1) >= ($pagination['last_page'] ?? 1) ? 'disabled' : '' }}">
                                <a class="page-link" href="{{ request()->fullUrlWithQuery(['page' => ($pagination['current_page'] ?? 1) + 1]) }}">بعدی</a>
                            </li>
                        </ul>
                    </nav>
                </div>
            </section>
        </div>
    </div>
</x-layouts.app>
