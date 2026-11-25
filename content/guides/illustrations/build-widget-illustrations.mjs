import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

// Build local illustration sources from the same widget HTML used in the guide.
// Usage: node content/guides/illustrations/build-widget-illustrations.mjs OUTPUT_DIRECTORY
const here = path.dirname(fileURLToPath(import.meta.url));
const output = path.resolve(process.argv[2] || 'storage/app/guide-illustrations');
const packagePath = path.join(here, '../packages/telegram-html-widgets-order-card.json');
const catalogPath = fs.existsSync(packagePath) ? packagePath : path.join(here, '../current.json');
const guide = JSON.parse(fs.readFileSync(catalogPath, 'utf8')).find(item => item.slug === 'telegram-html-widgets-order-card');
const blocks = guide.sections.flatMap(section => section.content.content);
const widget = blocks.find(node => node.attrs?.config?.label === 'HTML · widget.html').attrs.config.code;
const escape = value => value.replaceAll('&', '&amp;').replaceAll('"', '&quot;').replaceAll('<', '&lt;');
fs.mkdirSync(output, { recursive: true });

const css = `
*{box-sizing:border-box}html{scrollbar-width:none}body{margin:0;background:#0b1120;color:#edf3fc;font-family:Arial,sans-serif}::-webkit-scrollbar{display:none}
.canvas{width:1080px;padding:36px;background:radial-gradient(ellipse at top right,#23244b 0,transparent 60%),#101827}
.eyebrow{color:#b4a4ff;font-size:12px;font-weight:700;letter-spacing:2px;text-transform:uppercase;margin-bottom:12px}
h1{font-size:30px;line-height:1.2;letter-spacing:-.6px;margin:0 0 12px} .lead{font-size:16px;line-height:1.5;color:#b1bed1;margin:0}
.cards{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:18px;margin-top:30px}
.step{display:flex;gap:10px;align-items:center;font-size:15px;margin-bottom:14px;font-weight:700}
.num{display:flex;align-items:center;justify-content:center;border:1px solid #49466a;border-radius:50%;width:27px;height:27px;color:#c3b7ff;font-size:12px;flex:none}
.bubble{border-radius:16px;background:#17212b;overflow:hidden;border:1px solid #354153;box-shadow:0 8px 24px #0003}
iframe{display:block;width:100%;height:176px;border:0;pointer-events:none}
.caption{padding:0 16px 14px;color:#c0d0df;font-size:12px;line-height:1.5}
.note{color:#93a5ba;font-size:14px;line-height:1.5;margin:24px 0 0}
.step-note{color:#a7b7cc;font-size:13px;line-height:1.5;margin:12px 0 0}
.top{display:grid;grid-template-columns:1fr 42px 1.4fr;align-items:center;margin:28px 0 22px}
.box{border:1px solid #35445b;border-radius:14px;background:#172337;padding:20px}
.box h2{font-size:20px;line-height:1.35;margin:0 0 12px}.box p{font-size:16px;line-height:1.5;color:#b4c4d8;margin:0}
.code{display:inline-block;font:14px Consolas,monospace;color:#c2b4ff;margin:10px 0 0;overflow-wrap:anywhere}
.arrow{text-align:center;font-size:30px;color:#8e81d1}
.saved{border:1px solid #406859;border-radius:9px;background:#18362e;color:#b9efcf;margin-top:16px;padding:12px;font-size:15px}
.branches{display:grid;grid-template-columns:1fr 1fr;gap:20px}.branch{border-top:3px solid #9a87ed}.branch:last-child{border-top-color:#59bdb6}
.badge{font-size:12px;letter-spacing:1px;color:#c2b4ff;font-weight:700;margin-bottom:14px}.branch:last-child .badge{color:#7ed8d1}
.branch .outcome{margin-top:15px;padding-top:15px;border-top:1px solid #35445b;color:#ecf4ff}
.mobile{width:390px;padding:24px}.mobile h1{font-size:25px}.mobile .lead{font-size:15px}.mobile .cards{grid-template-columns:1fr;gap:24px;margin-top:24px}
.mobile iframe{height:166px}.mobile .step-note{margin-top:8px}.mobile .note{font-size:13px}
.mobile .top{grid-template-columns:1fr;gap:12px;margin-top:24px}.mobile .arrow{transform:rotate(90deg);line-height:1}
.mobile .branches{grid-template-columns:1fr;gap:16px}.mobile .box{padding:18px}.mobile .box h2{font-size:18px}.mobile .box p{font-size:15px}
`;
const shell = (name, mobile, body) => {
  fs.writeFileSync(path.join(output, `${name}-${mobile ? 'mobile' : 'desktop'}.html`), `<!doctype html><html lang="ru"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Иллюстрация · BotGate</title><style>${css}</style><main class="canvas ${mobile ? 'mobile' : ''}">${body}</main></html>`);
};
const mockWidget = (status, r) => {
  const bridge = `<style>:root{--tg-theme-text-color:#f3f6fa;--tg-theme-bg-color:#17212b;--tg-theme-button-color:#2b8fca;--tg-theme-button-text-color:#fff;--tg-theme-hint-color:#9eb1c3}</style><script>window.TelegramWidget={chatData:'${JSON.stringify({ id: 1042, status, r })}',onEvent(){},ready(){}};</script>`;
  return widget.replace('<style>', bridge + '<style>');
};
const steps = [
  ['new', '01', 'Заказ принят', 'Принят', 'Магазин сохранил заказ и отправил карточку.'],
  ['ready', '02', 'Можно забирать', 'Готов к выдаче', 'Новый статус открыл кнопку подтверждения.'],
  ['received', '03', 'Получение подтверждено', 'Получен', 'Сервер сохранил решение покупателя.'],
];
for (const mobile of [false, true]) {
  shell('order-states', mobile, `<div class="eyebrow">Telegram Widgets · пример из статьи</div><h1>Один заказ. Три состояния карточки.</h1><p class="lead">Меняется состояние сообщения, HTML остаётся прежним.</p><div class="cards">${steps.map(([state, n, title, status, note], i) => `<section><div class="step"><span class="num">${n}</span>${title}</div><div class="bubble"><iframe title="Заказ ${status}" srcdoc="${escape(mockWidget(state, i + 1))}"></iframe><div class="caption">Заказ №1042: ${status}</div></div><p class="step-note">${note}</p></section>`).join('')}</div><p class="note">Макет из HTML-примера. Внешний вид внутри Telegram зависит от клиента и темы.</p>`);
  shell('query-flow', mobile, `<div class="eyebrow">От кнопки до состояния заказа</div><h1>Одно нажатие — два результата</h1><p class="lead">Ответ на запрос и обновление сообщения — разные операции.</p><div class="top"><div class="box"><h2>1. Покупатель нажимает<br>«Я получил заказ»</h2><p>Карточка передаёт действие и ревизию.</p><span class="code">request('received:2')</span></div><div class="arrow">→</div><div class="box"><h2>2. Бот проверяет запрос</h2><p>Владелец → сообщение → ревизия → допустимый переход.</p><span class="code">widget_query</span><div class="saved">Сохраняет в БД: status = received, r = 3</div></div></div><div class="branches"><div class="box branch"><div class="badge">3А · ОТВЕТ НА НАЖАТИЕ</div><h2>Показать результат<br>открытой карточке</h2><span class="code">answerWidgetQuery</span><p class="outcome">Пользователь видит подтверждение. Этот ответ не меняет данные сообщения.</p></div><div class="box branch"><div class="badge">3Б · ОТДЕЛЬНЫЙ ПРОЦЕСС</div><h2>Обновить сохранённое<br>сообщение</h2><span class="code">editMessageWidgetData</span><p class="outcome">Меняется chat_data. Событие chatDataChanged перерисовывает карточку.</p></div></div><p class="note">БД хранит результат действия. Потерянный ответ проверяем через «Обновить», а не повторяем действие вслепую.</p>`);
}
console.log(output);
