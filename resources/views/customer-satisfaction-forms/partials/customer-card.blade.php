@php
    $prefix = "customers[{$index}]";

    $displayIndex = $displayIndex
        ?? (is_numeric($index)
            ? ((int) $index + 1)
            : '__INDEX_DISPLAY__');
@endphp
<section class="cs-card customer-card" data-index="{{ $index }}">
 <header class="d-flex justify-content-between align-items-center mb-4"><div><small>مشتری شماره <span class="customer-number">{{ $displayIndex }}</span></small><h3 class="h5 mb-0 customer-title">اطلاعات مشتری</h3></div><button type="button" class="btn btn-sm btn-outline-danger remove-customer {{ $index ? '' : 'd-none' }}">حذف</button></header>
 <div class="row g-3">
  <div class="col-md-6"><label class="form-label">نام و نام خانوادگی <b>*</b></label><input class="form-control" name="{{ $prefix }}[customer_full_name]" value="{{ $customer['customer_full_name'] ?? '' }}" required></div>
  <div class="col-md-6"><label class="form-label">شماره تماس</label><input class="form-control" dir="ltr" name="{{ $prefix }}[customer_phone]" maxlength="20" value="{{ $customer['customer_phone'] ?? '' }}"></div>
  <div class="col-md-6"><label class="form-label">تاریخ تماس / پیگیری (شمسی)</label><div class="cs-date-field"><input type="text" class="form-control cs-jalali-date" data-jdp name="{{ $prefix }}[submitted_at_fa]" value="{{ $customer['submitted_at_fa'] ?? '' }}" readonly inputmode="none" autocomplete="off" placeholder="انتخاب تاریخ شمسی"><button type="button" class="cs-date-trigger" aria-label="انتخاب تاریخ"><i class="bi bi-calendar3" aria-hidden="true"></i></button></div>@error("customers.$index.submitted_at_fa")<div class="invalid-feedback d-block">{{ $message }}</div>@enderror</div>
  <div class="col-md-6"><label class="form-label">ارجاع به</label><select class="form-select" name="{{ $prefix }}[assigned_to_user_id]"><option value="">بدون ارجاع</option>@foreach($reviewUsers as $u)<option value="{{ $u->id }}" @selected((string)($customer['assigned_to_user_id'] ?? '') === (string)$u->id)>{{ $u->name }}</option>@endforeach</select></div>
 </div>
 <div class="question-block mt-4"><label class="form-label fw-bold">آیا این مشتری خرید کرده است؟ *</label><div class="choice-grid status-choices">@foreach(\App\Models\CustomerSatisfactionForm::PURCHASE_STATUSES as $value=>$label)<label class="choice"><input type="radio" name="{{ $prefix }}[purchase_status]" value="{{ $value }}" @checked(($customer['purchase_status'] ?? '') === $value) required><span>{{ $label }}</span></label>@endforeach</div></div>
 <div class="buyer-section mt-4">
  <div class="row g-3"><div class="col-12"><label class="form-label">تاریخ ارسال بار (اختیاری)</label><div class="cs-date-field"><input type="text" class="form-control cs-jalali-date" data-jdp name="{{ $prefix }}[shipment_sent_at_fa]" value="{{ $customer['shipment_sent_at_fa'] ?? '' }}" readonly inputmode="none" autocomplete="off" placeholder="انتخاب تاریخ شمسی"><button type="button" class="cs-date-trigger" aria-label="انتخاب تاریخ"><i class="bi bi-calendar3" aria-hidden="true"></i></button></div>@error("customers.$index.shipment_sent_at_fa")<div class="invalid-feedback d-block">{{ $message }}</div>@enderror</div>
  @foreach(['sales_response_score'=>'از لحظه ارتباط با کارشناس فروش و ثبت پیش‌فاکتور، به کیفیت پاسخ‌گویی و ارتباط با شما چه امتیازی می‌دهید؟','shipping_time_score'=>'به زمان ارسال کالا چه امتیازی می‌دهید؟','packaging_quality_score'=>'به کیفیت بسته‌بندی محصول چه امتیازی می‌دهید؟'] as $field=>$question)
   <div class="col-12 question-block"><label class="form-label">{{ $question }}</label><div class="score-row">@for($score=1;$score<=5;$score++)<label><input type="radio" name="{{ $prefix }}[{{ $field }}]" value="{{ $score }}" @checked((string)($customer[$field] ?? '') === (string)$score)><span>{{ $score }}</span></label>@endfor</div></div>
  @endforeach
  <div class="col-12 question-block"><label class="form-label">کدام ویژگی مثبت در پاسخ‌گویی و پشتیبانی برای شما قابل توجه بوده است؟</label><div class="choice-grid">@foreach(\App\Models\CustomerSatisfactionForm::SUPPORT_FEATURES as $value=>$label)<label class="choice"><input type="checkbox" name="{{ $prefix }}[support_positive_features][]" value="{{ $value }}" @checked(in_array($value, $customer['support_positive_features'] ?? []))><span>{{ $label }}</span></label>@endforeach</div></div>
  @foreach(['warranty_explained'=>'آیا شرایط گارانتی به‌وضوح برای شما توضیح داده شده است؟','warranty_meets_needs'=>'آیا شرایط گارانتی نیازهای پشتیبانی محصول شما را برآورده می‌کند؟','product_value_satisfied'=>'آیا کیفیت محصول با توجه به قیمت، رضایت‌بخش بوده است؟','would_recommend'=>'آیا مجموعه ما را به دوستان و همکاران خود معرفی می‌کنید؟','would_choose_again'=>'آیا برای خرید یا همکاری مجدد، مجموعه ما را انتخاب می‌کنید؟'] as $field=>$question)
   <div class="col-md-6 question-block"><label class="form-label">{{ $question }}</label><div class="yes-no">@foreach(\App\Models\CustomerSatisfactionForm::YES_NO as $value=>$label)<label><input type="radio" name="{{ $prefix }}[{{ $field }}]" value="{{ $value }}" @checked(($customer[$field] ?? '') === $value)><span>{{ $label }}</span></label>@endforeach</div></div>
  @endforeach</div>
 </div>
 <div class="non-buyer-section mt-4 question-block"><p class="text-muted">برای بهبود عملکرد مجموعه و بررسی دلیل خرید نکردن شما، لطفاً به سؤال زیر پاسخ دهید.</p><label class="form-label">دلیل اصلی خرید نکردن شما چه بوده است؟</label><div class="choice-grid">@foreach(\App\Models\CustomerSatisfactionForm::NO_PURCHASE_REASONS as $value=>$label)<label class="choice"><input type="radio" name="{{ $prefix }}[no_purchase_reason]" value="{{ $value }}" @checked(($customer['no_purchase_reason'] ?? '') === $value)><span>{{ $label }}</span></label>@endforeach</div></div>
 <div class="mt-4"><label class="form-label">توضیحات تکمیلی</label><textarea class="form-control" rows="3" name="{{ $prefix }}[description]">{{ $customer['description'] ?? '' }}</textarea></div>
</section>
