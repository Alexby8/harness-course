import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

// Authoring only. Capture with capture-scenario-illustrations.mjs; no API calls.
const here = path.dirname(fileURLToPath(import.meta.url));
const output = path.resolve(process.argv[2] || 'storage/app/knowledge-calendar-drafts');
fs.mkdirSync(output, { recursive: true });
const slugs = ['telegram-bot-company-knowledge-base', 'telegram-bot-google-calendar', 'telegram-mini-app-form-draft'];
const articles = slugs.map(slug => {
  const file = path.join(here, '../packages', slug + '.json');
  return JSON.parse(fs.readFileSync(fs.existsSync(file) ? file : path.join(here, '../current.json'), 'utf8')).find(a => a.slug === slug);
});
const form = articles[2].sections.flatMap(s => s.content.content).find(n => n.attrs?.config?.label === 'HTML и JavaScript · public/form.html')?.attrs.config.code;
if (!form) throw new Error('Article form source missing');
const sample = 'Нужно перенести клиентскую базу и сохранить историю обращений. Как подготовить данные?';
// Same markup as the article, demo state only. External scripts are removed.
const restored = form.replace(/<script\b[^>]*>[\s\S]*?<\/script>/g, '')
  .replace(/<small>[\s\S]*?<\/small>/g, '')
  .replace('Загружаем черновик…', 'Черновик восстановлен')
  .replace(/ disabled/g, '').replace('</textarea>', sample + '</textarea>');
fs.writeFileSync(path.join(output, 'draft-restored.html'), restored);
const css = `*{box-sizing:border-box}body{margin:0;font:30px/1.4 Arial,sans-serif;color:#edf3fc;background:#111b2c}
.canvas{width:1080px;padding:40px;background:linear-gradient(135deg,#192a45,#111b2c)}
.tag{color:#bfb0f5;font-size:19px;letter-spacing:1px;text-transform:uppercase;margin-bottom:16px}
h1{font-size:40px;line-height:1.2;margin:0 0 28px}h2{font-size:28px;margin:0 0 16px}
.cols{display:grid;grid-template-columns:1fr 1fr;gap:28px}.box{padding:24px;background:#f4f7fc;color:#203149;border-radius:16px;overflow-wrap:anywhere}
.box+.box{margin-top:18px}.label{font-size:22px;color:#526984;font-weight:bold;margin-bottom:10px}.good{background:#dff3e9}.warn{background:#fff0d6}
.note{color:#bfcfe4;font-size:25px;margin:20px 0 0}.id{font:25px/1.5 Consolas,monospace;overflow-wrap:anywhere}.arrow{color:#abd4fa;margin:12px 0;font-weight:bold}
.frame{border-radius:16px;overflow:hidden;height:732px;background:#f4f7fb}.frame iframe{border:0;display:block;width:390px;height:610px;transform:scale(1.2);transform-origin:top left;pointer-events:none}
.status{font:20px/1.5 system-ui;padding:12px;background:#e4edf8;border-radius:10px;color:#17212b}.new{font:20px/1.5 system-ui;background:#2869b0;color:#fff;text-align:center;border-radius:10px;padding:12px;margin-top:12px}
.mobile{width:390px;padding:22px;font-size:21px}.mobile .tag{font-size:13px}.mobile h1{font-size:27px;margin-bottom:22px}.mobile h2{font-size:22px;margin-bottom:12px}.mobile .cols{grid-template-columns:1fr;gap:22px}.mobile .box{padding:18px;border-radius:12px}.mobile .label{font-size:17px}.mobile .note{font-size:18px}.mobile .id{font-size:18px}.mobile .frame{height:610px}.mobile .frame iframe{width:346px;transform:none}.mobile .status,.mobile .new{font-size:18px}`;
const box = (label, body, kind='') => `<div class="box ${kind}"><div class="label">${label}</div>${body}</div>`;
const manifest = [];
function illustration(index, name, section, title, body, caption, alt, tag='Поясняющая схема') {
  if (!articles[index].sections.some(s => s.id === section)) throw new Error('Missing section: '+section);
  for (const mobile of [false,true]) {
    const filename = `${name}-${mobile?'mobile':'desktop'}.html`;
    fs.writeFileSync(path.join(output,filename),`<!doctype html><html lang="ru"><meta charset="utf-8"><title>${title}</title><style>${css}</style><main class="canvas ${mobile?'mobile':''}"><div class="tag">${tag}</div><h1>${title}</h1>${body}</main></html>`);
    manifest.push({slug:slugs[index],name,section,mobile,filename,width:mobile?390:1080,caption,alt});
  }
}
illustration(0,'answer-or-operator','search','Есть источник — отвечаем. Нет ответа — передаём человеку.',
  `<div class="cols"><section><h2>Вопрос об экспорте CSV</h2>${box('Найден документ','Инструкция «Экспорт CSV»')}<div class="arrow">↓ Ответ с опорой на материал</div>${box('Ответ клиенту','Откройте «Отчёты», выберите период и нажмите «Экспорт CSV».<br><br><strong>Источник: Экспорт CSV</strong>','good')}</section><section><h2>Вопрос об индивидуальной скидке</h2>${box('Надёжного ответа нет','В публичной базе нет условий индивидуальной скидки.')}<div class="arrow">↓ Создаём обращение</div>${box('Диалог ведёт оператор','Бот сообщает о передаче вопроса. Следующие сообщения дополняют обращение.','warn')}</section></div><p class="note">Ссылку собирает PHP из найденного документа. Во время работы оператора ИИ не отвечает.</p>`,
  'Схема по учебной базе статьи, не снимок Telegram. Ответ про CSV иллюстрирует содержание источника; формулировка модели может отличаться.',
  'Документ про экспорт CSV позволяет подготовить ответ со ссылкой; вопрос без надёжного ответа создаёт обращение оператору.');
illustration(1,'stable-event','move-cancel','Перенос меняет время, а не создаёт новое событие',
  box('Одна связь на весь срок записи','<strong>Бронь B-1042 → событие Google</strong><br><span class="id">event_id: 0123456789abcdef0123456789abcdef</span>')+
  `<div class="cols" style="margin-top:24px"><section><h2>До переноса</h2>${box('Время встречи','20 ноября · 10:00–11:00<br>Europe/Moscow')}${box('Версия события','etag: A')}</section><section><h2>После переноса</h2>${box('Новое время','20 ноября · 12:00–13:00<br>Europe/Moscow','good')}${box('Тот же event_id','PATCH с If-Match: A<br>Новый etag: B','good')}</section></div><p class="note">Если событие уже изменили вручную, ответ 412 переводит запись в review. Потерянный ответ проверяем чтением того же event_id.</p>`,
  'Поясняющая схема, не снимок Google Calendar. ID, время и обозначения etag условные; в API используются полные значения etag без изменений.',
  'Бронь B-1042 сохраняет один event_id при переносе с 10 на 12 часов; If-Match защищает чужие правки, конфликт требует сверки.');
illustration(2,'draft-states','format','Продолжить заполнение или увидеть принятую заявку',
  `<div class="cols"><section><h2>При повторном открытии</h2><div class="frame"><iframe src="draft-restored.html" title="Форма из статьи с восстановленным черновиком"></iframe></div></section><section><h2>После отправки</h2>${box('Фрагмент той же формы','<div class="status">Заявка №42 принята. Черновик очищен.</div><div class="new">Новая заявка</div>','good')}${box('Что хранит сервер','<strong>Одна заявка</strong><br>Состояние: submitted<br>Поля черновика очищены')}<p class="note">Повтор запроса возвращает №42. Новая заявка получает отдельный draftId.</p></section></div>`,
  'Фрагменты HTML-формы статьи с демонстрационными данными: восстановленный черновик, затем статус и кнопка после отправки. Нижняя памятка формы опущена; API Telegram и сервер здесь не вызываются.',
  'Восстановленная форма содержит вопрос о переносе базы; после отправки показан номер 42 и кнопка новой заявки, старый черновик очищен.',
  'Макет формы из примера');
fs.writeFileSync(path.join(output,'manifest.json'),JSON.stringify(manifest,null,2)+'\n');
