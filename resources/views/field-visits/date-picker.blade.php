@once
    @push('styles')
        <link rel="stylesheet" href="{{ asset('lib/jalalidatepicker.min.css') }}">
        <style>jdp-container{z-index:10000!important}input[data-fv-jdp]{text-align:right;direction:ltr}</style>
    @endpush
    @push('scripts')
        <script src="{{ asset('lib/jalalidatepicker.min.js') }}"></script>
        <script>
            (() => {
                const persian = value => value.replace(/[0-9]/g, digit => '۰۱۲۳۴۵۶۷۸۹'[Number(digit)]);
                const english = value => value.replace(/[۰-۹]/g, digit => '۰۱۲۳۴۵۶۷۸۹'.indexOf(digit));
                document.querySelectorAll('[data-fv-jdp]').forEach(input => {
                    input.value = persian(input.value);
                    input.addEventListener('focusin', () => { input.value = english(input.value); }, true);
                    ['jdp:change', 'change', 'blur'].forEach(event => input.addEventListener(event, () => { input.value = persian(input.value); }));
                });
                jalaliDatepicker.startWatch({selector:'[data-fv-jdp]',time:false,persianDigits:true,autoHide:true,showCloseBtn:true,separatorChars:{date:'/'},zIndex:10000});
                document.addEventListener('pointerdown', event => {
                    if (!event.target.closest('jdp-container, [data-fv-jdp]')) jalaliDatepicker.hide();
                }, true);
                document.addEventListener('keydown', event => {
                    if (event.key === 'Escape') jalaliDatepicker.hide();
                });
            })();
        </script>
    @endpush
@endonce
