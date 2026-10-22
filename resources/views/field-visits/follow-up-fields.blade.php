<div class="visit-help mb-4">
    <h3 class="h6">قدم بعدی برای این فروشگاه</h3>
    <div class="row g-3">
        <div class="col-md-6"><label for="follow_up_status" class="form-label">وضعیت پیگیری</label><select id="follow_up_status" name="follow_up_status" class="form-select">
            @foreach (\App\Models\ShopContact::STATUSES as $value => $label)
                <option value="{{ $value }}" @selected(old('follow_up_status', $contact?->follow_up_status ?? 'new') === $value)>{{ $label }}</option>
            @endforeach
        </select></div>
        <div class="col-md-6"><label for="next_follow_up_at" class="form-label">تاریخ پیگیری بعدی (میلادی)</label><input id="next_follow_up_at" name="next_follow_up_at" type="date" class="form-control" value="{{ old('next_follow_up_at', $contact?->next_follow_up_at?->format('Y-m-d')) }}"><small class="text-muted">تاریخ‌ها در گزارش به شمسی نمایش داده می‌شوند.</small></div>
        <div class="col-12"><label for="follow_up_note" class="form-label">یادداشت پیگیری</label><textarea id="follow_up_note" name="follow_up_note" class="form-control" rows="2" maxlength="2000" placeholder="مثلاً ارسال لیست قیمت و تماس مجدد">{{ old('follow_up_note', $contact?->follow_up_note) }}</textarea></div>
    </div>
</div>
