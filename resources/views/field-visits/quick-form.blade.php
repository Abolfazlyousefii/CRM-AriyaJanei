<x-app-layout>
    <x-slot name="header"><div class="d-flex flex-wrap justify-content-between align-items-center gap-2"><h2 class="h5 mb-0">نقطه‌زنی فروشگاه‌ها</h2><a class="btn btn-sm btn-outline-secondary" href="{{ route('field-visits.index') }}"><i class="bi bi-arrow-right" aria-hidden="true"></i> بازدیدهای من</a></div></x-slot>
    @push('styles')
        <link rel="stylesheet" href="https://static.neshan.org/sdk/leaflet/v1.9.4/neshan-sdk/v1.0.8/index.css">
        @include('field-visits.entry-styles')
    @endpush
    @php
        $hasBuyingData = count(old('requested_products',$contact?->requested_products) ?? []) || count(old('purchase_priorities',$contact?->purchase_priorities) ?? []) || old('purchase_volume',$contact?->purchase_volume) || old('purchase_cycle',$contact?->purchase_cycle) || old('preferred_payment',$contact?->preferred_payment) || old('next_action',$contact?->next_action) || old('requested_products_note',$contact?->requested_products_note);
        $buyingErrors = $errors->hasAny(['requested_products','requested_products.*','purchase_priorities','purchase_priorities.*','purchase_volume','purchase_cycle','preferred_payment','next_action','requested_products_note']);
        $moreErrors = $errors->hasAny(['owner_name','shop_grade','description','follow_up_status','next_follow_up_at','follow_up_note']);
    @endphp
    <div class="fv-entry">
        <header class="entry-hero">
            <div class="entry-hero-icon" aria-hidden="true"><i class="bi bi-geo-alt"></i></div>
            <div class="entry-hero-copy"><span class="entry-eyebrow">ویزیت و شناسایی مشتری</span><h1>{{ $contact ? 'ویرایش بازدید فروشگاه' : 'یک بازدید جدید ثبت کنید' }}</h1><p>مشخصات اصلی، نتیجهٔ گفتگو و موقعیت فروشگاه؛ باقی اطلاعات اختیاری است.</p></div>
            <div class="entry-agent"><i class="bi bi-person" aria-hidden="true"></i><span>ثبت به نام<strong>{{ $contact ? ($contact->user?->name ?? $contact->visitor_name) : auth()->user()->name }}</strong></span></div>
        </header>
        @if(session('success'))<div class="alert alert-success" role="status">{{ session('success') }}</div>@endif
        @if($errors->any())<div class="alert alert-danger" role="alert"><strong>چند مورد نیاز به اصلاح دارد.</strong><ul class="mb-0 mt-2">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
        <form id="quick-visit-form" class="entry-grid" method="POST" action="{{ $contact ? route('field-visits.quick-update',$contact) : route('field-visits.quick-store') }}">
            @csrf @if($contact) @method('PUT') @endif
            <section class="entry-card entry-info" aria-labelledby="entry-info-title">
                <div class="entry-section-head"><span class="entry-section-icon" aria-hidden="true"><i class="bi bi-shop"></i></span><div><h2 id="entry-info-title">مشخصات فروشگاه</h2><p>فیلدهای ستاره‌دار برای ثبت ضروری‌اند.</p></div><span id="entry-info-badge" class="entry-badge">اطلاعات اصلی</span></div>
                <div class="row g-3">
                    <div class="col-12"><label for="shop_name" class="form-label">نام فروشگاه <span class="entry-required">*</span></label><input id="shop_name" name="shop_name" class="form-control @error('shop_name') is-invalid @enderror" required maxlength="120" autocomplete="organization" placeholder="مثلاً موبایل آریا" value="{{ old('shop_name',$contact?->shop_name) }}">@error('shop_name')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                    <div class="col-sm-6"><label for="owner_phone" class="form-label">شماره مسئول خرید <span class="entry-required">*</span></label><input id="owner_phone" name="owner_phone" type="tel" class="form-control @error('owner_phone') is-invalid @enderror" required maxlength="30" inputmode="tel" autocomplete="tel" dir="ltr" placeholder="09121234567" value="{{ old('owner_phone',$contact?->owner_phone) }}">@error('owner_phone')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                    <div class="col-sm-6"><label for="buyer_name" class="form-label">نام مسئول خرید <small>اختیاری</small></label><input id="buyer_name" name="buyer_name" class="form-control" maxlength="120" autocomplete="name" placeholder="نام و نام خانوادگی" value="{{ old('buyer_name',$contact?->buyer_name) }}"></div>
                    @foreach(['customer_type'=>'نوع مشتری','visit_result'=>'نتیجه بازدید'] as $field=>$label)
                    <div class="col-sm-6"><label for="{{ $field }}" class="form-label">{{ $label }} <span class="entry-required">*</span></label><select id="{{ $field }}" name="{{ $field }}" class="form-select @error($field) is-invalid @enderror" required><option value="">انتخاب کنید</option>@foreach(\App\Models\ShopContact::WHOLESALE_OPTIONS[$field] as $code=>$text)<option value="{{ $code }}" @selected(old($field,$contact?->$field)===$code)>{{ $text }}</option>@endforeach</select>@error($field)<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                    @endforeach
                </div>
            </section>
            <section class="entry-card entry-location" aria-labelledby="quick-location-title">
                <div class="entry-section-head"><span class="entry-section-icon entry-section-icon-teal" aria-hidden="true"><i class="bi bi-geo-alt"></i></span><div><h2 id="quick-location-title">موقعیت فروشگاه</h2><p>نقطهٔ فروشگاه را روی نقشه مشخص کنید.</p></div><span id="entry-location-badge" class="entry-badge">انتخاب نشده</span></div>
                <button id="quick-location" type="button" class="entry-gps"><i class="bi bi-geo-alt" aria-hidden="true"></i> انتخاب موقعیت فعلی من</button>
                <p id="quick-geo-status" class="entry-status" role="status">جلوی فروشگاه هستید؟ دکمهٔ بالا را بزنید؛ یا روی نقشه انتخاب کنید.</p>
                <div id="quick-map"></div>
                <input id="quick-lat" name="lat" type="hidden" value="{{ old('lat',$contact?->lat) }}"><input id="quick-lng" name="lng" type="hidden" value="{{ old('lng',$contact?->lng) }}">
                <p id="quick-location-error" class="entry-location-error" role="alert" @if(!$errors->has('lat') && !$errors->has('lng')) hidden @endif>ابتدا موقعیت فروشگاه را انتخاب کنید.</p>
                <div class="entry-address"><p id="quick-address-status" class="entry-status" role="status">شهر و آدرس پس از انتخاب موقعیت پیشنهاد می‌شوند.</p><button id="quick-fill-address" type="button" class="entry-text-button">دریافت دوبارهٔ آدرس <i class="bi bi-arrow-repeat" aria-hidden="true"></i></button></div>
                <details class="entry-address-details" @if($errors->hasAny(['city','address'])) open @endif><summary>مشاهده یا اصلاح شهر و آدرس <i class="bi bi-chevron-down" aria-hidden="true"></i></summary><div class="row g-3 mt-1"><div class="col-12"><label for="city" class="form-label">شهر <small>اختیاری</small></label><input id="city" name="city" class="form-control" maxlength="60" value="{{ old('city',$contact?->city) }}"></div><div class="col-12"><label for="address" class="form-label">آدرس پیشنهادی <small>اختیاری</small></label><textarea id="address" name="address" class="form-control" maxlength="200" rows="2">{{ old('address',$contact?->address) }}</textarea></div></div></details>
                <details id="quick-manual-location" class="entry-address-details" hidden><summary>ورود دستی مختصات <i class="bi bi-chevron-down" aria-hidden="true"></i></summary><div class="row g-2 mt-1"><div class="col-6"><label for="manual-lat" class="form-label">عرض جغرافیایی</label><input id="manual-lat" type="number" min="-90" max="90" step="any" class="form-control" dir="ltr" value="{{ old('lat',$contact?->lat) }}"></div><div class="col-6"><label for="manual-lng" class="form-label">طول جغرافیایی</label><input id="manual-lng" type="number" min="-180" max="180" step="any" class="form-control" dir="ltr" value="{{ old('lng',$contact?->lng) }}"></div></div></details>
                <p class="entry-attribution">پیشنهاد آدرس: <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener">© OpenStreetMap contributors</a> · قابل اصلاح</p>
            </section>
            <details id="entry-buying" class="entry-card entry-disclosure entry-buying" @if($hasBuyingData || $buyingErrors) open @endif>
                <summary><span class="entry-section-icon" aria-hidden="true"><i class="bi bi-bag"></i></span><span class="entry-summary-copy"><strong>نیاز و ظرفیت خرید</strong><small id="entry-buying-summary">کالاها، حجم خرید و شرایط همکاری</small></span><span class="entry-optional">اختیاری</span><i class="bi bi-chevron-down entry-chevron" aria-hidden="true"></i></summary>
                <div class="entry-disclosure-body">@include('field-visits.wholesale-fields')</div>
            </details>
            <details id="entry-more" class="entry-card entry-disclosure entry-more" @if($moreErrors) open @endif>
                <summary><span class="entry-section-icon entry-section-icon-teal" aria-hidden="true"><i class="bi bi-chat-left-text"></i></span><span class="entry-summary-copy"><strong>یادداشت و پیگیری</strong><small>توضیحات، تاریخ تماس و اطلاعات تکمیلی</small></span><span class="entry-optional">اختیاری</span><i class="bi bi-chevron-down entry-chevron" aria-hidden="true"></i></summary>
                <div class="entry-disclosure-body"><div class="row g-3">
                    <div class="col-12"><label for="description" class="form-label">یادداشت بازدید</label><textarea id="description" name="description" class="form-control" rows="3" maxlength="2000" placeholder="نتیجه گفتگو یا نکته‌ای برای پیگیری">{{ old('description',$contact?->description) }}</textarea></div>
                    <div class="col-12">@include('field-visits.follow-up-fields')</div>
                    <div class="col-12 entry-full-form-mobile"><a href="{{ $contact ? route('field-visits.edit',['contact'=>$contact,'detailed'=>1]) : route('field-visits.create',['detailed'=>1]) }}">فرم کامل ارزیابی فروشگاه <i class="bi bi-arrow-left" aria-hidden="true"></i></a></div>
                    <div class="col-sm-6"><label for="owner_name" class="form-label">نام مالک</label><input id="owner_name" name="owner_name" class="form-control" maxlength="120" value="{{ old('owner_name',$contact?->owner_name) }}"></div>
                    <div class="col-sm-6"><label for="shop_grade" class="form-label">گرید فروشگاه</label><select id="shop_grade" name="shop_grade" class="form-select"><option value="">هنوز مشخص نیست</option>@foreach(['A - خرید عمده/فروشگاه بزرگ/فروش پرچمدار','B - ویترین متوسط / فروش میان رده','C - حجم خرید کم/ تعمیرکار/ فروش پایین رده'] as $grade)<option @selected(old('shop_grade',$contact?->shop_grade) === $grade)>{{ $grade }}</option>@endforeach</select></div>
                </div></div>
            </details>
            <footer class="entry-submit-bar">
                <div id="entry-progress" class="entry-progress" aria-live="polite"><span id="entry-basic-ready"><i class="bi bi-circle" aria-hidden="true"></i> مشخصات فروشگاه</span><span id="entry-point-ready"><i class="bi bi-circle" aria-hidden="true"></i> موقعیت</span></div>
                <button id="quick-submit" type="submit" class="entry-submit">{{ $contact ? 'ذخیره تغییرات' : 'ثبت بازدید' }}</button>
                <a class="entry-full-form" href="{{ $contact ? route('field-visits.edit',['contact'=>$contact,'detailed'=>1]) : route('field-visits.create',['detailed'=>1]) }}">فرم کامل ارزیابی</a>
            </footer>
        </form>
    </div>
    @push('scripts')
        <script src="https://static.neshan.org/sdk/leaflet/v1.9.4/neshan-sdk/v1.0.8/index.js"></script>
        <script>
        (() => {
            const form=document.getElementById('quick-visit-form'),lat=document.getElementById('quick-lat'),lng=document.getElementById('quick-lng'),status=document.getElementById('quick-geo-status'),button=document.getElementById('quick-location'),error=document.getElementById('quick-location-error'),manual=document.getElementById('quick-manual-location'),submit=document.getElementById('quick-submit');
            const city=document.getElementById('city'),address=document.getElementById('address'),addressStatus=document.getElementById('quick-address-status');
            function refreshEntry(){
                const fields=['shop_name','owner_phone','customer_type','visit_result'].map(id=>document.getElementById(id));
                const filled=fields.filter(input=>input.value.trim()!=='').length,ready=filled===fields.length,located=validPosition();
                const basic=document.getElementById('entry-basic-ready'),point=document.getElementById('entry-point-ready');
                basic.classList.toggle('is-done',ready);point.classList.toggle('is-done',located);
                basic.querySelector('i').className=ready?'bi bi-check-circle-fill':'bi bi-circle';point.querySelector('i').className=located?'bi bi-check-circle-fill':'bi bi-circle';
                document.getElementById('entry-info-badge').textContent=ready?'تکمیل شد':new Intl.NumberFormat('fa-IR').format(filled)+' از ۴';
                document.getElementById('entry-location-badge').textContent=located?'انتخاب شد':'انتخاب نشده';
                document.querySelector('.entry-location').classList.toggle('is-located',located);
                const count=form.querySelectorAll('[name="requested_products[]"]:checked').length;
                document.getElementById('entry-buying-summary').textContent=count?new Intl.NumberFormat('fa-IR').format(count)+' گروه کالا انتخاب شده':'کالاها، حجم خرید و شرایط همکاری';
            }
            form.addEventListener('input',refreshEntry);form.addEventListener('change',refreshEntry);
            form.addEventListener('invalid',event=>{let parent=event.target.closest('details');while(parent){parent.open=true;parent=parent.parentElement.closest('details');}},true);
            let addressRequest=0,addressTimer=null,addressAbort=null,addressPending=Promise.resolve();
            [city,address].forEach(input=>input.addEventListener('input',()=>{input.dataset.manual='1';}));
            async function suggestAddress(){
                if(!validPosition())return;
                const id=++addressRequest,pointLat=lat.value,pointLng=lng.value,originalCity=city.value,originalAddress=address.value;
                if(addressAbort)addressAbort.abort();const currentAbort=addressAbort=new AbortController();let timedOut=false;const timeout=setTimeout(()=>{timedOut=true;currentAbort.abort();},8000);
                addressStatus.textContent='در حال پیشنهاد شهر و آدرس...';
                try{const url=new URL(@json(route('field-visits.location')));url.searchParams.set('lat',pointLat);url.searchParams.set('lng',pointLng);
                    const response=await fetch(url,{headers:{Accept:'application/json'},credentials:'same-origin',signal:currentAbort.signal});
                    const data=await response.json();if(id!==addressRequest||lat.value!==pointLat||lng.value!==pointLng)return;
                    if(!response.ok||!data.available)throw new Error('Address unavailable');
                    if(city.dataset.manual!=='1'&&city.value===originalCity)city.value=data.city||'';
                    if(address.dataset.manual!=='1'&&address.value===originalAddress)address.value=data.address||'';
                    addressStatus.textContent='شهر و آدرس پیشنهاد شد؛ در صورت نیاز از بخش زیر اصلاح کنید.';
                }catch(e){if((e.name!=='AbortError'||timedOut)&&id===addressRequest)addressStatus.textContent='پیشنهاد آدرس در دسترس نیست؛ می‌توانید بدون شهر و آدرس ثبت کنید یا آن‌ها را دستی بنویسید.';}finally{clearTimeout(timeout);}
            }
            function scheduleAddress(){clearTimeout(addressTimer);addressRequest++;if(addressAbort)addressAbort.abort();addressTimer=setTimeout(()=>{addressTimer=null;addressPending=suggestAddress();},700);}
            document.getElementById('quick-fill-address').addEventListener('click',()=>{if(!validPosition()){addressStatus.textContent='ابتدا موقعیت فروشگاه را انتخاب کنید.';return;}city.dataset.manual='';address.dataset.manual='';clearTimeout(addressTimer);addressTimer=null;addressPending=suggestAddress();});
            const key=@json(config('field_visits.neshan_key'));let map=null,marker=null;
            const validPosition=()=>lat.value!==''&&lng.value!==''&&Number.isFinite(Number(lat.value))&&Number.isFinite(Number(lng.value))&&Math.abs(Number(lat.value))<=90&&Math.abs(Number(lng.value))<=180;
            function position(latitude,longitude,lookup=true){lat.value=latitude;lng.value=longitude;document.getElementById('manual-lat').value=latitude;document.getElementById('manual-lng').value=longitude;error.hidden=validPosition();status.textContent='موقعیت فروشگاه انتخاب شد.';if(map){if(!marker)marker=L.marker([latitude,longitude]).addTo(map);else marker.setLatLng([latitude,longitude]);map.setView([latitude,longitude],16);}if(lookup)scheduleAddress();refreshEntry();}
            button.addEventListener('click',()=>{if(!navigator.geolocation){status.textContent='موقعیت گوشی در دسترس نیست؛ روی نقشه انتخاب کنید.';manual.hidden=false;return;}button.disabled=true;status.textContent='در حال دریافت موقعیت...';navigator.geolocation.getCurrentPosition(p=>{position(p.coords.latitude,p.coords.longitude);status.textContent='موقعیت دریافت شد. نقطهٔ فروشگاه را روی نقشه بررسی کنید.';button.disabled=false;},()=>{status.textContent='موقعیت دریافت نشد؛ اجازهٔ موقعیت گوشی را فعال کنید یا روی نقشه انتخاب کنید.';manual.hidden=false;button.disabled=false;},{enableHighAccuracy:true,timeout:10000,maximumAge:0});});
            try{if(typeof L==='undefined'||!key)throw new Error('Map unavailable');map=new L.Map('quick-map',{key,maptype:'neshan',poi:true,traffic:false,center:[35.699756,51.338076],zoom:12,scrollWheelZoom:false});map.on('click',e=>position(e.latlng.lat,e.latlng.lng));if(validPosition())position(Number(lat.value),Number(lng.value),false);}catch(e){map=null;document.getElementById('quick-map').hidden=true;manual.hidden=false;status.textContent='نقشه بارگذاری نشد. با دکمهٔ موقعیت گوشی، محل فروشگاه را ثبت کنید.';}
            ['manual-lat','manual-lng'].forEach(id=>document.getElementById(id).addEventListener('input',()=>{lat.value=document.getElementById('manual-lat').value;lng.value=document.getElementById('manual-lng').value;error.hidden=validPosition();if(validPosition())scheduleAddress();refreshEntry();}));
            let sending=false;
            form.addEventListener('submit',async event=>{event.preventDefault();if(sending)return;if(!validPosition()){error.hidden=false;error.scrollIntoView({behavior:'smooth',block:'center'});button.focus({preventScroll:true});return;}sending=true;submit.disabled=true;submit.textContent='در حال ثبت...';if(addressTimer){clearTimeout(addressTimer);addressTimer=null;addressPending=suggestAddress();}await addressPending;HTMLFormElement.prototype.submit.call(form);});
            refreshEntry();
            window.addEventListener('pageshow',()=>{sending=false;submit.disabled=false;submit.textContent=@json($contact ? 'ذخیره تغییرات' : 'ثبت بازدید');});
        })();
        </script>
    @endpush
</x-app-layout>





