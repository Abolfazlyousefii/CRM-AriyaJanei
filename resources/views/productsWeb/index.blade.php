<x-layouts.app>
    <x-slot name="header">
        <h2 class="fw-bold h4">مدیریت محصولات</h2>
    </x-slot>

    <div class="container mt-4" dir="rtl">
        @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
        @if(session('error'))   <div class="alert alert-danger">{{ session('error') }}</div>   @endif

        <style>
            .product-thumb {
                width: 128px;
                height: 128px;
                object-fit: cover;
                border-radius: .75rem;
                border: 1px solid #dee2e6;
                background: #f8f9fa;
            }
            .product-thumb-placeholder {
                width: 128px;
                height: 128px;
                border-radius: .75rem;
                border: 1px dashed #ced4da;
                background: #f8f9fa;
                color: #adb5bd;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                font-size: 2rem;
            }
            .product-select-cell { width: 52px; }
            .product-image-cell { width: 150px; }
            .product-price-input { min-width: 110px; }
            .product-quantity-input { min-width: 90px; }
        </style>

        <div class="row">
            {{-- سایدبار دسته‌ها --}}
            <aside class="col-12 col-lg-3 mb-3 mb-lg-0">
                <div class="card shadow-sm mb-3">
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

            <section class="col-12 col-lg-9">
                <div class="card shadow-sm mb-3">
                    <div class="card-header bg-light fw-bold d-flex justify-content-between align-items-center gap-2 flex-wrap">
                        <span>افزودن محصول جدید به لیست فعلی</span>
                        <small class="text-muted">اگر محصول در دیتای سایت نبود، همین‌جا اضافه کنید.</small>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('products.custom.store') }}" enctype="multipart/form-data" class="row g-3 align-items-end">
                            @csrf
                            <input type="hidden" name="redirect_q" value="{{ $query }}">
                            <input type="hidden" name="redirect_category" value="{{ $category }}">

                            <div class="col-12 col-md-5">
                                <label class="form-label">نام محصول <span class="text-danger">*</span></label>
                                <input type="text" name="title" class="form-control" value="{{ old('title') }}" required>
                            </div>

                            <div class="col-12 col-md-4">
                                <label class="form-label">دسته‌بندی</label>
                                <select name="category_value" class="form-select">
                                    <option value="">{{ empty($category) ? 'بدون دسته / نمایش در همه' : 'دسته انتخاب‌شده فعلی' }}</option>
                                    @foreach($categories as $cat)
                                        @php
                                            $val = !empty($cat['slug']) ? $cat['slug'] : ($cat['id'] ?? '');
                                            $selected = old('category_value', $category) == $val;
                                        @endphp
                                        <option value="{{ $val }}" data-name="{{ $cat['name'] ?? 'بدون دسته' }}" {{ $selected ? 'selected' : '' }}>
                                            {{ $cat['name'] ?? 'بدون دسته' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-12 col-md-3">
                                <label class="form-label">عکس محصول</label>
                                <input type="file" name="image" class="form-control" accept="image/*">
                            </div>

                            <div class="col-6 col-md-3">
                                <label class="form-label">قیمت پایه</label>
                                <input type="number" name="base_price" class="form-control" value="{{ old('base_price', 0) }}" min="0">
                            </div>

                            <div class="col-6 col-md-3">
                                <label class="form-label">تخفیف</label>
                                <input type="number" name="discount" class="form-control" value="{{ old('discount', 0) }}" min="0">
                            </div>

                            <div class="col-6 col-md-3">
                                <label class="form-label">قیمت نهایی</label>
                                <input type="number" name="final_price" class="form-control" value="{{ old('final_price', 0) }}" min="0">
                            </div>

                            <div class="col-6 col-md-2">
                                <label class="form-label">موجودی</label>
                                <input type="number" name="quantity" class="form-control" value="{{ old('quantity', 0) }}" min="0">
                            </div>

                            <div class="col-12 col-md-1 d-grid">
                                <button type="submit" class="btn btn-primary">افزودن</button>
                            </div>
                        </form>
                    </div>
                </div>

                {{-- جدول محصولات --}}
                <form method="GET" action="{{ route('products.pdf') }}" target="_blank" id="productsPrintForm">
                    <input type="hidden" name="q" value="{{ $query }}">
                    <input type="hidden" name="category" value="{{ $category }}">
                    <input type="hidden" name="page" value="{{ $pagination['current_page'] ?? 1 }}">
                </form>

                <div class="card shadow-sm">
                        <div class="card-header bg-light fw-bold d-flex justify-content-between align-items-center gap-2 flex-wrap">
                            <div>
                                <span>لیست محصولات</span>
                                @if(!empty($category))
                                    <small class="text-primary me-2">فیلتر دسته: {{ $category }}</small>
                                @endif
                            </div>
                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                <div class="form-check m-0">
                                    <input class="form-check-input" type="checkbox" id="selectAllProducts">
                                    <label class="form-check-label small" for="selectAllProducts">انتخاب همه</label>
                                </div>
                                <button class="btn btn-sm btn-danger" type="submit" form="productsPrintForm">
                                    پرینت / PDF موارد انتخاب‌شده
                                </button>
                            </div>
                        </div>

                        <div class="card-body p-0">
                            <table class="table table-bordered table-hover mb-0 text-center align-middle">
                                <thead class="table-secondary">
                                    <tr>
                                        <th class="product-select-cell">انتخاب</th>
                                        <th class="product-image-cell">عکس</th>
                                        <th>نام محصول</th>
                                        <th>تنوع‌ها / ویژگی</th>
                                        <th>قیمت پایه</th>
                                        <th>تخفیف</th>
                                        <th>قیمت نهایی</th>
                                        <th>موجودی</th>
                                        <th>ذخیره</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($products as $product)
                                        <tr class="table-primary">
                                            <td colspan="9" class="text-start">
                                                <strong>{{ $product['title'] ?? '—' }}</strong>
                                                <small class="text-muted">({{ $product['slug'] ?? '' }})</small>
                                                @if(!empty($product['__is_custom']))
                                                    <span class="badge bg-info text-dark ms-2">افزوده‌شده</span>
                                                @endif
                                                @if(!empty($product['__has_price_override']))
                                                    <span class="badge bg-warning text-dark ms-2">قیمت/موجودی ویرایش‌شده</span>
                                                @endif
                                            </td>
                                        </tr>

                                        @php
                                            $pBase  = data_get($product, '__pricing.base', 0);
                                            $pFinal = data_get($product, '__pricing.final', $pBase);
                                            $pDisc  = data_get($product, '__pricing.discount', max(0, $pBase - $pFinal));
                                            $printKey = $product['__print_key'] ?? ($product['slug'] ?? '');
                                            $editFormId = 'product-pricing-' . $loop->iteration;
                                        @endphp
                                        <tr>
                                            <td>
                                                <input class="form-check-input product-print-checkbox" type="checkbox" name="selected[]" value="{{ $printKey }}" form="productsPrintForm">
                                            </td>
                                            <td>
                                                @if(!empty($product['__image_url']))
                                                    <img class="product-thumb" src="{{ $product['__image_url'] }}" alt="{{ $product['title'] ?? 'تصویر محصول' }}" loading="lazy">
                                                @else
                                                    <span class="product-thumb-placeholder" title="تصویر موجود نیست">🖼️</span>
                                                @endif
                                            </td>
                                            <td>{{ $product['title'] ?? '—' }}</td>
                                            <td>—</td>
                                            <td>
                                                <input class="form-control form-control-sm text-center product-price-input" type="number" name="base_price" value="{{ $pBase }}" min="0" form="{{ $editFormId }}">
                                            </td>
                                            <td>
                                                <input class="form-control form-control-sm text-center product-price-input" type="number" name="discount" value="{{ $pDisc }}" min="0" form="{{ $editFormId }}">
                                            </td>
                                            <td>
                                                <input class="form-control form-control-sm text-center product-price-input" type="number" name="final_price" value="{{ $pFinal }}" min="0" form="{{ $editFormId }}">
                                            </td>
                                            <td>
                                                <input class="form-control form-control-sm text-center product-quantity-input" type="number" name="quantity" value="{{ $product['quantity'] ?? 0 }}" min="0" form="{{ $editFormId }}">
                                            </td>
                                            <td>
                                                <form id="{{ $editFormId }}" method="POST" action="{{ route('products.pricing.update') }}">
                                                    @csrf
                                                    <input type="hidden" name="product_key" value="{{ $printKey }}">
                                                    <input type="hidden" name="redirect_q" value="{{ $query }}">
                                                    <input type="hidden" name="redirect_category" value="{{ $category }}">
                                                    <input type="hidden" name="redirect_page" value="{{ $pagination['current_page'] ?? 1 }}">
                                                    <button class="btn btn-sm btn-success" type="submit">ذخیره</button>
                                                </form>
                                            </td>
                                        </tr>

                                        @forelse($product['varieties'] ?? [] as $variety)
                                            @php
                                                $vBase  = data_get($variety, '__pricing.base', 0);
                                                $vFinal = data_get($variety, '__pricing.final', $vBase);
                                                $vDisc  = data_get($variety, '__pricing.discount', max(0, $vBase - $vFinal));
                                            @endphp
                                            <tr>
                                                <td></td>
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
                                                <td></td>
                                            </tr>
                                        @empty
                                            {{-- اگر تنوعی نبود، همان ردیف کلی کفایت می‌کند --}}
                                        @endforelse
                                    @empty
                                        <tr><td colspan="9">هیچ محصولی موجود نیست. از فرم بالا می‌توانید محصول اضافه کنید.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
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

    <script>
        document.getElementById('selectAllProducts')?.addEventListener('change', function () {
            document.querySelectorAll('.product-print-checkbox').forEach((checkbox) => {
                checkbox.checked = this.checked;
            });
        });
    </script>
</x-layouts.app>
