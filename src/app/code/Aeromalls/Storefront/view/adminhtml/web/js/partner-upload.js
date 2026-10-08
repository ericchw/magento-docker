document.addEventListener('change', async (event) => {
 const input = event.target;
 if (!input.matches('[data-aero-logo-upload]') || !input.files.length) return;
 const status = input.parentElement.querySelector('[data-aero-upload-status]');
 const text = document.getElementById(input.dataset.target);
 input.disabled = true; status.textContent = 'Uploading…';
 try {
  const data = new FormData();data.append('image',input.files[0]);data.append('form_key',window.FORM_KEY || document.querySelector('[name="form_key"]').value);
  const response = await fetch(input.dataset.url,{method:'POST',body:data,credentials:'same-origin'});const result=await response.json();
  if(!response.ok || !result.path)throw new Error(result.error || 'Upload failed');
  text.value=result.path;status.textContent='Uploaded. Save configuration to publish.';
 }catch(error){status.textContent=error.message;}finally{input.disabled=false;input.value='';}
});
