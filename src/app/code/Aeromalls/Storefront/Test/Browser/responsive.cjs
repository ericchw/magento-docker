const {chromium}=require(process.env.AEROMALLS_PLAYWRIGHT_MODULE || 'playwright');
const fs=require('fs');
(async()=>{
 const browser=await chromium.launch({headless:true,args:['--no-sandbox','--host-resolver-rules=MAP daiwa-ubuntu-server 127.0.0.1']});
 const context=await browser.newContext({ignoreHTTPSErrors:true});const page=await context.newPage();
 const errors=[];page.on('pageerror',e=>errors.push(e.message));page.on('console',m=>{if(m.type()==='error')errors.push(m.text());});
 const checks=[];
 for(const width of [360,390,768,1024,1440,1920]){
  await page.setViewportSize({width,height:900});const r=await page.goto('http://daiwa-ubuntu-server/aeromalls',{waitUntil:'networkidle',timeout:60000});
  const metrics=await page.evaluate(()=>({title:document.querySelector('h1')?.textContent,bodyWidth:document.documentElement.scrollWidth,viewport:innerWidth,searchCount:document.querySelectorAll('#search').length,css:[...document.styleSheets].map(x=>x.href).filter(Boolean)}));
  checks.push({width,status:r.status(),...metrics});await page.screenshot({path:`docs/aeromalls/home-${width}.png`,fullPage:true});
 }
 await page.setViewportSize({width:390,height:844});await page.goto('http://daiwa-ubuntu-server/aeromalls',{waitUntil:'networkidle'});
 if(await page.locator('#aero-mobile-nav').count()){
  await page.getByRole('button',{name:'Open menu',exact:true}).click();checks.push({mobileMenuOpen:await page.locator('#aero-mobile-nav').evaluate(x=>x.open)});await page.keyboard.press('Escape');checks.push({mobileMenuClosed:await page.locator('#aero-mobile-nav').evaluate(x=>!x.open)});
 }
 const endpoint=await page.evaluate(async()=>{const r=await fetch('/aeromalls_search/suggest/index?q=B000&limit=99');return {status:r.status,cache:r.headers.get('cache-control'),data:await r.json()};});checks.push({endpoint});
 await page.locator('#search').fill('B000');await page.waitForTimeout(900);checks.push({searchExpanded:await page.locator('#search').getAttribute('aria-expanded'),options:await page.getByRole('option').count()});
 const builder=await page.goto('http://daiwa-ubuntu-server/pcbuilder',{waitUntil:'networkidle'});checks.push({builder:builder.status(),builderTitle:await page.locator('h1').textContent()});await page.screenshot({path:'docs/aeromalls/pcbuilder-mobile.png',fullPage:true});
 const categories=await page.goto('http://daiwa-ubuntu-server/aeromalls/categories',{waitUntil:'networkidle'});checks.push({categories:categories.status(),categoryCards:await page.locator('.aero-category-grid>section').count()});
 fs.writeFileSync('docs/aeromalls/browser-checks.json',JSON.stringify({checks,errors},null,2));console.log(JSON.stringify({checks,errors},null,2));await browser.close();
})().catch(e=>{console.error(e);process.exit(1)});
