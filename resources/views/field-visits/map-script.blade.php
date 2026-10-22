<script src="https://static.neshan.org/sdk/leaflet/v1.9.4/neshan-sdk/v1.0.8/index.js"></script>
<script>
(() => {
    const points = @json($points);
    const container = document.getElementById(@json($mapId));
    const message = document.getElementById('map-message');
    if (typeof L === 'undefined') { if(message) message.textContent = 'نقشه بارگذاری نشد؛ اطلاعات فروشگاه‌ها در جدول در دسترس است.'; return; }
    const key = @json(config('field_visits.neshan_key'));
    if (!key) { if(message) message.textContent = 'کلید نقشه تنظیم نشده است.'; return; }
    const map = new L.Map(container, {key, maptype:'neshan', poi:true, traffic:false, center:[35.699756,51.338076],zoom:11});
    points.forEach(p => {
        const content = document.createElement('div');
        const link = document.createElement('a'); link.href = p.url; link.textContent = p.name;
        content.append(link, document.createElement('br'), document.createTextNode(p.city + ' · ' + p.status));
        L.marker([p.lat,p.lng]).addTo(map).bindPopup(content);
    });
    if(points.length) map.fitBounds(points.map(p=>[p.lat,p.lng]), {padding:[30,30],maxZoom:16});
    else if(message) message.textContent = 'در نتیجه فیلترها نقطه دارای مختصات وجود ندارد.';
})();
</script>
