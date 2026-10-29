import {spawn} from 'node:child_process';
import {writeFile} from 'node:fs/promises';
// Read-only browser regression against the DevelopmentDataSeeder fixtures.
// Run: node tests/Browser/admin-document-forms.mjs
const base=process.env.BILLEX_BROWSER_URL || 'http://billex-dev.local';
const chrome=spawn('/usr/bin/google-chrome',['--headless=new','--no-sandbox','--disable-dev-shm-usage','--no-proxy-server','--remote-debugging-port=9226','--user-data-dir=/tmp/billex-party-logo-chrome','about:blank'],{stdio:'ignore'});
let ws;let id=0;const pending=new Map();
const pause=ms=>new Promise(r=>setTimeout(r,ms));
try {
 let tabs;
 for(let i=0;i<80;i++){try{tabs=await(await fetch('http://127.0.0.1:9226/json')).json();if(tabs.length)break;}catch{} await pause(100);}
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
 const screenshot=async(label,frontend=false)=>{
   for(const width of [1280,430]) for(const theme of ['light','dark']) {
     await send('Emulation.setDeviceMetricsOverride',{width,height:1000,deviceScaleFactor:1,mobile:width<600});
     await evaluate(`document.documentElement.setAttribute('data-bs-theme','${theme}');document.body.setAttribute('data-bs-theme','${theme}');document.documentElement.classList.toggle('dark',${frontend && theme==='dark'});document.activeElement?.blur();true`);
     await pause(150);
     assert(await evaluate('document.documentElement.scrollWidth<=innerWidth'),`${label} ${width} ${theme}: horizontal overflow`);
     await writeFile(`/tmp/billex-logo-${label}-${width}-${theme}.png`,Buffer.from((await send('Page.captureScreenshot',{captureBeyondViewport:true})).data,'base64'));
   }
 };
 for(const entity of ['business','contact']) {
   await go(`/admin/${entity}/create`);
   await waitFor('!!document.querySelector("input[type=file][name=logo]")');
   assert(await evaluate('document.querySelector("input[name=logo]").form.enctype === "multipart/form-data"'),`${entity}: multipart upload is missing`);
   await screenshot(`admin-${entity}`);
 }
 await go('/en/login');
 if(await evaluate('!!document.querySelector("[name=email]")')) {
   await evaluate(`document.querySelector('[name=email]').value=${JSON.stringify(process.env.BILLEX_FRONTEND_EMAIL || 'user1@billex.test')};document.querySelector('[name=password]').value=${JSON.stringify(process.env.BILLEX_FRONTEND_PASSWORD || 'backpack')};document.querySelector('form').requestSubmit();true`);
   await waitFor('!location.pathname.includes("login") && document.readyState === "complete"');
 }
 for(const entity of ['businesses','contacts']) {
   await go(`/en/${entity}/create`);
   await waitFor('!!document.querySelector("input[type=file][id=logo]") && typeof Livewire !== "undefined"');
   await evaluate(`(async()=>{
     const canvas=document.createElement('canvas');canvas.width=240;canvas.height=120;
     const context=canvas.getContext('2d');context.fillStyle='#490BF4';context.fillRect(0,0,240,120);context.fillStyle='white';context.font='bold 30px sans-serif';context.fillText('LOGO',70,70);
     const blob=await new Promise(resolve=>canvas.toBlob(resolve,'image/png'));
     const transfer=new DataTransfer();transfer.items.add(new File([blob],'logo-preview.png',{type:'image/png'}));
     const input=document.querySelector('#logo');input.files=transfer.files;input.dispatchEvent(new Event('change',{bubbles:true}));
   })()`);
   await waitFor('[...document.querySelectorAll("form img")].some(img=>img.src.includes("preview-file") && img.complete && img.naturalWidth===240)');
   await screenshot(`frontend-${entity}`,true);
   console.log('shared logo upload and preview passed',entity);
 }
 await go('/en/invoice/create');
 await waitFor('!!document.querySelector("[name=business_id]")');
 assert(await evaluate('!document.querySelector("[name=invoice_logo]")'),'Authenticated invoice must use the business logo');
 await screenshot('frontend-invoice',true);
 const invoiceUrl=await evaluate(`(async()=>{const response=await fetch('/en/invoice');const html=await response.text();const doc=new DOMParser().parseFromString(html,'text/html');return [...doc.querySelectorAll('a[href]')].map(a=>new URL(a.href,location.origin).pathname).find(path=>path.startsWith('/en/invoice/') && path.endsWith('/edit') && Number.isInteger(Number(path.split('/')[3])));})()`);
 if(invoiceUrl) {
   await go(invoiceUrl);await waitFor('!!document.querySelector("[name=business_id]")');
   assert(await evaluate('!document.querySelector("[name=invoice_logo]")'),'Invoice edit must use its stored supplier logo');
   await screenshot('frontend-invoice-edit',true);
 }
 console.log('Logo form browser checks passed; only temporary preview uploads were created, no records were saved.');
} finally {ws?.close();chrome.kill();}
