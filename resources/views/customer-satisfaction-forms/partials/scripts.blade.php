<script src="{{ asset('lib/jalalidatepicker.min.js') }}"></script>
<script>
(()=>{const container=document.querySelector('#customers-container'),template=document.querySelector('#customer-template');if(!container)return;let datepickerStarted=false;
const startDatepicker=()=>{if(!datepickerStarted&&typeof window.jalaliDatepicker!=='undefined'){window.jalaliDatepicker.startWatch({time:false,autoHide:true,separatorChars:{date:'/'}});datepickerStarted=true}};
const clearSection=(section)=>section.querySelectorAll('input,select,textarea').forEach(el=>{if(el.type==='radio'||el.type==='checkbox')el.checked=false;else el.value=''});
const sync=card=>{const checked=card.querySelector('[name$="[purchase_status]"]:checked');card.dataset.status=checked?.value||''};
const renumber=()=>container.querySelectorAll('.customer-card').forEach((card,i)=>{card.dataset.index=i;card.querySelector('.customer-number').textContent=i+1;card.querySelector('.remove-customer').classList.toggle('d-none',i===0);card.querySelectorAll('[name]').forEach(el=>el.name=el.name.replace(/^customers\[\d+]/,`customers[${i}]`))});
container.addEventListener('change',e=>{if(e.target.matches('[name$="[purchase_status]"]')){const card=e.target.closest('.customer-card');clearSection(card.querySelector(e.target.value==='purchased'?'.non-buyer-section':'.buyer-section'));sync(card)}});
container.addEventListener('click',e=>{const trigger=e.target.closest('.cs-date-trigger');if(trigger){const input=trigger.closest('.cs-date-field')?.querySelector('.cs-jalali-date');if(input&&typeof window.jalaliDatepicker!=='undefined')window.jalaliDatepicker.show(input);return}const b=e.target.closest('.remove-customer');if(b){b.closest('.customer-card').remove();renumber()}});
container.addEventListener('paste',e=>{if(e.target.matches('.cs-jalali-date'))e.preventDefault()});container.addEventListener('drop',e=>{if(e.target.matches('.cs-jalali-date'))e.preventDefault()});
document.querySelector('#add-customer-btn')?.addEventListener('click',()=>{const nextIndex=container.children.length;container.insertAdjacentHTML('beforeend',template.innerHTML.replaceAll('__INDEX__',nextIndex).replaceAll('__INDEX_DISPLAY__',nextIndex+1));renumber();startDatepicker()});
container.querySelectorAll('.customer-card').forEach(sync);if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',startDatepicker,{once:true});else startDatepicker()})();
</script>
