@canany(['create', 'viewAny'], \App\Models\ShopContact::class)
    <section id="field-visits-home" class="sd-card p-3 p-sm-4 mb-4" aria-labelledby="field-visits-home-title">
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
            <div>
                <h2 id="field-visits-home-title" class="h5 fw-bold sd-text mb-2"><i class="bi bi-geo-alt" aria-hidden="true"></i> نقطه‌زنی و بازدید فروشگاه‌ها</h2>
                <p class="sd-muted small mb-0">دسترسی مستقیم به ثبت بازدید، نقشه و گزارش فروشگاه‌ها</p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                @can('create', \App\Models\ShopContact::class)
                    <a id="home-field-visit-create" href="{{ route('field-visits.create') }}" class="btn btn-primary px-4 py-2"><i class="bi bi-geo-alt" aria-hidden="true"></i> ثبت نقطه‌زنی</a>
                @endcan
                @can('viewAny', \App\Models\ShopContact::class)
                    <a id="home-field-visit-report" href="{{ route('field-visits.index') }}" class="btn btn-outline-primary px-4 py-2"><i class="bi bi-bar-chart" aria-hidden="true"></i> گزارش و آرشیو نقطه‌زنی</a>
                @endcan
            </div>
        </div>
    </section>
@endcanany
