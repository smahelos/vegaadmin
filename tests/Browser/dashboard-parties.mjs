import {spawn} from 'node:child_process';
import {writeFile} from 'node:fs/promises';
// Read-only browser regression against the DevelopmentDataSeeder fixtures.
// Run: node tests/Browser/dashboard-parties.mjs
const base=process.env.BILLEX_BROWSER_URL || 'http://billex-dev.local';
const chrome=spawn('/usr/bin/google-chrome',['--headless=new','--no-sandbox','--disable-dev-shm-usage','--no-proxy-server','--remote-debugging-port=9228','--user-data-dir=/tmp/billex-dashboard-parties-chrome','about:blank'],{stdio:'ignore'});
let ws;let id=0;const pending=new Map();
const pause=ms=>new Promise(r=>setTimeout(r,ms));
try {
 let tabs;
 for(let i=0;i<80;i++){try{tabs=await(await fetch('http://127.0.0.1:9228/json')).json();if(tabs.length)break;}catch{} await pause(100);}
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
 const assert=(condition,message)=>{if(!condition)throw Error(message);};
 await go('/cs/login');
 if(await evaluate('!!document.querySelector("[name=email]")')) await evaluate(`document.querySelector('[name=email]').value=${JSON.stringify(process.env.BILLEX_BROWSER_EMAIL || 'user1@billex.test')};document.querySelector('[name=password]').value=${JSON.stringify(process.env.BILLEX_BROWSER_PASSWORD || 'backpack')};document.querySelector('form').requestSubmit();true`);
 await waitFor('!location.pathname.includes("login") && document.readyState === "complete"');
 await go('/cs/dashboard');
 await waitFor('window.dashboardStats?.initialized && Object.keys(window.dashboardStats.charts).length === 5');
 const before = await evaluate(`JSON.stringify(Object.fromEntries(Object.entries(window.dashboardStats.charts).map(([key,chart])=>[key,chart.data])))`);
 assert(await evaluate('document.querySelector("#stats-contact-filter").options.length > 0'),'Missing contact filter options');
 const responses=await evaluate(`(async()=>{const output=[];for(const endpoint of ['monthly-revenue','customer-revenue','invoice-status','payment-methods','revenue-expenses']){const response=await fetch('/api/statistics/'+endpoint,{headers:{'Accept':'application/json'}});const body=await response.json();output.push({endpoint,status:response.status,rows:body.data.length});}return output;})()`);
 assert(responses.every(result=>result.status===200 && result.rows>0),'Chart endpoint failed: '+JSON.stringify(responses));
 assert(await evaluate(`!!document.querySelector('a[href*="/contacts/"]') && !!document.querySelector('a[href*="/businesses/"]')`),'Missing latest party detail links');
 assert(await evaluate(`(()=>{const widgets=[...document.querySelectorAll('*')].filter(element=>element.hasAttribute('wire:snapshot') && JSON.parse(element.getAttribute('wire:snapshot')).memo.name==='party.party-list-latest');return widgets.length===2 && widgets.every(widget=>widget.querySelectorAll('ul > li').length<=5 && !widget.querySelector('nav') && [...widget.querySelectorAll('a')].some(link=>link.textContent.trim()==='Zobrazit všechny'));})()`),'Latest widgets must have at most five rows, no pagination and a View all link');
 for(const width of [1280,430]) for(const theme of ['light','dark']) {
   await send('Emulation.setDeviceMetricsOverride',{width,height:1000,deviceScaleFactor:1,mobile:width<600});
   await evaluate(`document.documentElement.classList.toggle('dark',${theme==='dark'});true`);
   await pause(300);
   assert(await evaluate('document.documentElement.scrollWidth<=innerWidth'),`dashboard ${width} ${theme}: horizontal overflow`);
   assert(await evaluate(`[...document.querySelectorAll('#stats-contact-filter,#stats-timerange,#stats-date-from,#stats-date-to')].every(element=>{const box=element.getBoundingClientRect();return box.left>=0 && box.right<=innerWidth;})`),`dashboard ${width} ${theme}: clipped filter`);
   await writeFile(`/tmp/billex-dashboard-${width}-${theme}.png`,Buffer.from((await send('Page.captureScreenshot',{captureBeyondViewport:true})).data,'base64'));
   console.log('layout',width,theme);
 }
 await evaluate(`const filter=document.querySelector('#stats-contact-filter');filter.options[0].selected=true;filter.dispatchEvent(new Event('change',{bubbles:true}));true`);
 await waitFor('window.dashboardStats.selectedContacts.length === 1');
 await pause(600);
 assert(await evaluate(`JSON.stringify(Object.fromEntries(Object.entries(window.dashboardStats.charts).map(([key,chart])=>[key,chart.data]))) !== ${JSON.stringify(before)}`),'Contact filter did not change chart data');
 assert(exceptions.length===0,'JavaScript exceptions: '+JSON.stringify(exceptions));
 console.log('PASS dashboard charts, contact filtering, party links and four layouts',JSON.stringify(responses));
} finally {ws?.close();chrome.kill();}
