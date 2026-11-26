(() => {
 'use strict';
 const menu=document.getElementById('mainNav');
 const toggle=document.getElementById('menuToggle');
 const closeMenu=() => { menu?.classList.remove('open'); toggle?.setAttribute('aria-expanded','false'); toggle?.setAttribute('aria-label','Open navigation'); };
 toggle?.addEventListener('click',()=>toggle.setAttribute('aria-label',toggle.getAttribute('aria-expanded')==='true'?'Close navigation':'Open navigation'));
 document.addEventListener('keydown',event=>{if(event.key==='Escape'&&menu?.classList.contains('open')){closeMenu();toggle.focus();}});
 document.addEventListener('click',event=>{if(menu?.classList.contains('open')&&!menu.contains(event.target)&&!toggle?.contains(event.target))closeMenu();});
 menu?.addEventListener('focusout',()=>setTimeout(()=>{if(!menu.contains(document.activeElement)&&document.activeElement!==toggle)closeMenu();},0));
 const current=location.pathname.split('/').pop()||'index.html';
 menu?.querySelectorAll('a').forEach(a=>{if(a.getAttribute('href')===current)a.setAttribute('aria-current','page');});
 document.querySelectorAll('[data-back-to-top]').forEach(link=>link.addEventListener('click',event=>{
  event.preventDefault();
  const main=document.getElementById('main-content');
  main?.focus({preventScroll:true});
  window.scrollTo({top:0,behavior:matchMedia('(prefers-reduced-motion: reduce)').matches?'instant':'smooth'});
 }));
 document.querySelectorAll('[data-enquiry-form]').forEach(async form=>{
  const submit=form.querySelector('button[aria-disabled]');
  const status=form.querySelector('.form-status');
  const kind=form.dataset.enquiryForm;
  let ready=false;
  const endpoint=new URL('forms.php',location.href);
  const report=message=>{status.textContent=message;};
  async function refreshToken(){
   const response=await fetch(endpoint+'?form='+encodeURIComponent(kind),{credentials:'same-origin',cache:'no-store',headers:{Accept:'application/json'}});
   if(!response.ok)throw new Error('unavailable');
   const result=await response.json();
   if(!result.ready||!result.token)throw new Error('unavailable');
   form.elements.csrf_token.value=result.token;
   ready=true; submit.disabled=false; submit.removeAttribute('aria-disabled');
  }
  form.addEventListener('submit',async event=>{
   event.preventDefault();
   if(!ready){report('Online submission is currently unavailable. Please email admin@thechorefacilitator.co.za.');return;}
   if(!form.reportValidity())return;
   submit.disabled=true; form.setAttribute('aria-busy','true'); report('Sending…');
   const controller=new AbortController();
   const timer=setTimeout(()=>controller.abort(),30000);
   try {
    const response=await fetch(endpoint,{method:'POST',credentials:'same-origin',body:new FormData(form),headers:{Accept:'application/json'},signal:controller.signal});
    const result=await response.json();
    if(!response.ok||!result.ok){
     if(response.status===403){await refreshToken();throw new Error('Your session expired. Your details are still here; please submit again.');}
     throw new Error(result.message||'We could not send your message. Your details are still here; please try again or email admin@thechorefacilitator.co.za.');
    }
    form.reset(); report('Your information has been received. Thank you. If you do not hear back, please email admin@thechorefacilitator.co.za.');
    ready=false;
    try{await refreshToken();}catch{report('Your information has been received. Please refresh the page before sending another message.');}
   }catch(error){report(error.name==='AbortError'?'The request timed out and delivery could not be confirmed. Your details are still here. Please contact admin@thechorefacilitator.co.za before sending again.':error.message||'Sending failed. Your details are still here.');}
   finally{clearTimeout(timer);submit.disabled=!ready;form.removeAttribute('aria-busy');status.focus({preventScroll:true});}
  });
  if(location.hostname.endsWith('.github.io')||document.documentElement.dataset.siteMode==='preview'){
   report('Client preview: this form is available to review, but submissions and email delivery are disabled until the website launches.');
   const note=form.querySelector('[class$="submit"] p, .client-submit-area p');if(note)note.textContent='Client preview — email submission is not active.';
   return;
  }
  try{await refreshToken(); const note=form.querySelector('[class$="submit"] p, .client-submit-area p'); if(note)note.textContent='Your information will be used to respond to your enquiry.';}
  catch{report('Online submission is currently unavailable. Please email admin@thechorefacilitator.co.za.');}
 });
})();
