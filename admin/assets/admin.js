document.querySelectorAll('form[data-confirm]').forEach(form=>form.addEventListener('submit',event=>{if(!confirm(form.dataset.confirm))event.preventDefault();}));
const resetForm=document.getElementById('reset-complete');
function openResetLink(){if(!resetForm)return;const token=new URLSearchParams(location.hash.slice(1)).get('token');if(token&&/^[a-f0-9]{64}$/.test(token)){resetForm.elements.token.value=token;resetForm.hidden=false;document.getElementById('reset-request').hidden=true;history.replaceState(null,'',location.pathname);}}
openResetLink();window.addEventListener('hashchange',openResetLink);
