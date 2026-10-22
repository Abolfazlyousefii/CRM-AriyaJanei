@php($options = \App\Models\ShopContact::WHOLESALE_OPTIONS)
<div class="row g-3">
    <div class="col-12"><fieldset><legend class="h6">کالاهای موردنیاز مشتری</legend><input type="hidden" name="requested_products" value=""><div class="qv-choices">
        @foreach($options['requested_products'] as $code=>$label)<label><input type="checkbox" name="requested_products[]" value="{{ $code }}" @checked(in_array($code,old('requested_products',$contact?->requested_products) ?? []))> {{ $label }}</label>@endforeach
    </div></fieldset></div>
    <div class="col-12"><label for="requested_products_note" class="form-label">کالای دیگر یا برند موردنظر</label><input id="requested_products_note" name="requested_products_note" class="form-control" maxlength="255" value="{{ old('requested_products_note',$contact?->requested_products_note) }}" placeholder="اختیاری؛ مثلاً شارژر ۲۵ وات یا برند موردنظر"></div>
    @foreach(['purchase_volume'=>'حجم تقریبی خرید ماهانه (تومان)','purchase_cycle'=>'دوره خرید','preferred_payment'=>'روش پرداخت ترجیحی'] as $field=>$label)
    <div class="col-md-6"><label for="{{ $field }}" class="form-label">{{ $label }}</label><select id="{{ $field }}" name="{{ $field }}" class="form-select"><option value="">نامشخص</option>@foreach($options[$field] as $code=>$text)<option value="{{ $code }}" @selected(old($field,$contact?->$field)===$code)>{{ $text }}</option>@endforeach</select>@error($field)<div class="text-danger small">{{ $message }}</div>@enderror</div>
    @endforeach
    <div class="col-12"><fieldset><legend class="h6">چه چیزی برای مشتری مهم‌تر است؟</legend><input type="hidden" name="purchase_priorities" value=""><div class="qv-choices">@foreach($options['purchase_priorities'] as $code=>$label)<label><input type="checkbox" name="purchase_priorities[]" value="{{ $code }}" @checked(in_array($code,old('purchase_priorities',$contact?->purchase_priorities) ?? []))> {{ $label }}</label>@endforeach</div></fieldset></div>
    <div class="col-12"><label for="next_action" class="form-label">اقدام بعدی</label><select id="next_action" name="next_action" class="form-select"><option value="">هنوز مشخص نیست</option>@foreach($options['next_action'] as $code=>$label)<option value="{{ $code }}" @selected(old('next_action',$contact?->next_action)===$code)>{{ $label }}</option>@endforeach</select></div>
    <div class="col-12"><small class="text-muted">حجم خرید برآورد مشتری است. روش پرداخت ترجیحی به معنی تأیید اعتبار یا فروش چکی نیست.</small></div>
</div>
