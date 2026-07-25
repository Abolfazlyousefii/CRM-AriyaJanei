<script>
(()=>{const container=document.querySelector('#customers-container'),template=document.querySelector('#customer-template');if(!container)return;
const clearSection=(section)=>section.querySelectorAll('input,select,textarea').forEach(el=>{if(el.type==='radio'||el.type==='checkbox')el.checked=false;else el.value=''});
const sync=card=>{const checked=card.querySelector('[name$="[purchase_status]"]:checked');card.dataset.status=checked?.value||''};
const renumber=()=>container.querySelectorAll('.customer-card').forEach((card,i)=>{card.dataset.index=i;card.querySelector('.customer-number').textContent=i+1;card.querySelector('.remove-customer').classList.toggle('d-none',i===0);card.querySelectorAll('[name]').forEach(el=>el.name=el.name.replace(/^customers\[\d+]/,`customers[${i}]`))});
container.addEventListener('change',e=>{if(e.target.matches('[name$="[purchase_status]"]')){const card=e.target.closest('.customer-card');clearSection(card.querySelector(e.target.value==='purchased'?'.non-buyer-section':'.buyer-section'));sync(card)}});
container.addEventListener('click',e=>{const b=e.target.closest('.remove-customer');if(b){b.closest('.customer-card').remove();renumber()}});
document.querySelector('#add-customer-btn')?.addEventListener('click',()=>{container.insertAdjacentHTML('beforeend',template.innerHTML.replaceAll('__INDEX__',container.children.length));renumber();window.jalaliDatepicker?.startWatch()});
container.querySelectorAll('.customer-card').forEach(sync);window.jalaliDatepicker?.startWatch();})();
</script>
