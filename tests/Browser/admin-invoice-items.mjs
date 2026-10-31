import {spawn} from 'node:child_process';
import {writeFile} from 'node:fs/promises';
// Read-only browser regression against the DevelopmentDataSeeder fixtures.
// Run: node tests/Browser/admin-invoice-items.mjs
const base=process.env.BILLEX_BROWSER_URL || 'http://billex-dev.local';
const chrome=spawn('/usr/bin/google-chrome',['--headless=new','--no-sandbox','--disable-dev-shm-usage','--no-proxy-server','--remote-debugging-port=9227','--user-data-dir=/tmp/billex-invoice-items-chrome','about:blank'],{stdio:'ignore'});
let ws;let id=0;const pending=new Map();
const pause=ms=>new Promise(r=>setTimeout(r,ms));
try {
 let tabs;
 for(let i=0;i<80;i++){try{tabs=await(await fetch('http://127.0.0.1:9227/json')).json();if(tabs.length)break;}catch{} await pause(100);}
 ws=new WebSocket(tabs.find(t=>t.type==='page').webSocketDebuggerUrl);
 await new Promise(r=>ws.addEventListener('open',r,{once:true}));
 ws.addEventListener('message',e=>{const m=JSON.parse(e.data);if(m.id){const p=pending.get(m.id);pending.delete(m.id);m.error?p.reject(m.error):p.resolve(m.result);}});
 const send=(method,params={})=>new Promise((resolve,reject)=>{pending.set(++id,{resolve,reject});ws.send(JSON.stringify({id,method,params}));});
 const evaluate=async expression=>{const r=await send('Runtime.evaluate',{expression,awaitPromise:true,returnByValue:true});if(r.exceptionDetails)throw Error(JSON.stringify(r.exceptionDetails));return r.result.value;};
 const waitFor=async expr=>{for(let i=0;i<120;i++){if(await evaluate(expr))return;await pause(100);}throw Error('Timed out: '+expr+'; '+await evaluate('JSON.stringify({status:document.querySelector("[data-items-status]")?.textContent,items:document.querySelector("[name=invoice-products]")?.value,amount:document.querySelector("[name=payment_amount]")?.value})')); };
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

 const ready=()=>waitFor('!!document.querySelector("[data-invoice-items]") && typeof jQuery !== "undefined" && !!jQuery("[name=user_id]").data("select2")');
 const totalsReady=()=>waitFor('!document.querySelector("[data-item-totals]").hidden && !document.querySelector("[data-items-status]").textContent');
 const screenshot=async(label)=>{
   for(const width of [1280,430]) for(const theme of ['light','dark']) {
     await send('Emulation.setDeviceMetricsOverride',{width,height:1000,deviceScaleFactor:1,mobile:width<600});
     await evaluate(`document.documentElement.setAttribute('data-bs-theme','${theme}');document.body.setAttribute('data-bs-theme','${theme}');document.activeElement?.blur();true`);
     await pause(150);
     assert(await evaluate('document.documentElement.scrollWidth <= innerWidth'),`${label} ${width} ${theme}: horizontal overflow`);
     await writeFile(`/tmp/billex-items-${label}-${width}-${theme}.png`,Buffer.from((await send('Page.captureScreenshot',{captureBeyondViewport:true})).data,'base64'));
     console.log('layout',label,width,theme);
   }
 };
 await go('/admin/invoice/create'); await ready();
 const owner=await evaluate('[...document.querySelector("[name=user_id]").options].find(option=>/User 1/.test(option.textContent))?.value');
 assert(owner,'Missing seeded User 1');
 await evaluate(`jQuery('[name=user_id]').val(${JSON.stringify(owner)}).trigger('change');jQuery('[name=payment_currency]').val('CZK').trigger('change');true`);
 await waitFor('document.querySelector("[data-catalog-select]").options.length > 0');
 await evaluate('document.querySelector("[data-add-catalog]").click();true');
 await totalsReady();
 const selected=await evaluate('JSON.parse(document.querySelector("[name=invoice-products]").value)[0]');
 assert(Number(selected.product_id)>0,'Catalog selection must keep its product reference');
 await evaluate(`document.querySelector('[data-key=quantity]').value='1.5';document.querySelector('[data-key=price]').value='0.03';document.querySelector('[data-key=tax_rate]').value=[...document.querySelector('[data-key=tax_rate]').options].find(option=>Number(option.value)===21).value;document.querySelector('[data-key=price]').dispatchEvent(new Event('input',{bubbles:true}));true`);
 await totalsReady();
 assert(await evaluate('document.querySelector("[name=payment_amount]").value === "0.06" && document.querySelector("[name=payment_amount]").readOnly'),'Preview must use server line rounding: 0.05 base + 0.01 VAT');
 const beforeBusiness=await evaluate('document.querySelector("[name=invoice-products]").value');
 const businessIds=await evaluate(`(async()=>{const response=await fetch('/admin/document-parties/businesses?user_id=${owner}');return (await response.json()).data.map(row=>row.id);})()`);
 for(const businessId of businessIds.slice(0,2)) await evaluate(`jQuery('[name=business_id]').append(new Option('Business',${JSON.stringify(String(businessId))},true,true)).trigger('change');true`);
 assert(await evaluate(`document.querySelector('[name=invoice-products]').value === ${JSON.stringify(beforeBusiness)}`),'Changing own business must preserve the shared catalog line');
 await screenshot('invoice-create');
 await evaluate('document.querySelector("[data-add-custom]").click();true');
 await evaluate(`const rows=document.querySelectorAll('[data-item-row]');rows[1].querySelector('[data-key=name]').value='Custom browser item';rows[1].querySelector('[data-key=price]').value='25';rows[1].querySelector('[data-key=price]').dispatchEvent(new Event('input',{bubbles:true}));true`);
 await totalsReady();
 assert(await evaluate('JSON.parse(document.querySelector("[name=invoice-products]").value).length===2'),'Adding custom line must serialize both lines');
 await evaluate('document.querySelector("[data-remove-item]").click();true');
 await totalsReady();
 assert(await evaluate('JSON.parse(document.querySelector("[name=invoice-products]").value)[0].name==="Custom browser item"'),'Removing catalog line must retain the custom item');
 const probe=await evaluate(`(async()=>{const form=document.querySelector('[data-invoice-items]').closest('form');const body=new FormData(form);body.set('invoice_vs','');const response=await fetch(form.action,{method:'POST',body,headers:{Accept:'application/json'}});const data=await response.json();return {status:response.status,errors:data.errors,items:JSON.parse(body.get('invoice-products'))};})()`);
 assert(probe.status===422 && probe.errors.invoice_vs,'The validation probe must not save a development invoice');
 assert(!Object.keys(probe.errors).some(key=>key.startsWith('invoice-products')),'Serialized custom items must pass request validation');
 assert(probe.items.length===1,'POST must carry the current editor contents');
 // Currency changes never relabel the stored prices silently.
 await evaluate(`jQuery('[name=payment_currency]').val('EUR').trigger('change');true`);
 await waitFor('!!document.querySelector("[data-items-status]").textContent && document.querySelector("[data-item-totals]").hidden');
 assert(await evaluate('document.querySelector("[data-key=currency]").value==="CZK"'),'Changing invoice currency must preserve each line currency until explicitly edited');
 await evaluate(`jQuery('[name=user_id]').val(${JSON.stringify(owner)}).trigger('change');true`);
 assert(await evaluate('JSON.parse(document.querySelector("[name=invoice-products]").value).length===0 && !document.querySelector("[name=payment_amount]").readOnly'),'Changing owner must clear the previous owner\'s line references');

 await go('/admin/invoice');await waitFor('document.querySelector("a[href$=\'/edit\']")');
 const paths=await evaluate('[...document.querySelectorAll("a[href$=\'/edit\']")].map(link=>new URL(link.href).pathname)');
 let found=false;
 for(const path of paths) {
   await go(path);await ready();
   const count=await evaluate('document.querySelectorAll("[data-item-row]").length');
   if(!count)continue;
   found=true;await totalsReady();
   const snapshot=await evaluate(`(()=>{const editor=document.querySelector('[data-invoice-items]');return {stored:JSON.parse(editor.dataset.items),rendered:JSON.parse(editor.querySelector('[name=invoice-products]').value)};})()`);
   assert(snapshot.stored.length===snapshot.rendered.length,'Opening edit must preserve all saved rows');
   for(let i=0;i<snapshot.stored.length;i++) for(const key of ['name','price','quantity','currency']) assert(String(snapshot.stored[i][key])===String(snapshot.rendered[i][key]),`Saved ${key} must remain unchanged`);
   await screenshot('invoice-edit');break;
 }
 assert(found,'No seeded invoice with items found');
 for(const entity of ['product']) {
   await go(`/admin/${entity}/create`);
   await waitFor('!!document.querySelector("[name=name]")');
   assert(await evaluate('!document.querySelector("[name=supplier_id]")'),'Product create must have no supplier selector');
   await screenshot(`${entity}-create`);
   await go(`/admin/${entity}`);await waitFor('document.querySelector("a[href$=\'/edit\']")');
   const editPath=await evaluate('new URL(document.querySelector("a[href$=\'/edit\']").href).pathname');
   await go(editPath);await waitFor('!!document.querySelector("[name=name]")');
   assert(await evaluate('!document.querySelector("[name=supplier_id]")'),'Product edit must have no supplier selector');
   await screenshot(`${entity}-edit`);
 }
 assert(exceptions.length===0,'Browser errors: '+exceptions.join(', '));
 console.log('All catalog and invoice item browser checks passed; no records were saved.');
} finally {ws?.close();chrome.kill();}
