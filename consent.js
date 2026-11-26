/* Optional services must be registered here BEFORE this script runs. Nothing is
   installed by default. Each entry needs category ('analytics'/'marketing'), src,
   and its cookies [{name,path,domain}] and storageKeys for withdrawal cleanup.
   Do not add the same services as ordinary script tags: that bypasses consent. */
(() => {
 'use strict';
 const services = Array.isArray(window.TCF_OPTIONAL_SERVICES) ? window.TCF_OPTIONAL_SERVICES : [];
 const categories = ['analytics','marketing'];
 const active = categories.filter(category => services.some(s=>s.category===category));
 const key='tcf-cookie-choice-v1';
 const maxAge=180*24*60*60*1000;
 const fingerprint=JSON.stringify(services.map(s=>[s.category,s.src,s.cookies||[],s.storageKeys||[]]));
 const privacySignal=navigator.globalPrivacyControl===true||navigator.doNotTrack==='1';
 let state={analytics:false,marketing:false}, saved=false, persistence=true, dialog, banner;
 const loaded=new Set();
 function clean(service){
  for(const cookie of service.cookies||[]){
   if(!/^[\w.-]+$/.test(cookie.name))continue;
   document.cookie=cookie.name+'=; Max-Age=0; path='+(cookie.path||'/')+(cookie.domain?'; domain='+cookie.domain:'')+'; SameSite=Lax';
  }
  for(const storageKey of service.storageKeys||[]){try{localStorage.removeItem(storageKey);sessionStorage.removeItem(storageKey);}catch{}}
 }
 try {
  const choice=JSON.parse(localStorage.getItem(key)||'null');
  if(choice&&choice.version===1&&choice.fingerprint===fingerprint&&Number.isFinite(choice.time)&&Date.now()-choice.time>=0&&Date.now()-choice.time<maxAge){
   saved=true; for(const category of active)state[category]=choice[category]===true&&!privacySignal;
  }
 }catch{persistence=false;}
 if(new URL(location.href).searchParams.get('tcf-consent')==='reject'){
  state={analytics:false,marketing:false};saved=true;
 }
 function apply(){
  for(const service of services){
   if(!categories.includes(service.category)||!state[service.category]||privacySignal){clean(service);continue;}
   if(loaded.has(service))continue;
   // The registry is trusted site code, not user input. HTTPS services only.
   let url;try{url=new URL(service.src,location.href);}catch{continue;}
   if(url.protocol!=='https:'&&url.origin!==location.origin)continue;
   const script=document.createElement('script');script.src=url.href;script.async=true;
   script.dataset.optionalConsent=service.category;loaded.add(service);document.head.append(script);
  }
 }
 function save(next){
  const revoke=services.some(s=>loaded.has(s)&&!next[s.category]);
  state={analytics:active.includes('analytics')&&next.analytics===true&&!privacySignal,marketing:active.includes('marketing')&&next.marketing===true&&!privacySignal};
  try{localStorage.setItem(key,JSON.stringify({...state,version:1,time:Date.now(),fingerprint}));persistence=true;}catch{persistence=false;}
  saved=true;apply();banner?.remove();dialog?.close();
  // Removing a script tag does not stop code that has already executed. Reload
  // after saving a withdrawal so no optional service starts on the next page.
  if(revoke){
   if(persistence)location.reload();
   else {const url=new URL(location.href);url.searchParams.set('tcf-consent','reject');location.replace(url.href);}
  }else if(persistence){
   const url=new URL(location.href);if(url.searchParams.has('tcf-consent')){url.searchParams.delete('tcf-consent');history.replaceState(null,'',url.href);}
  }
 }
 function openSettings(){
  if(!dialog){
   dialog=document.createElement('dialog');dialog.className='cookie-dialog';dialog.setAttribute('aria-labelledby','cookie-dialog-title');
   dialog.innerHTML='<h2 id="cookie-dialog-title">Cookie settings</h2><p data-consent-summary></p><p>Essential storage protects forms and remembers your cookie choices. It cannot be switched off here.</p><fieldset class="consent-options"><legend>Optional cookies</legend><label><input type="checkbox" name="analytics"> Analytics <small data-for="analytics"></small></label><label><input type="checkbox" name="marketing"> Marketing <small data-for="marketing"></small></label></fieldset><p data-privacy-signal></p><p>Google Fonts supplies our fonts and receives connection information. Read our <a href="cookies.html">cookie policy</a> and <a href="privacy.html">privacy policy</a>.</p><div class="consent-actions"><button type="button" data-consent-action="accept">Accept all</button><button type="button" data-consent-action="reject">Reject optional</button><button type="button" data-consent-action="save">Save preferences</button><button type="button" data-consent-action="close">Close</button></div>';
   document.body.append(dialog);
   dialog.addEventListener('click',event=>{
    const action=event.target.closest('[data-consent-action]')?.dataset.consentAction;
    if(action==='close')dialog.close();
    if(action==='accept')save({analytics:true,marketing:true});
    if(action==='reject')save({analytics:false,marketing:false});
    if(action==='save')save({analytics:dialog.querySelector('[name=analytics]').checked,marketing:dialog.querySelector('[name=marketing]').checked});
   });
  }
  dialog.querySelector('[data-consent-summary]').textContent=active.length?'Choose which optional cookies you allow. You can change or withdraw your choice here at any time.':'No optional tracking is installed. Analytics and marketing are unavailable; only essential functionality is in use.';
  for(const category of categories){
   const input=dialog.querySelector('[name='+category+']');input.checked=state[category];input.disabled=!active.includes(category)||privacySignal;
   dialog.querySelector('[data-for='+category+']').textContent=active.includes(category)?'':'(not installed)';
  }
  dialog.querySelector('[data-consent-action=accept]').disabled=!active.length||privacySignal;
  dialog.querySelector('[data-privacy-signal]').textContent=privacySignal?'Your browser privacy signal is respected: optional tracking remains off.':!persistence?'Your browser has blocked saving preferences. Your choice will apply only to this page.':'';
  dialog.showModal();
 }
 document.querySelectorAll('[data-cookie-settings]').forEach(button=>button.addEventListener('click',openSettings));
 // No banner until actual optional services are registered.
 if(active.length&&!saved&&!privacySignal){
  banner=document.createElement('section');banner.className='consent-banner';banner.setAttribute('aria-label','Cookie choices');
  banner.innerHTML='<p>We use essential storage to make this website work. Optional cookies stay off unless you allow them. <a href="cookies.html">Cookie policy</a></p><div class="consent-actions"><button type="button" data-choice="accept">Accept all</button><button type="button" data-choice="reject">Reject optional</button><button type="button" data-choice="manage">Manage preferences</button></div>';
  banner.addEventListener('click',event=>{const choice=event.target.closest('[data-choice]')?.dataset.choice;if(choice==='manage')openSettings();if(choice==='accept')save({analytics:true,marketing:true});if(choice==='reject')save({analytics:false,marketing:false});});
  document.body.append(banner);
 }
 window.addEventListener('storage',event=>{if(event.key===key)location.reload();});
 apply();
})();
