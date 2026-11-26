const toggle=document.getElementById('menuToggle'),nav=document.getElementById('mainNav');toggle?.addEventListener('click',()=>{const open=nav.classList.toggle('open');toggle.setAttribute('aria-expanded',String(open));});nav?.querySelectorAll('a').forEach(a=>a.addEventListener('click',()=>{nav.classList.remove('open');toggle.setAttribute('aria-expanded','false');}));document.getElementById('year').textContent=new Date().getFullYear();
// ALTO default: hide on downward scroll; reveal when scrolling up or navigating.
const header=document.querySelector('.site-header');let lastY=window.scrollY;let scrollQueued=false;
function updateHeader(){const y=window.scrollY;const delta=y-lastY;if(y<80||nav?.classList.contains('open')||header?.contains(document.activeElement)){header?.classList.remove('header-hidden');lastY=y;}else if(Math.abs(delta)>5){header?.classList.toggle('header-hidden',delta>0);lastY=y;}scrollQueued=false;}
window.addEventListener('scroll',()=>{if(!scrollQueued){scrollQueued=true;requestAnimationFrame(updateHeader)}},{passive:true});
header?.addEventListener('focusin',()=>header.classList.remove('header-hidden'));
