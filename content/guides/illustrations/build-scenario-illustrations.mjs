import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

// Labeled diagrams, not imitations of native Telegram/n8n screenshots.
// Run render-scenario-assets.php first; capture .canvas at its native width.
const here = path.dirname(fileURLToPath(import.meta.url));
const output = path.resolve(process.argv[2] || 'storage/app/illustrations-next');
fs.mkdirSync(output, { recursive: true });
const fixtures = JSON.parse(fs.readFileSync(path.join(output, 'scenario-fixtures.json'), 'utf8'));
const slugs = {
  inline: 'telegram-inline-bot-product-search', choice: 'telegram-mini-app-or-chatbot',
  cart: 'telegram-mini-app-cart-checkout', n8n: 'n8n-telegram-botgate-http-request',
  support: 'telegram-bot-support-operator-group', booking: 'telegram-bot-appointment-booking',
  approval: 'telegram-bot-request-approval', share: 'telegram-mini-app-share-message',
  chat: 'telegram-bot-select-notification-chat', father: 'telegram-bot-botfather',
  catalog: 'telegram-bot-product-catalog-pagination',
};
const articles = Object.fromEntries(Object.entries(slugs).map(([key, slug]) => {
  const packagePath = path.join(here, '../packages', slug + '.json');
  const source = fs.existsSync(packagePath) ? packagePath : path.join(here, '../current.json');
  const article = JSON.parse(fs.readFileSync(source, 'utf8')).find(a => a.slug === slug);
  if (!article) throw new Error(`Missing article ${slug}`);
  return [key, article];
}));
function code(key, label) {
  const node = articles[key].sections.flatMap(s => s.content.content)
    .find(n => n.attrs?.id === 'guide-code' && n.attrs.config.label === label);
  if (!node) throw new Error(`Missing source ${key}: ${label}`);
  return node.attrs.config.code;
}
const esc = value => String(value).replaceAll('&', '&amp;').replaceAll('<', '&lt;').replaceAll('>', '&gt;').replaceAll('"', '&quot;');
const text = value => esc(value).replaceAll('\n', '<br>');
const box = (body, kind = '') => `<div class="box ${kind}">${body}</div>`;
const label = s => `<div class="label">${s}</div>`;
const buttons = (items, selected = '') => `<div class="buttons">${items.map(s => `<span class="button ${s === selected ? 'selected' : ''}">${esc(s)}</span>`).join('')}</div>`;
const keyboard = markup => markup.inline_keyboard.map(row => buttons(row.map(b => b.text))).join('');
const note = s => `<p class="note">${s}</p>`;
const panel = (title, body) => `<section><h2>${title}</h2>${body}</section>`;
const columns = (...items) => `<div class="cols">${items.join('')}</div>`;
const arrow = s => `<div class="arrow">↓ <span>${s}</span></div>`;
const message = (who, body, kind = '') => box(label(who) + body, kind);
const field = (name, value) => `<div class="field"><span>${name}</span><div>${value}</div></div>`;
const css = `
*{box-sizing:border-box}body{margin:0;background:#111b2c;color:#edf3fc;font:26px/1.4 Arial,sans-serif}
.canvas{width:1080px;padding:40px;background:radial-gradient(ellipse at top right,#26334d,transparent 65%),#111b2c}
.eyebrow{font-size:18px;letter-spacing:1.5px;text-transform:uppercase;font-weight:700;color:#b5a9ef;margin-bottom:14px}
h1{font-size:38px;line-height:1.15;margin:0 0 12px;letter-spacing:-.5px}.lead{color:#c2cee0;font-size:25px;line-height:1.4;margin:0 0 30px}
h2{font-size:26px;line-height:1.3;margin:0 0 16px}.cols{display:grid;grid-template-columns:1fr 1fr;gap:28px;align-items:start}
section{min-width:0}.box{background:#f4f7fc;color:#1c2b40;border-radius:16px;padding:24px;margin-bottom:16px;overflow-wrap:anywhere}
.box:last-child{margin-bottom:0}.label{font-size:20px;line-height:1.3;font-weight:700;color:#455c7a;margin-bottom:10px}
.cols+.box{margin-top:20px}
.box p{margin:0}.box strong{font-weight:700}.muted{color:#526984}.line{border-top:1px solid #ced8e6;margin-top:16px;padding-top:16px}
.buttons{display:flex;gap:10px;margin-top:14px}.button{flex:1;padding:11px 10px;text-align:center;border:1px solid #b3cce7;background:#e0edfa;border-radius:8px;font-size:24px;color:#17517f;line-height:1.3}
.selected{background:#246ca7;color:white;border-color:#246ca7}.note{font-size:23px;line-height:1.45;margin:16px 0 0;color:#c0cfe3}
.good{background:#dff3e9;color:#184f40}.good .label{color:#21634f}.warn{background:#fff0d6;color:#614018}.warn .label{color:#80551b}
.internal{background:#e2e7ef}.reply{border-left:4px solid #4388b6;padding:8px 12px;background:#e2edf7;font-size:23px;margin-bottom:14px}
.arrow{font-size:26px;color:#a9d7ef;margin:12px 0 18px;font-weight:700}.arrow span{font-size:23px}
.field{margin-bottom:20px}.field span{display:block;color:#556a83;font-size:21px;margin-bottom:6px}.field div{padding:12px;border:1px solid #b9c8da;border-radius:8px;background:white}
.foot{font-size:20px;line-height:1.4;color:#acbdd5;border-top:1px solid #394961;padding-top:20px;margin-top:28px}
.mono{font-family:Consolas,monospace;font-size:23px;overflow-wrap:anywhere}.url{font-size:21px;color:#29618d;overflow-wrap:anywhere}
.list-row+.list-row{border-top:1px solid #cad8e8;margin-top:14px;padding-top:14px}.step{display:flex;gap:20px;align-items:start}.num{flex:0 0 46px;border-radius:50%;background:#315675;color:#fff;text-align:center;line-height:46px}.step>div:last-child{flex:1;min-width:0}
.setting{display:grid;grid-template-columns:1fr 1fr;gap:16px;border-top:1px solid #cbd7e6;padding:14px 0}.setting:first-child{border:0;padding-top:0}.setting:last-child{padding-bottom:0}.setting span{color:#526580;font-size:23px}
.preview-wrap{height:270px;overflow:hidden;border-radius:16px;background:white}.preview-wrap iframe{width:340px;height:300px;border:0;display:block;transform:scale(1.3);transform-origin:top left;pointer-events:none}
.mobile{width:390px;padding:22px;font-size:20px}.mobile h1{font-size:27px}.mobile .eyebrow{font-size:13px;letter-spacing:1px}.mobile .lead{font-size:19px;margin-bottom:22px}
.mobile .cols{grid-template-columns:1fr;gap:22px}.mobile h2{font-size:21px;margin-bottom:12px}.mobile .box{padding:18px;border-radius:12px;margin-bottom:12px}
.mobile .label{font-size:16px}.mobile .button{font-size:19px;padding:9px 6px}.mobile .note{font-size:18px;margin-top:12px}.mobile .foot{font-size:16px;margin-top:22px;padding-top:16px}
.mobile .arrow{font-size:23px;margin:8px 0 12px}.mobile .arrow span{font-size:18px}.mobile .reply{font-size:18px}.mobile .mono{font-size:18px}.mobile .url{font-size:17px}
.mobile .field{margin-bottom:14px}.mobile .field span{font-size:16px}.mobile .field div{padding:9px}.mobile .step{gap:12px}.mobile .num{flex-basis:34px;line-height:34px}
.mobile .setting{gap:10px;padding:10px 0}.mobile .setting span{font-size:17px}.mobile .setting{font-size:18px}.mobile .preview-wrap{height:230px}.mobile .preview-wrap iframe{width:346px;transform:none}
.mobile .foot{display:none}.mobile .canvas-note{display:none}
`;
const manifest = [];
function illustration(key, name, section, title, lead, body, caption, alt, tag = 'Схема · демонстрационные данные') {
  if (!articles[key].sections.some(s => s.id === section)) throw new Error(`Missing section ${section}`);
  for (const mobile of [false, true]) {
    const filename = `${name}-${mobile ? 'mobile' : 'desktop'}.html`;
    fs.writeFileSync(path.join(output, filename), `<!doctype html><html lang="ru"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>${esc(title)}</title><style>${css}</style><main class="canvas ${mobile ? 'mobile' : ''}"><div class="eyebrow">${tag}</div><h1>${title}</h1><p class="lead">${lead}</p>${typeof body === 'function' ? body(mobile) : body}<div class="foot">${caption}</div></main></html>`);
    manifest.push({ slug: slugs[key], name, section, mobile, filename, width: mobile ? 390 : 1080, caption, alt });
  }
}

const results = fixtures.inline.results;
const chosen = results[0];
illustration('inline', 'inline-search', 'enable', 'Поиск прямо в переписке', 'Карточку собеседнику отправляет пользователь.',
  box(label('1 · Ввод в поле сообщения') + '<strong>@имя_бота лампа</strong>') + columns(
    panel('2 · Выдача по запросу', box(results.map(r => `<div class="list-row"><strong>${esc(r.title)}</strong><br>${esc(r.description)}</div>`).join('')) + note('Выбор «Лампа Луна» отправит карточку в текущий чат.')),
    panel('3 · Сообщение после выбора', box(text(chosen.input_message_content.message_text) + keyboard(chosen.reply_markup)))
  ) + note('<strong>Inline-поиск:</strong> ввод @бот и запроса → выдача. <strong>Кнопки под сообщением:</strong> отдельные действия; здесь «Открыть товар» ведёт на сайт.'),
  'Схема, не снимок Telegram. Товары, цены, текст карточки и кнопка получены из PHP-примера статьи; username условный.',
  'Ввод @имя_бота лампа, два результата поиска и отправленная карточка «Лампа Луна» за 3 900 ₽ с кнопкой открытия товара.');

illustration('choice', 'chat-or-form', 'chat', 'Одна заявка — два интерфейса', 'Сервисный центр: услуга, проблема и контакт.', columns(
  panel('Чат · по одному шагу',
    message('1 · Бот спрашивает', 'Какая услуга нужна?' + buttons(['Ремонт ноутбука'])) +
    message('2 · Клиент описывает проблему', 'Ноутбук не включается.') +
    message('3 · Клиент оставляет контакт', 'demo@example.com') + note('Следующий вопрос зависит от шага диалога.')),
  panel('Mini App · всё перед глазами', box(field('Услуга', 'Ремонт ноутбука') + field('Проблема', 'Ноутбук не включается.') + field('Контакт', 'demo@example.com') + buttons(['Отправить заявку'])) + note('Можно проверить и изменить поля до отправки.'))
), 'Схема одного сценария из статьи. Тексты диалога и форма условные; это не готовый HTML-пример и не снимок Telegram.',
'Заявка на ремонт ноутбука в последовательном диалоге и в форме с теми же тремя полями.');

illustration('cart', 'cart-price-change', 'checkout-preview', 'Цена изменилась до оформления', 'Покупатель должен подтвердить новый расчёт.', columns(
  panel('1 · Первый расчёт', box(label('Корзина · самовывоз') + '<strong>Зелёный чай</strong><br>450 ₽ × 1 шт.' + '<div class="line"><strong>Итого: 450 ₽</strong></div>' + buttons(['Оформить'])) + note('Исходная цена товара — из примера статьи.')),
  panel('2 · После изменения цены', box(label('Сервер вернул конфликт') + 'Цена изменилась.<br>Заказ не создан.', 'warn') + box('<strong>Зелёный чай</strong><br>480 ₽ × 1 шт.<div class="line"><strong>Новый итог: 480 ₽</strong></div>' + buttons(['Подтвердить 480 ₽'])) + note('Заново загрузить корзину и получить расчёт.'))
), 'Пример интерфейса, а не результат готовой HTML-формы. Статья описывает серверную логику. Цена 480 ₽ — демонстрационное изменение.',
'Корзина с одной пачкой зелёного чая за 450 ₽; после изменения цены заказ не создан, новый итог 480 ₽ требует подтверждения.', 'Пример интерфейса · серверная логика');

const item = JSON.parse(code('n8n', 'JSON · Один item после подготовки'));
illustration('n8n', 'n8n-workflow', 'prepare', 'От заявки до проверенного ответа', 'Учебный workflow с одним тестовым item.', columns(
  panel('Сборка тестового сценария',
    box('<strong>Manual Trigger</strong><br>Ручной запуск') + arrow('передать один item') +
    box('<strong>Edit Fields (Set)</strong><br><span class="mono">lead_id: '+esc(item.lead_id)+'</span><br>'+esc(item.name)+'<br>'+esc(item.service)) +
    arrow('сформировать запрос') + box('<strong>HTTP Request</strong><br>POST → BotGate')),
  panel('Разбор результата',
    box(label('IF · оба условия') + '<span class="mono">statusCode === 200<br>body.ok === true</span>') +
    box(label('Условия выполнены') + 'Сохранить message_id.<br>Отметить отправку.', 'good') +
    box(label('Условия не выполнены / ошибка сети') + 'Разобрать ошибку.<br>При таймауте результат может быть неизвестен.', 'warn'))
), 'Схема workflow по статье, не снимок n8n. Никаких credentials и реальных данных; IF добавляется для проверки ответа HTTP Request.',
'Manual Trigger передаёт тестовую заявку через Edit Fields в HTTP Request; IF проверяет HTTP 200 и body.ok, ошибки идут в отдельную обработку.');

const settings = rows => box(rows.map(([k,v]) => `<div class="setting"><span>${k}</span><strong>${v}</strong></div>`).join(''));
illustration('n8n', 'n8n-request-settings', 'body', 'HTTP Request: что настроить', 'Памятка по полям из статьи, не окно редактора.', columns(
  panel('Запрос и тело', settings([
    ['Method', 'POST'], ['Authentication', 'Generic Credential Type → Header Auth'],
    ['Send Body', 'Включено'], ['Body Content Type', 'JSON'],
    ['Specify Body', 'Using JSON'], ['Поле JSON', 'Expression → объект'],
  ])),
  panel('Ответ и повтор', settings([
    ['Response Format', 'JSON'], ['Include Response<br>Headers and Status', 'Включено'],
    ['Never Error', 'Включено'], ['Follow Redirects', 'Выключено'],
    ['Retry On Fail', 'Выключено'], ['Timeout', '30000 мс'],
  ]))
) + note('URL: <span class="mono">https://bot-gate.ru/v1/bots/BOT_PUBLIC_ID/sendMessage</span>') +
'<div class="canvas-note">'+note('Authorization хранится в Credentials. Never Error требует явной проверки ответа и не превращает сетевой сбой в успех.')+'</div>',
'Схема настроек по статье и документации n8n. Названия могут отличаться в установленной версии. API-ключ и Telegram-токен не показаны.',
'Поля HTTP Request: POST, Header Auth, тело JSON через Expression; полный JSON-ответ, Never Error, без redirects и автоматического повтора.');

illustration('support', 'operator-reply', 'scenario', 'Как ответ доходит до клиента', 'Обращение №1042 связывает два разных чата.', columns(
  panel('Личный диалог клиента', message('Клиент → бот', 'Когда доставят заказ?') + note('Копия сообщения попадает в рабочую группу.') + '<div style="height:16px"></div>' +
    message('Бот → клиент · после Reply', 'Доставка завтра.', 'good') + note('Клиент не вступает в рабочую группу.')),
  panel('Закрытая рабочая группа', message('Карточка бота · №1042', 'Когда доставят заказ?') +
    message('Оператор · Reply на карточку', '<div class="reply">↳ Обращение №1042</div>Доставка завтра.', 'good') +
    message('Оператор · без Reply', 'Уточню детали у склада.', 'internal') + note('<strong>Обычное сообщение остаётся в группе.</strong>'))
) + note('Перед отправкой сервер проверяет оператора, связь Reply с обращением и открытый статус обращения.'),
'Схема маршрутизации из статьи. Переписка вымышленная; Reply показан условно, это не снимок Telegram.',
'Ответ оператора через Reply на карточку №1042 приходит клиенту от бота, а внутреннее сообщение без Reply остаётся в рабочей группе.');

const booking = JSON.parse(code('booking', 'JSON · inline-кнопки'));
illustration('booking', 'booking-states', 'buttons', 'Выбор времени ещё не запись', 'Консультация на 30 минут · Europe/Moscow.', columns(
  panel('1 · Дата и время', box(label('После /book') + 'Выберите дату' + buttons(['18 ноября', '19 ноября'], '18 ноября')) +
    box(label('18 ноября · время по Москве') + keyboard(booking) + note(''))),
  panel('2 · Резерв → подтверждение', box(label('Временно удерживается') + '<strong>18 ноября, 10:30–11:00</strong><br>Подтвердите до 10:05.' + buttons(['Подтвердить', 'Отменить']), 'warn') +
    arrow('нажать до истечения резерва') + box(label('Запись подтверждена') + '18 ноября, 10:30–11:00', 'good'))
) + note('В примере резерв создан в 10:00 и действует 5 минут. Если срок вышел, нужно выбрать время снова.'),
'Схема кнопок и состояний из статьи, не снимок Telegram. Даты и срок учебные; кнопки времени взяты из JSON-примера.',
'Выбор даты, кнопки 10:30 и 11:00, временный резерв до 10:05 с подтверждением или отменой и подтверждённая запись.');

const approval = JSON.parse(code('approval', 'JSON · reply_markup карточки'));
const reason = JSON.parse(code('approval', 'JSON · запрос причины отказа'));
illustration('approval', 'approval-rejection', 'comment', 'Отказ требует причины', 'До получения комментария заявка остаётся в ожидании.', columns(
  panel('1 · Карточка закупки', box(label('Заявка №142 · версия 1') + '<strong>Два монитора</strong><br>Автор: Анна<br>Сумма: 40 000 ₽' + keyboard(approval)) + note('Руководитель нажимает «Отклонить».')),
  panel('2 · Ответ на запрос причины', message('Бот', esc(reason.text)) + message('Руководитель · Reply на запрос', '<div class="reply">↳ Причина отказа · №142</div>Уточните комплектацию.'))
) + arrow('сервер проверил и сохранил решение') + box(label('Итоговая карточка · без кнопок') + '<strong>Заявка №142 отклонена.</strong><br>Причина: уточните комплектацию.', 'warn'),
'Схема по статье: кнопки и запрос причины — из JSON. Сумма, автор и текст ответа вымышлены. Это не снимок Telegram.',
'Карточка закупки №142 с кнопками Одобрить и Отклонить; после Reply с причиной появляется окончательный отказ без кнопок.');

const shareText = 'Синяя кружка, 350 мл\nЦена на момент подготовки: 650 ₽\nhttps://shop.example.com/products/mug-blue';
let shareHtml = code('share', 'HTML и JavaScript · public/share.html').replace(/<script\b[^>]*>[\s\S]*?<\/script>/gi, '');
shareHtml += `<style>h1,p,#prepare,label{display:none}body{margin:0;padding:16px}#preview{margin-top:0}#share{margin-bottom:0}</style><script>document.getElementById('preview').textContent=${JSON.stringify(shareText)};document.getElementById('share').disabled=false;</script>`;
illustration('share', 'share-public-card', 'screen', 'Поделиться публичной карточкой', 'Сначала предпросмотр, затем действие пользователя.', columns(
  panel('1 · Фрагмент share.html', `<div class="preview-wrap"><iframe srcdoc="${esc(shareHtml)}" title="Предпросмотр публичной карточки"></iframe></div>` + note('Показаны только предпросмотр и кнопка из HTML статьи.')),
  panel('2 · Выбор получателя', box(label('Условное обозначение окна Telegram') + '<strong>Выбрать личный чат или группу</strong><br>Например, чат с Анной.') + arrow('пользователь завершил отправку') +
    box(label('Результат у получателя') + 'Синяя кружка, 350 мл<br>Цена на момент подготовки: 650 ₽<br><span class="url">https://shop.example.com/products/mug-blue</span>' + buttons(['Открыть товар'])) +
    note('Отмена окна не считается отправкой.'))
), 'Увеличенный фрагмент HTML статьи с тестовой карточкой. Выбор чата и сообщение показаны схемой, не снимками Telegram. Домен магазина условный.',
'Публичная карточка синей кружки за 650 ₽ в HTML-примере, выбор получателя и сообщение с кнопкой Открыть товар; отмена не означает отправку.',
'Фрагмент HTML + схема · тестовые данные');

// These labels and messages are literal strings in bot.php, checked to prevent drift.
const chatSource = code('chat', 'PHP · bot.php');
for (const token of ['Выбрать ', 'Чат подключён. Отключить: /disconnect', 'Проверка подключения уведомлений.', 'chat_is_created']) {
  if (!chatSource.includes(token)) throw new Error(`Chat example changed: ${token}`);
}
illustration('chat', 'connect-notification-chat', 'flow', 'Выбрать чат → проверить доступ', 'Подключение включает только подтверждённая пробная отправка.', columns(
  panel('1 · Личный диалог с ботом', box(label('Пользователь отправил /group') + 'Выберите свой чат.<br>Бот должен быть администратором.' + buttons(['Выбрать группу'])) +
    arrow('открыть выбор Telegram') + box(label('Схема выбора') + 'Своя группа «Заявки»<br><span class="muted">Бот уже добавлен.</span>')),
  panel('2 · Сервер проверяет', box('Владелец чата ✓<br>Тип группы ✓<br>Права бота ✓') +
    box(label('Пробное сообщение в группу') + 'Проверка подключения уведомлений. Этот чат выбран его владельцем.') +
    arrow('получен успешный ответ') + box('Чат подключён.<br>Отключить: /disconnect', 'good'))
) + note('При неподтверждённой пробной отправке интеграция остаётся выключенной.'),
'Схема сценария /group из статьи. «Выбрать группу» — кнопка reply-клавиатуры с request_chat. Название группы условное; нативное окно Telegram не воспроизведено.',
'Команда /group, кнопка Выбрать группу, выбор своего чата, проверки прав и пробная отправка; только затем сообщение Чат подключён.');

const step = (n, title, body) => box(`<div class="step"><div class="num">${n}</div><div>${label(title)}${body}</div></div>`);
illustration('father', 'botfather-steps', 'create', 'Создание бота через BotFather', 'Сначала профиль; обработку заявок подключают отдельно.', columns(
  panel('Начать создание', step(1, 'Официальный аккаунт', '<strong>@BotFather</strong><br>Проверьте username.') + step(2, 'Команда в диалоге', '<strong>/newbot</strong>')),
  panel('Задать имя и адрес', step(3, 'Отображаемое имя', 'Мастерская —<br>заявки на ремонт') + step(4, 'Username', '<span class="mono">workshop_requests_bot</span><br>Если занят — выберите другой.'))
) + box(label('Результат') + 'BotFather выдаст ссылку на бота и секретный токен. Сохраните их; токен на схеме не показан.', 'good'),
'Памятка по шагам из статьи, не снимок BotFather. Имя и username — примеры; доступность адреса не проверена. Токен не изображён.',
'Проверить @BotFather, отправить /newbot, задать имя мастерской и username; затем сохранить ссылку и секретный токен.');

const catalogPanel = (title, page) => panel(title, box(text(page.text) + keyboard(page.reply_markup)));
illustration('catalog', 'catalog-pages', 'load', 'Один каталог, две крайние страницы', 'Только текст и навигация — как в PHP-примере.', columns(
  catalogPanel('Первая страница', fixtures.catalogFirst), catalogPanel('Последняя страница', fixtures.catalogLast)
) + note('Нажатие «Вперёд» меняет то же сообщение. На первой странице нет «Назад», на последней — «Вперёд».'),
'Схема сообщений, не снимок Telegram. Текст и кнопки получены функцией catalogPage из статьи на шести вымышленных товарах; фотографий и покупки в примере нет.',
'Первая страница текстового каталога содержит пять товаров и кнопку Вперёд; вторая, последняя — один товар и кнопку Назад.');

fs.writeFileSync(path.join(output, 'manifest.json'), JSON.stringify(manifest, null, 2) + '\n');
console.log(`Built ${manifest.length} sources for ${Object.keys(slugs).length} guides in ${output}`);
