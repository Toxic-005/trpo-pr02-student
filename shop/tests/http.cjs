// Node.js 18+; PHP_BIN указывает на php.exe, если PHP не добавлен в PATH.
const { spawn } = require('node:child_process');
const fs = require('node:fs');
const path = require('node:path');
const assert = require('node:assert/strict');
const root = path.resolve(__dirname, '..');
const php = process.env.PHP_BIN || 'php';
const servers = [], checks = [], logs = [];
const storageRoot = path.join(root, 'storage');
const storageDirs = [];
function check(ok, label) { assert.ok(ok, label); checks.push(label); console.log('PASS: ' + label); }
function csv(relative) { return fs.readFileSync(path.join(root,relative),'utf8').replace(/^\uFEFF/,'').trim().split(/\r?\n/).slice(1).map(line=>line.split(';')); }
async function start(mode, port) {
  const storage = path.join(storageRoot, 'http-' + mode + '-' + Date.now()); storageDirs.push(storage);
  const child = spawn(php, ['-S',`127.0.0.1:${port}`,'-t',path.join(root,'public'),path.join(root,'public/router.php')],
    {windowsHide:true,env:{...process.env,APP_ENV:'development',SHOP_CATALOG:mode === 'cart' ? 'cart' : '',SHOP_STORAGE:storage}});
  servers.push(child); child.stdout.on('data',d=>logs.push(d.toString())); child.stderr.on('data',d=>logs.push(d.toString()));
  child.on('error',error=>logs.push(error.message));
  const context = {base:`http://127.0.0.1:${port}`,cookie:'',storage};
  for(let i=0;i<80;i++){ try { await get(context,'/'); return context; } catch {} await new Promise(r=>setTimeout(r,100)); }
  throw Error('PHP server did not start; check PHP_BIN and port');
}
async function request(context, route, method='GET', form) {
  const response = await fetch(context.base+route,{method,redirect:'manual',headers:{...(context.cookie?{Cookie:context.cookie}:{}),...(form?{'Content-Type':'application/x-www-form-urlencoded'}:{})},body:form?new URLSearchParams(form):undefined});
  const cookie=response.headers.get('set-cookie'); if(cookie) context.cookie=cookie.split(';')[0];
  const body=await response.text();
  assert.ok(!/Warning:|Fatal error:|Notice:|Deprecated:/.test(body),'No raw PHP diagnostics');
  return {status:response.status,body,headers:response.headers};
}
const get=(context,route)=>request(context,route);
const post=(context,route,form)=>request(context,route,'POST',form);
async function token(context){const page=await get(context,'/catalog');const match=page.body.match(/name="csrf" value="([a-f0-9]+)"/);assert.ok(match,'CSRF token in catalog');return match[1];}
async function add(context,id,qty=1){return post(context,'/cart/add',{csrf:await token(context),product_id:String(id),qty:String(qty)});}
function snapshotCount(body){return Number(body.match(/data-cart-count>(\d+)/)?.[1]||0);}

(async()=>{
 try {
  const main=await start('main',8071);
  for(const [method,route] of csv('data/pr07/routes.csv')) {
    const r=await request(main,route,method); check(r.status===(route==='/missing'?404:200),'pr07 route '+route);
  }
  let r=await get(main,'/catalog'); check((r.body.match(/<article /g)||[]).length===24,'24 карточки в основном каталоге');
  for(const [label,category,sort,min] of csv('data/pr08/catalog_cases.csv')) {
    r=await get(main,'/catalog?'+new URLSearchParams({category,sort})); const count=(r.body.match(/<article /g)||[]).length;
    check(r.status===200 && (label==='unknown'?count===0:count>=Number(min)),'catalog_cases '+label);
  }
  check((await get(main,'/product/1')).status===200,'Карточка товара');
  check((await get(main,'/product/nope')).status===404,'Нечисловой id — 404');
  check((await get(main,'/catalog?category[]=1')).status===400,'Массив вместо категории — 400');
  check((await get(main,'/catalog?sort=unknown')).status===400,'Неверная сортировка — 400');
  check((await get(main,'/data/pr08/products.json')).status===404,'Dataset недоступен из public');
  check((await add(main,11)).status===422,'Нет в наличии — 422');
  check((await add(main,1,4)).status===422,'Нельзя превысить остаток');
  const context=await start('cart',8072);
  for(const [method,route,expected] of csv('data/pr11/route_tests.csv')) {
    const r=method==='POST'?await add(context,1):await request(context,route,method);
    check(r.status===Number(expected),'route_tests '+method+' '+route);
  }
  await post(context,'/cart/remove',{csrf:await token(context),product_id:'1'});
  for(const [index,scenario] of JSON.parse(fs.readFileSync(path.join(root,'data/pr09/cart_scenarios.json'),'utf8')).entries()) {
    const jar={...context,cookie:''};
    for(const step of scenario.steps){const [action,id,qty='1']=step.split(':');const r=await post(jar,'/cart/'+action,{csrf:await token(jar),product_id:id,qty});assert.equal(r.status,302);}
    const page=await get(jar,'/cart');
    const totalUnits=Object.values(scenario.expected).reduce((a,b)=>a+b,0);
    check(snapshotCount(page.body)===totalUnits,'HTTP cart_scenarios '+(index+1));
  }
  r=await add(context,1); check(r.status===302 && r.headers.get('location')==='/cart','POST корзины → 302');
  r=await get(context,'/cart'); check(r.body.includes('Товар добавлен в корзину.'),'Flash показан');
  check(!(await get(context,'/cart')).body.includes('Товар добавлен в корзину.'),'Flash исчез после одного GET');
  const before=snapshotCount((await get(context,'/cart')).body);
  check((await get(context,'/cart/add?product_id=2')).status===405,'GET add запрещён');
  check(snapshotCount((await get(context,'/cart')).body)===before,'GET не изменил session');
  check(snapshotCount((await get({...context,cookie:''},'/cart')).body)===0,'Независимая сессия пуста');
  check((await post(context,'/cart/add',{csrf:'bad',product_id:'1',qty:'1'})).status===403,'Неверный CSRF — 403');
  check((await add(context,1,-1)).status===422,'Отрицательное количество — 422');
  check((await add(context,99999)).status===404,'Неизвестный товар — 404');
  check((await post(context,'/cart/add',{csrf:await token(context),'product_id[]':'1',qty:'1'})).status===400,'Массив вместо id — 400');
  for(const item of JSON.parse(fs.readFileSync(path.join(root,'data/pr11/security_cases.json'),'utf8'))) {
    if(item.expected==='rejected')check((await add(context,1,item.qty)).status===422,'security_cases HTTP rejected');
    if(item.expected==='not_found')check((await add(context,item.product_id)).status===404,'security_cases HTTP not_found');
    if(item.expected==='escaped'){
      r=await post(context,'/checkout',{csrf:await token(context),name:item.input,email:'bad',delivery:'pickup'});
      check(r.status===422 && r.body.includes('&lt;script&gt;alert(1)&lt;/script&gt;') && !r.body.includes('<script>'),'security_cases HTML escaped');
    }
  }
  for(const item of JSON.parse(fs.readFileSync(path.join(root,'data/pr10/order_cases.json'),'utf8'))){
    const jar={...context,cookie:''}; await add(jar,1);
    r=await post(jar,'/checkout',{csrf:await token(jar),...item.input});
    check(r.status===(item.valid?302:422),'order_cases HTTP '+item.case);
  }
  r=await post(context,'/checkout',{csrf:await token(context),name:'Иван',email:'ivan@example.test',delivery:'unknown'});
  check(r.status===422 && r.body.includes('Выберите доступный способ доставки.'),'Неизвестный способ доставки');
  r=await post(context,'/checkout',{csrf:await token(context),name:'Иван',email:'ivan@example.test',delivery:'courier'});
  check(r.status===422 && r.body.includes('Для курьера укажите адрес'),'Курьер требует адрес');
  r=await post(context,'/checkout',{csrf:await token(context),name:'Иван',email:'ivan@example.test',delivery:'courier',address:'Учебная улица, 1',total:'1',price:'1'});
  check(r.status===302,'Корректный заказ → redirect'); const location=r.headers.get('location');
  const saved=JSON.parse(fs.readFileSync(path.join(context.storage,'orders.json'),'utf8'));
  check(saved.at(-1).total_cents===135000,'Подмена цены игнорируется; 900 + 450 ₽');
  r=await get(context,location); check(r.status===200 && r.body.includes('Курьер') && !r.body.includes('ivan@example.test') && !r.body.includes('Учебная улица'),'Подтверждение без лишних персональных данных');
  check((await get({...context,cookie:''},location)).status===404,'Чужая сессия не видит подтверждение');
  await get(context,location); check(JSON.parse(fs.readFileSync(path.join(context.storage,'orders.json'),'utf8')).length===saved.length,'Обновление не дублирует заказ');
  check(snapshotCount((await get(context,'/cart')).body)===0,'Корзина очищена после сохранения');
  check((await post(context,'/checkout',{csrf:await token(context),name:'Иван',email:'ivan@example.test',delivery:'pickup'})).status===409,'Повторный POST с пустой корзиной отклонён');
  await add(context,1); const original=fs.readFileSync(path.join(context.storage,'orders.json'));
  fs.writeFileSync(path.join(context.storage,'orders.json'),'{broken');
  r=await post(context,'/checkout',{csrf:await token(context),name:'Иван',email:'ivan@example.test',delivery:'pickup'});
  check(r.status===500 && snapshotCount((await get(context,'/cart')).body)===1,'Ошибка хранилища не очищает корзину');
  check(fs.readFileSync(path.join(context.storage,'orders.json'),'utf8')==='{broken','Повреждённые данные не перезаписаны');
  fs.writeFileSync(path.join(context.storage,'orders.json'),original);
  const source=path.join(root,'data/pr08/products.json'), backup=fs.readFileSync(source);
  try { fs.writeFileSync(source,'{broken'); r=await get(main,'/catalog'); check(r.status===500 && !r.body.includes('Fatal error'),'Ошибка bootstrap перехвачена'); }
  finally { fs.writeFileSync(source,backup); }
  r=await get(main,'/'); check(r.headers.get('content-security-policy').includes("script-src 'none'"),'Защитные заголовки');
  check(!/PHP (Warning|Notice|Deprecated|Fatal error)/.test(logs.join('')),'Нет PHP warnings/notices/deprecated/fatal');
  console.log('HTTP checks: '+checks.length);
 } finally {
  for(const server of servers)server.kill();
  await new Promise(r=>setTimeout(r,150));
  for(const directory of storageDirs){if(!path.resolve(directory).startsWith(storageRoot+path.sep))throw Error('Unsafe cleanup path');fs.rmSync(directory,{recursive:true,force:true});}
 }
})().catch(error=>{console.error(error);process.exitCode=1;});
