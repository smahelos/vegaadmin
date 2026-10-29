import {spawn} from 'node:child_process';
import {writeFile} from 'node:fs/promises';
// Read-only browser regression against the DevelopmentDataSeeder fixtures.
// Run: node tests/Browser/admin-document-forms.mjs
const base=process.env.BILLEX_BROWSER_URL || 'http://billex-dev.local';
const chrome=spawn('/usr/bin/google-chrome',['--headless=new','--no-sandbox','--disable-dev-shm-usage','--no-proxy-server','--remote-debugging-port=9225','--user-data-dir=/tmp/billex-document-forms-chrome','about:blank'],{stdio:'ignore'});
let ws;let id=0;const pending=new Map();
const pause=ms=>new Promise(r=>setTimeout(r,ms));
try {
 let tabs;
 for(let i=0;i<80;i++){try{tabs=await(await fetch('http://127.0.0.1:9225/json')).json();if(tabs.length)break;}catch{} await pause(100);}
 ws=new WebSocket(tabs.find(t=>t.type==='page').webSocketDebuggerUrl);
 await new Promise(r=>ws.addEventListener('open',r,{once:true}));
 ws.addEventListener('message',e=>{const m=JSON.parse(e.data);if(m.id){const p=pending.get(m.id);pending.delete(m.id);m.error?p.reject(m.error):p.resolve(m.result);}});
 const send=(method,params={})=>new Promise((resolve,reject)=>{pending.set(++id,{resolve,reject});ws.send(JSON.stringify({id,method,params}));});
 const evaluate=async expression=>{const r=await send('Runtime.evaluate',{expression,awaitPromise:true,returnByValue:true});if(r.exceptionDetails)throw Error(JSON.stringify(r.exceptionDetails));return r.result.value;};
 const waitFor=async expr=>{for(let i=0;i<120;i++){if(await evaluate(expr))return;await pause(100);}throw Error('Timed out: '+expr);};
 const go=async path=>{await send('Page.navigate',{url:base+path});await waitFor('document.readyState === "complete"');await pause(300);};

 await send('Page.enable');
 await send('Runtime.enable');
 const exceptions=[];
 ws.addEventListener('message',event=>{
   const message=JSON.parse(event.data);
   if(message.method==='Runtime.exceptionThrown') {const detail=message.params.exceptionDetails;exceptions.push(detail.exception?.description || detail.text);console.log('browser exception',JSON.stringify(detail));}
 });
 await go('/admin/login');
 if(await evaluate('!!document.querySelector("[name=email]")')) await evaluate(`document.querySelector('[name=email]').value=${JSON.stringify(process.env.BILLEX_BROWSER_EMAIL || 'admin@billex.test')};document.querySelector('[name=password]').value=${JSON.stringify(process.env.BILLEX_BROWSER_PASSWORD || 'backpack')};document.querySelector('form').requestSubmit();true`);
 await waitFor('!location.pathname.includes("login") && document.readyState === "complete"');
 const assert=(condition,message)=>{if(!condition)throw Error(message);};
 const ready=()=>waitFor('typeof jQuery !== "undefined" && !!jQuery("[name=contact_id]").data("select2") && !!document.querySelector("[data-document-contact-status]")?.textContent');
 const select=async(name,record)=>{
   // Trigger the actual Select2 result: the profile must survive processResults.
   await evaluate(`jQuery('[name=${name}]').select2('open');jQuery('.select2-search__field').last().val(${JSON.stringify(record.name || record.label)}).trigger('input');true`);
   // Backpack focuses the search after 100 ms; let that timer run before closing it.
   await pause(120);
   await waitFor(`jQuery('.select2-results__option[aria-selected]').filter(function(){return String(jQuery.fn.select2.amd.require('select2/utils').GetData(this,'data')?.id)===${JSON.stringify(String(record.id))}}).length > 0`);
   await evaluate(`jQuery('.select2-results__option[aria-selected]').filter(function(){return String(jQuery.fn.select2.amd.require('select2/utils').GetData(this,'data')?.id)===${JSON.stringify(String(record.id))}}).first().trigger('mouseup');true`);
   await waitFor(`String(jQuery('[name=${name}]').val())===${JSON.stringify(String(record.id))}`);
 };
 const compareProfile=async(role,profile)=>{
   const result=await evaluate(`Object.fromEntries([...document.querySelectorAll('[name^="${role}_"]')].map(e=>[e.name.slice(${role.length+1}),{value:e.value,disabled:e.disabled}]))`);
   for(const [key,value] of Object.entries(profile)) {
     if(key === 'logo_url') continue;
     assert(result[key]?.value===String(value ?? ''),`${role}.${key}: expected ${value}, got ${JSON.stringify(result[key])}`);
     assert(result[key]?.disabled,`${role}.${key} must be locked for a newly selected contact`);
   }
 };
 const screenshot=async(label)=>{
   for(const width of [1280,430]) for(const theme of ['light','dark']) {
     await send('Emulation.setDeviceMetricsOverride',{width,height:1000,deviceScaleFactor:1,mobile:width<600});
     await evaluate(`document.documentElement.setAttribute('data-bs-theme','${theme}');document.body.setAttribute('data-bs-theme','${theme}');document.activeElement?.blur();true`);
     await pause(150);
     const layout=await evaluate(`(()=>{
       const rect=name=>{const r=document.querySelector('[name='+name+']').closest('[bp-field-wrapper]').getBoundingClientRect();return {x:r.x,y:r.y,width:r.width};};
       return {overflow:document.documentElement.scrollWidth>innerWidth,owner:rect('user_id'),business:rect('business_id'),contact:rect('contact_id'),account:rect('bank_account_id')};
     })()`);
     assert(!layout.overflow,`${label} ${width} ${theme}: horizontal overflow`);
     if(width>600) {
       assert(Math.abs(layout.owner.y-layout.business.y)<2,`${label}: owner and business are not on the same row`);
       assert(Math.abs(layout.contact.y-layout.account.y)<2,`${label}: contact and account are not on the same row`);
     } else {
       assert(layout.business.y>layout.owner.y,`${label}: owner and business do not stack`);
       assert(layout.account.y>layout.contact.y,`${label}: contact and account do not stack`);
     }
     await writeFile(`/tmp/billex-document-${label}-${width}-${theme}.png`,Buffer.from((await send('Page.captureScreenshot',{captureBeyondViewport:true})).data,'base64'));
     console.log('layout',label,width,theme,layout);
   }
 };
 let fixtures;
 for(const [documentType,role] of [['invoice','customer'],['expense','supplier']]) {
   console.log('Checking',documentType);await go(`/admin/${documentType}/create`);await ready();
   if(!fixtures) {
     fixtures=await evaluate(`(async()=>{
       const owner=[...document.querySelector('[name=user_id]').options].find(o=>/User 1/.test(o.textContent));
       if(!owner)throw Error('Seed the development fixtures first');
       const businesses=await(await fetch('/admin/document-parties/businesses?user_id='+owner.value)).json();
       const contacts=await(await fetch('/admin/document-parties/contacts?user_id='+owner.value)).json();
       return {owner:owner.value,businesses:businesses.data,contacts:contacts.data};
     })()`);
   }
   await evaluate(`jQuery('[name=user_id]').val(${JSON.stringify(fixtures.owner)}).trigger('change');true`);
   await select('business_id',fixtures.businesses[0]);
   assert(await evaluate('!document.querySelector("[name=invoice_logo]")'), 'Admin invoice must not offer its own logo upload');
   await select('contact_id',fixtures.contacts[0]);
   await compareProfile(role,fixtures.contacts[0].profile);
   if(documentType==='expense') assert(await evaluate('document.querySelector("[name=supplier_logo]").disabled'), 'A contact-backed expense must use the contact logo');
   await select('contact_id',fixtures.contacts[1]);
   await compareProfile(role,fixtures.contacts[1].profile);
   // A programmatic selection (e.g. a validation redirect) must also fetch the complete profile by ID.
   const last=fixtures.contacts.at(-1);
   await evaluate(`jQuery('[name=contact_id]').append(new Option(${JSON.stringify(last.name)},${JSON.stringify(String(last.id))},true,true)).trigger('change');true`);
   await waitFor(`document.querySelector('[name=${role}_name]').value===${JSON.stringify(last.name)}`);
   await compareProfile(role,last.profile);
   if(documentType==='invoice') {
     await waitFor('!!jQuery("[name=bank_account_id]").val() && !!jQuery("[name=account_number]").val()');
     assert(await evaluate('document.querySelector("[name=bank_account_id]").required'),'Invoice account must be required for transfer');
     await evaluate(`jQuery('[name=payment_method_id]').val(jQuery('[name=payment_method_id] option').filter(function(){return this.value && this.value!==String(jQuery('[name=bank_account_id]').data('bank-transfer-method'))}).first().val()).trigger('change');true`);
     assert(await evaluate('!jQuery("[name=bank_account_id]").prop("required") && !jQuery("[data-document-section=account]").is(":visible")'),'Cash must hide bank details');
     await evaluate(`jQuery('[name=payment_method_id]').val(String(jQuery('[name=bank_account_id]').data('bank-transfer-method'))).trigger('change');true`);
   } else {
     const uploadCheck=await evaluate(`(async()=>{
       const form=document.querySelector('main form');
       const data=new FormData(form);
       // A missing amount guarantees validation failure without creating a document.
       data.set('amount','');
       const response=await fetch(form.action,{method:'POST',body:data,headers:{Accept:'application/json'}});
       const result=await response.json();
       return {enctype:form.enctype,required:document.querySelector('[name="attachments[]"]').required,status:response.status,errors:result.errors};
     })()`);
     assert(uploadCheck.enctype==='multipart/form-data','Expense form must use multipart encoding');
     assert(!uploadCheck.required,'Expense attachment must be optional');
     assert(uploadCheck.status===422 && !!uploadCheck.errors.amount,'Browser validation probe must not save a document');
     assert(!Object.keys(uploadCheck.errors).some(name=>name.startsWith('attachments')),'An empty upload must not fail attachment validation');
     await evaluate(`jQuery('[name=contact_id]').val(null).trigger('change');jQuery('[name=account_number]').val('2001234561');true`);
     assert(await evaluate('!document.querySelector("[name=supplier_logo]").disabled'), 'A one-off expense must allow its supplier logo upload');
     assert(await evaluate('!document.querySelector("[name=supplier_name]").disabled && !document.querySelector("[name=account_number]").disabled'),'One-off supplier/account must remain editable');
     const accountFixture=await evaluate(`(async()=>{
       for(const contact of ${JSON.stringify(fixtures.contacts)}) {
         const response=await(await fetch('/admin/document-parties/accounts?user_id=${fixtures.owner}&contact_id='+contact.id)).json();
         if(response.data.length)return {contact,account:response.data[0]};
       }
       throw Error('Missing demo supplier account');
     })()`);
     await select('contact_id',accountFixture.contact);
     await select('bank_account_id',accountFixture.account);
     assert(await evaluate('document.querySelector("[name=account_number]").disabled'),'Selected expense account must be locked');
     assert(await evaluate(`document.querySelector('[name=account_number]').value===${JSON.stringify(accountFixture.account.account.number)}`),'Selected expense account must show actual details');
     await evaluate('jQuery("[name=bank_account_id]").val(null).trigger("change");true');
     assert(await evaluate('document.querySelector("[name=account_number]").value==="2001234561" && !document.querySelector("[name=account_number]").disabled'),'Clearing selected account must restore one-off input');
     await select('bank_account_id',accountFixture.account);
   }
   await screenshot(`${documentType}-create`);
   await go(`/admin/${documentType}`);
   await waitFor('document.querySelector("a[href$=\'/edit\']")');
   const editPath=await evaluate('new URL(document.querySelector("a[href$=\'/edit\']").href).pathname');
   await go(editPath);await ready();
   let stored=await evaluate(`({contact:jQuery('[name=contact_id]').val(),owner:jQuery('[name=user_id]').val(),name:document.querySelector('[name=${role}_name]').value})`);
   // Find an existing document with a contact; never write to the development DB.
   if(!stored.contact) {
     await go(`/admin/${documentType}`);await waitFor('document.querySelector("a[href$=\'/edit\']")');
     const paths=await evaluate('[...document.querySelectorAll("a[href$=\'/edit\']")].map(a=>new URL(a.href).pathname)');
     for(const path of paths) {
       await go(path);await ready();stored=await evaluate(`({contact:jQuery('[name=contact_id]').val(),owner:jQuery('[name=user_id]').val(),name:document.querySelector('[name=${role}_name]').value})`);
       if(stored.contact)break;
     }
   }
   assert(stored.contact,`No seeded ${documentType} with a contact`);
   await pause(700);
   assert(await evaluate(`document.querySelector('[name=${role}_name]').value===${JSON.stringify(stored.name)} && !document.querySelector('[name=${role}_name]').disabled`),'Opening edit must preserve the stored, editable snapshot');
   await evaluate(`jQuery('[name=${role}_name]').val('Correction on this document only');true`);
   const alternatives=await evaluate(`(async()=>{const r=await(await fetch('/admin/document-parties/contacts?user_id=${stored.owner}')).json();return r.data;})()`);
   const different=alternatives.find(c=>String(c.id)!==String(stored.contact));
   await select('contact_id',different);await compareProfile(role,different.profile);
   await evaluate(`jQuery('[name=contact_id]').append(new Option('Stored contact',${JSON.stringify(String(stored.contact))},true,true)).trigger('change');true`);
   assert(await evaluate(`document.querySelector('[name=${role}_name]').value==='Correction on this document only' && !document.querySelector('[name=${role}_name]').disabled`),'Returning to stored contact must restore the document correction');
   await screenshot(`${documentType}-edit`);
   console.log('profile and snapshot regression passed',documentType);
 }
 assert(exceptions.length===0,'Browser JavaScript errors: '+exceptions.join(', '));
 console.log('All document form browser checks passed; no documents were saved.');
} finally {ws?.close();chrome.kill();}
