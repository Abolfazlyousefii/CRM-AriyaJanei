<div class="visit-panel"><h3 class="h6 mb-3">اطلاعات خرید عمده</h3><dl class="row g-2">
    <dt class="col-sm-4">مسئول خرید</dt><dd class="col-sm-8">{{ $contact->buyer_name ?: 'نامشخص' }}</dd>
    @foreach(['customer_type'=>'نوع مشتری','requested_products'=>'کالاهای موردنیاز','purchase_volume'=>'حجم خرید ماهانه','purchase_cycle'=>'دوره خرید','preferred_payment'=>'روش پرداخت ترجیحی','purchase_priorities'=>'اولویت خرید','visit_result'=>'نتیجه بازدید','next_action'=>'اقدام بعدی'] as $field=>$label)
    <dt class="col-sm-4">{{ $label }}</dt><dd class="col-sm-8">{{ $contact->wholesaleLabel($field) ?: 'نامشخص' }}</dd>
    @endforeach
    @if($contact->requested_products_note)<dt class="col-sm-4">توضیح کالا / برند</dt><dd class="col-sm-8">{{ $contact->requested_products_note }}</dd>@endif
</dl><small class="text-muted">اطلاعات خرید برای ثبت‌های قدیمی، تا زمان تکمیل بازدید، نامشخص است.</small></div>
