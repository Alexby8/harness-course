import fs from 'node:fs';
import path from 'node:path';
const output=path.resolve(process.argv[2]||'storage/app/language-course-illustrations');
const data=JSON.parse(fs.readFileSync(path.join(output,'language-course.json'),'utf8'));
const e=s=>String(s).replaceAll('&','&amp;').replaceAll('<','&lt;').replaceAll('>','&gt;').replaceAll('"','&quot;');
const css=`*{box-sizing:border-box}body{margin:0;background:#142033;color:#eef3ff;font:24px/1.42 Arial,sans-serif}.canvas{width:1080px;padding:42px;background:linear-gradient(140deg,#1c2d48,#111c2c)}.eyebrow{font-size:18px;color:#b7b6ff;letter-spacing:1px;margin:0 0 14px}h1{font-size:35px;line-height:1.18;margin:0 0 28px}h2{font-size:25px;margin:0 0 16px}.grid{display:grid;grid-template-columns:1fr 1fr;gap:24px}.box{background:#243850;border:1px solid #43566c;border-radius:16px;padding:24px}.bubble{white-space:pre-line;background:#f1f6fc;color:#172d45;border-radius:12px;padding:18px;margin:12px 0}.error{background:#fff2dc}.button{padding:10px 14px;border:1px solid #6b8aa8;border-radius:9px;color:#c7e7ff;text-align:center;margin-top:10px}.note{margin:25px 0 0;color:#c0cde0;font-size:21px}.rule{background:#dff3e9;color:#184537;padding:20px 24px;border-radius:14px;margin-top:24px}.step{display:flex;gap:20px;align-items:center;background:#243850;border-radius:12px;padding:17px 22px;margin-top:12px}.step b{display:grid;place-items:center;flex:0 0 44px;height:44px;border-radius:50%;background:#3d526c}.step strong{display:block}.step small{display:block;color:#bdcde1;font-size:20px}.done{border-left:6px solid #74d6ab}.active{border-left:6px solid #a0a1ff}.locked{opacity:.85}.mobile .canvas{width:390px;padding:24px 20px}.mobile{font-size:19px}.mobile .eyebrow{font-size:13px}.mobile h1{font-size:27px;margin-bottom:22px}.mobile h2{font-size:21px}.mobile .grid{grid-template-columns:1fr;gap:18px}.mobile .box{padding:17px}.mobile .bubble{padding:15px}.mobile .note{font-size:17px}.mobile .rule{padding:17px;font-size:18px}.mobile .step{gap:12px;padding:14px}.mobile .step b{flex-basis:34px;height:34px}.mobile .step small{font-size:16px}`;
const manifest=[];
for(const mobile of [false,true]){
 const languages=['ru','en'].map(locale=>`<div class="box"><h2>${locale==='ru'?'Русский · ru':'English · en'}</h2><div class="bubble">${e(data[locale].ask_name)}</div><div class="bubble error">${e(data[locale].bad_name)}</div><div class="button">${e(data[locale].language_button)}</div><div class="button">${e(data[locale].help_button)}</div></div>`).join('');
 const variants=[{
  slug:'telegram-bot-multiple-languages',name:'language-dialogue',
  title:'Один диалог — два языка',
  html:`<div class="grid">${languages}</div><div class="rule">Ручной выбор сохраняется в БД.<br>Новые ответы и уведомления используют этот язык.</div><p class="note">Тексты и кнопки взяты из словарей PHP-примера. Это схема состояний, а не снимок Telegram.</p>`,
  alt:'Одни и те же просьба ввести имя, ошибка и кнопки на русском и английском; ручной выбор языка сохраняется.',
  caption:'Поясняющая схема диалога. Подписи взяты из словарей статьи; интерфейс Telegram условно не воспроизводится.'
 },{
  slug:'telegram-bot-mini-course',name:'course-progress',title:'Прогресс хранится между визитами',
  html:Object.entries(data.lessons).map(([id,l])=>`<div class="step ${id==='1'?'done':id==='2'?'active':'locked'}"><b>${id}</b><div><strong>${e(l.title)}</strong><small>${id==='1'?'Пройден · верный ответ':id==='2'?'Открыт · задание ещё не выполнено':'Закрыт · ждёт предыдущий этап'}</small></div></div>`).join('')+`<div class="rule">Верный ответ → пауза 24 часа → следующий урок по /continue</div><div class="box" style="margin-top:24px"><h2>/stop — только напоминания</h2>Пройденные занятия сохраняются.<br>/continue остаётся доступной.</div><p class="note">Пример состояния: completed = 1, active_lesson = 2. Это поясняющая схема, а не снимок Telegram.</p>`,
  alt:'Пять уроков курса: первый пройден, второй открыт, остальные закрыты. Отключение напоминаний сохраняет прогресс.',
  caption:'Поясняющая схема курса из примера: первый урок завершён, второй открыт. Переход требует правильного ответа и паузы в 24 часа.'
 }];
 for(const item of variants){
  const filename=item.name+(mobile?'-mobile':'')+'.html';
  fs.writeFileSync(path.join(output,filename),`<!doctype html><html lang="ru"><meta charset="utf-8"><style>${css}</style><body class="${mobile?'mobile':''}"><main class="canvas"><p class="eyebrow">ПОЯСНЯЮЩАЯ СХЕМА</p><h1>${item.title}</h1>${item.html}</main></body></html>`);
  const {html,title,...metadata}=item;manifest.push({...metadata,filename,mobile,width:mobile?390:1080});
 }
}
fs.writeFileSync(path.join(output,'manifest.json'),JSON.stringify(manifest,null,2)+'\n');
