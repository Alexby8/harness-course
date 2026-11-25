import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

// Produces local, network-free HTML compositions from the guides' own markup.
// Capture these compositions as raster images; do not publish executable HTML.
const here = path.dirname(fileURLToPath(import.meta.url));
const output = path.resolve(process.argv[2] || 'tmp/guide-visuals');
fs.mkdirSync(output, { recursive: true });
const slugs = {
  adaptive: 'telegram-mini-app-iphone-android',
  form: 'telegram-mini-app-html-php-form',
  settings: 'telegram-mini-app-notification-settings',
  tickets: 'telegram-bot-qr-tickets',
  pdf: 'laravel-telegram-pdf-document',
};
const guides = Object.fromEntries(Object.entries(slugs).map(([key, slug]) => {
  const packagePath = path.join(here, '../packages', slug + '.json');
  const source = fs.existsSync(packagePath) ? packagePath : path.join(here, '../current.json');
  return [key, JSON.parse(fs.readFileSync(source, 'utf8')).find(a => a.slug === slug)];
}));
function code(key, label) {
  const node = guides[key].sections.flatMap(s => s.content.content)
    .find(n => n.attrs?.id === 'guide-code' && n.attrs.config.label === label);
  if (!node) throw new Error(`Missing source: ${key}, ${label}`);
  return node.attrs.config.code;
}
const escape = s => s.replaceAll('&', '&amp;').replaceAll('"', '&quot;').replaceAll('<', '&lt;');
const light = '--tg-theme-bg-color:#ffffff;--tg-theme-text-color:#17212b;--tg-theme-secondary-bg-color:#f2f5f8;--tg-theme-button-color:#287dbe;--tg-theme-button-text-color:#ffffff;color-scheme:light';
const dark = '--tg-theme-bg-color:#17212b;--tg-theme-text-color:#eef4fa;--tg-theme-secondary-bg-color:#242f3d;--tg-theme-button-color:#5288c1;--tg-theme-button-text-color:#ffffff;color-scheme:dark';
function source(key, label, { css = '', fixture = '', theme = light } = {}) {
  const html = code(key, label).replace(/<script\b[^>]*>[\s\S]*?<\/script>/gi, '')
    .replace(/<link\b[^>]*>/gi, '');
  return html + `<style>:root{${theme}}html{scrollbar-width:none}::-webkit-scrollbar{display:none}${css}</style><script>${fixture}</script>`;
}
const frame = (html, height, title) => `<div class="screen"><iframe height="${height}" title="${title}" srcdoc="${escape(html)}"></iframe></div>`;
const panel = (title, body, note = '') => `<section><h2>${title}</h2>${body}${note ? `<p class="note">${note}</p>` : ''}</section>`;
const css = `
*{box-sizing:border-box}html{scrollbar-width:none}body{margin:0;font-family:Arial,sans-serif;color:#edf3fc;background:#101827}::-webkit-scrollbar{display:none}
.canvas{width:1080px;padding:36px;background:radial-gradient(ellipse at top right,#242745 0,transparent 65%),#101827}
.eyebrow{color:#b4a4ff;font-size:12px;font-weight:700;letter-spacing:1.6px;text-transform:uppercase;margin-bottom:12px}
h1{font-size:30px;line-height:1.2;margin:0 0 12px;letter-spacing:-.5px}.lead{font-size:17px;color:#b5c3d7;line-height:1.5;margin:0 0 28px}
h2{font-size:19px;line-height:1.35;margin:0 0 16px}.cols{display:grid;grid-template-columns:1fr 1fr;gap:28px;align-items:start}
.screen{max-width:360px;margin:auto;border:1px solid #4a5970;border-radius:16px;overflow:hidden;background:white;box-shadow:0 8px 25px #0002}iframe{width:100%;display:block;border:0;pointer-events:none}
.note{color:#b2c1d4;font-size:16px;line-height:1.5;margin:16px 0 0}.foot{border-top:1px solid #354054;margin-top:28px;padding-top:18px;font-size:14px;color:#9eacc1;line-height:1.5}
.callout{background:#19273b;border:1px solid #3b4b63;border-radius:14px;padding:22px;margin-top:18px}.callout p{font-size:17px;line-height:1.5;margin:0;color:#c9d5e5}.callout strong{color:#fff}
.badge{color:#9be1ca;font-size:13px;letter-spacing:1px;margin-bottom:12px;font-weight:bold}.keyboard{background:repeating-linear-gradient(90deg,#303e52 0,#303e52 25px,#233044 25px,#233044 30px);height:120px;display:grid;place-items:center;border-top:5px solid #17212b}.keyboard span{font-size:16px;background:#17212b;padding:10px;border-radius:8px}
.zone{font-size:13px;line-height:1.4;text-align:center;color:#d9cbff;background:#453465;padding:8px}.zone.bottom{color:#a0e6d4;background:#214d47}
.document{background:#e6eaf0;border-radius:14px;padding:18px}.document img{display:block;width:100%;height:auto;box-shadow:0 4px 20px #0002}
.qr{display:block;width:250px;max-width:100%;margin:24px auto;background:#fff;border:16px solid white;border-radius:12px}.ticket{border:1px solid #42536b;border-radius:16px;padding:24px;background:#19273b;text-align:center}.ticket h2{margin:0}.ticket .note{font-size:15px}
.states{display:grid;gap:16px}.state h2{font-size:16px;margin:0 0 10px;color:#bdb0f4}.state .screen{max-width:none}.state iframe{height:180px}
.mobile{width:390px;padding:24px}.mobile h1{font-size:25px}.mobile .lead{font-size:16px;margin-bottom:24px}.mobile .cols{grid-template-columns:1fr;gap:28px}.mobile h2{font-size:18px}.mobile .note{font-size:15px}.mobile .callout{padding:18px}.mobile .callout p{font-size:16px}.mobile .foot{font-size:13px}.mobile .document{padding:8px}.mobile .ticket{padding:20px}.mobile .states{gap:20px}
`;
const manifest = [];
function illustration(key, name, title, lead, body, foot) {
  for (const mobile of [false, true]) {
    const filename = `${name}-${mobile ? 'mobile' : 'desktop'}.html`;
    fs.writeFileSync(path.join(output, filename), `<!doctype html><html lang="ru"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>${title}</title><style>${css}</style><main class="canvas ${mobile ? 'mobile' : ''}"><div class="eyebrow">BotGate · практический пример</div><h1>${title}</h1><p class="lead">${lead}</p>${typeof body === 'function' ? body(mobile) : body}<div class="foot">${foot}</div></main></html>`);
    manifest.push({slug:slugs[key],name,mobile,filename,width:mobile?390:1080});
  }
}

const formFixture = `document.querySelector('[name=name]').value='Алёна';document.querySelector('[name=question]').value='Хочу подключить уведомления о заказах.';document.getElementById('send').disabled=false;`;
const form1 = source('form','HTML · public/index.html',{fixture:formFixture});
const form2 = source('form','HTML · public/index.html',{fixture:formFixture+`document.getElementById('send').disabled=true;document.getElementById('status').textContent='Заявка №142 сохранена. Подтверждение отправлено в чат.';document.getElementById('new').hidden=false;`});
illustration('form','form-result','Что получится из HTML-примера','Форма остаётся открытой до ответа сервера.',`<div class="cols">${panel('1. Заполняем заявку',frame(form1,650,'Заполненная форма'))}${panel('2. Сервер подтвердил сохранение',frame(form2,810,'Сохранённая заявка'))}</div>`,'Разметка из статьи, демонстрационные данные и состояния. Это макет, а не снимок клиента Telegram.');

const settings = source('settings','HTML и JavaScript · public/settings.html',{fixture:`document.getElementById('fields').disabled=false;document.getElementById('timezone').add(new Option('Europe/Moscow','Europe/Moscow'));document.getElementById('orders').checked=true;document.getElementById('quiet').checked=true;document.getElementById('status').textContent='Настройки сохранены. Разрешение писать запрашивается отдельно.';`});
illustration('settings','notification-preferences','Настройки и разрешение — отдельно','Пользователь выбирает сообщения и время их получения.',`<div class="cols">${panel('Форма из примера',frame(settings,780,'Настройки уведомлений'))}<section><div class="callout"><div class="badge">1 · ПРЕДПОЧТЕНИЯ</div><p><strong>«Сохранить»</strong> записывает категории, часовой пояс и часы тишины в вашем приложении.</p></div><div class="callout"><div class="badge">2 · ВОЗМОЖНОСТЬ НАПИСАТЬ</div><p><strong>«Разрешить боту писать»</strong> вызывает отдельный запрос Telegram. Сохранение формы его не заменяет.</p></div><div class="callout"><div class="badge">ПРИМЕР НА ЭКРАНЕ</div><p>Статусы заказов включены, предложения магазина выключены. Тишина — с 22:00 до 08:00 по Москве.</p></div><p class="note">На первом запуске обе категории выключены. Здесь показан выбор пользователя после сохранения.</p></section></div>`,'Разметка из статьи с тестовыми значениями. Нативное окно разрешения Telegram на макете не изображено.');

const adaptiveCss = code('adaptive','CSS · app.css');
function adaptive(height, theme=light) {
  return source('adaptive','HTML · index.html',{theme,css:adaptiveCss+`:root{--tg-viewport-stable-height:${height}px;--editing-height:${height}px}`,fixture:`document.querySelector('[name=name]').value='Алёна';document.querySelector('[name=comment]').value='Позвоните перед выдачей заказа.';`});
}
illustration('adaptive','viewport-keyboard','Клавиатура меняет доступную высоту','Поле и основное действие должны оставаться достижимыми.',`<div class="cols">${panel('Клавиатура закрыта',frame(adaptive(520),520,'Форма без клавиатуры'),'Форма занимает доступную высоту. Кнопка находится в отдельной строке сетки.')}<section><h2>Клавиатура открыта</h2><div class="screen"><iframe height="400" srcdoc="${escape(adaptive(400))}" title="Уменьшенная область формы"></iframe><div class="keyboard"><span>Область экранной клавиатуры</span></div></div><p class="note">Область формы уменьшается. Если места мало, поля прокручиваются внутри неё.</p></section></div>`,'Схема на основе HTML/CSS статьи. Клавиатура обозначена условно; реальное поведение нужно проверять на iPhone и Android.');
const themed = (theme, title) => `<div class="screen"><div class="zone">Верхний безопасный отступ</div><iframe height="440" title="${title}" srcdoc="${escape(adaptive(440,theme))}"></iframe><div class="zone bottom">Нижний безопасный отступ</div></div>`;
illustration('adaptive','themes-safe-areas','Одна форма в двух темах','Цвета берём из темы Telegram, отступы — из данных окружения.',`<div class="cols">${panel('Светлая тема',themed(light,'Светлая форма'))}${panel('Тёмная тема',themed(dark,'Тёмная форма'))}</div>`,'Поясняющий макет из примера. Цветные полосы обозначают отступы, а не элементы Telegram; их реальные размеры зависят от устройства и режима окна.');

const checkerLabel = 'HTML и JavaScript · public/checker.html';
function checker(message, enabled) {
  return source('tickets',checkerLabel,{css:'body{margin:18px}h1,#scan,#token,#preview{display:none}#result{margin:0}#consume{margin:14px 0 0}',fixture:`document.getElementById('result').textContent=${JSON.stringify(message)};document.getElementById('consume').disabled=${!enabled};`});
}
const states = [
  ['1. Проверили код','Билет действителен. Подтвердите проход.',true],
  ['2. Подтвердили проход','Проход записан. Пропустите участника.',false],
  ['3. Проверили повторно','Билет уже использован. Повторный проход запрещён.',false],
];
illustration('tickets','ticket-check','QR-код ещё не означает проход','Проверка и использование билета — два отдельных действия.',`<div class="cols"><section><div class="ticket"><h2>QR участника</h2><img class="qr" src="ticket-demo.png" alt="Демонстрационный QR"><p class="note">В примере бот отправляет PNG-документ. Внутри QR — непрозрачный код без имени и телефона.</p></div><div class="callout"><p>Только кнопка <strong>«Пропустить участника»</strong> запускает серверную запись прохода.</p></div><p class="note">QR здесь учебный: он не связан с действующим билетом.</p></section><section><h2>Фрагменты экрана проверяющего</h2><div class="states">${states.map(([title,message,enabled])=>`<div class="state"><h2>${title}</h2>${frame(checker(message,enabled),180,title)}</div>`).join('')}</div></section></div>`,'Тексты и кнопка взяты из checker.html статьи. Показаны фрагменты интерфейса с тестовыми ответами сервера; рамка QR — оформление иллюстрации.');

illustration('pdf','pdf-preview','PDF из Blade-шаблона статьи','Подтверждение заявки с кириллицей и переносом строки.',mobile => mobile
  ? `<h2>Реквизиты: номер, дата, клиент</h2><div class="document"><img src="pdf-values.png" alt="Значения реквизитов PDF"></div><h2 style="margin-top:24px">Тема и текст обращения</h2><div class="document"><img src="pdf-subject.png" alt="Тема обращения в PDF"></div><p class="note">Увеличенные фрагменты одного документа. Данные — из примера для Tinker.</p>`
  : `<div class="document"><img src="pdf-crop.png" alt="Фрагмент готового PDF"></div><p class="note">Фрагмент страницы A4: номер, дата, имя и тема обращения. Данные совпадают с примером для Tinker.</p>`,
  'Документ сформирован Dompdf. Фрагменты обрезаны только на иллюстрации; сам PDF сохраняет размер страницы A4.');

fs.writeFileSync(path.join(output,'manifest.json'),JSON.stringify(manifest,null,2)+'\n');
fs.writeFileSync(path.join(output,'pdf-template.blade.php'),code('pdf','Blade · application-confirmation.blade.php'));
fs.writeFileSync(path.join(output,'pdf-fixture.php'),'<?php\n'+code('pdf','PHP · команда для Tinker').split('Pdf::loadView')[0].replace('use Barryvdh\\DomPDF\\Facade\\Pdf;',''));
console.log(`Built ${manifest.length} illustration sources in ${output}`);
