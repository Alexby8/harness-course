import fs from 'node:fs';
import path from 'node:path';

// Conceptual diagrams, not Telegram or Google interface screenshots.
const output = path.resolve(process.argv[2] || 'storage/app/illustrations-payments-sheets-rss-streaming');
fs.mkdirSync(output, { recursive: true });
const diagrams = [
  { slug: 'telegram-bot-physical-goods-payments', title: 'Когда заказ оплачен', subtitle: 'Кружка 1490 ₽ + доставка 300 ₽ = 1790 ₽',
    steps: [
      ['01 · СЧЁТ', 'draft', 'Цена и владелец сохранены. Товар ещё не зарезервирован.'],
      ['02 · ДОСТАВКА', 'shipping_query', 'Проверяем адрес и предлагаем обычную или срочную доставку.'],
      ['03 · ПЕРЕД ОПЛАТОЙ', 'held', 'Проверяем 1790 ₽ и резервируем одну кружку. Это ещё не оплата.'],
      ['04 · ПЛАТЁЖ ПОЛУЧЕН', 'paid', 'successful_payment совпал с заказом. Можно готовить отгрузку.'],
    ], note: 'Не совпали заказ или сумма → сохраняем платёж в review. Зависший held → сверяем с провайдером.' },
  { slug: 'telegram-bot-google-sheets-catalog', title: 'Каталог меняется целиком', subtitle: 'Google Таблица → проверка → локальная версия → бот',
    steps: [
      ['01 · РЕДАКТИРОВАНИЕ', 'mug-450', 'ID остаётся прежним. Менеджер меняет цену, остаток или active.'],
      ['02 · ПРОВЕРКА', 'Все строки', 'Дубли ID, пустой остаток и неверная цена останавливают импорт.'],
      ['03 · ПЕРЕКЛЮЧЕНИЕ', 'Один снимок', 'Только проверенный набор заменяет каталог в короткой транзакции.'],
      ['04 · КАРТОЧКА', '/item mug-450', 'Бот читает базу. После 15 минут без успешной проверки отмечает устаревание.'],
    ], note: 'Google недоступен или данные неверны → сохраняем последнюю рабочую версию. Для отключения товара: active = FALSE.' },
  { slug: 'telegram-rss-channel-autopost', title: 'Из ленты — в очередь', subtitle: 'Повторная загрузка не означает повторную публикацию',
    steps: [
      ['01 · ПЕРВЫЙ ЗАПУСК', 'baseline', 'Запоминаем текущие записи. Архив не отправляем в канал.'],
      ['02 · НОВАЯ ЗАПИСЬ', 'guid или link', 'Проверяем ключ и фильтр. Исправление заголовка не создаёт новый пост.'],
      ['03 · РАСПИСАНИЕ', 'queued', 'Сохраняем анонс и время. Между попытками — минимум 120 секунд.'],
      ['04 · ОТПРАВКА', 'sent / unknown', 'Есть message_id — принят. Потерян ответ — сначала проверяем канал.'],
    ], note: 'Ошибка XML или сети не очищает очередь. После простоя публикации продолжаются с интервалом.' },
  { slug: 'telegram-bot-ai-streaming-replies', title: 'Черновик не равен сообщению', subtitle: 'Одна генерация → один draft_id → отдельный финал',
    steps: [
      ['01 · НАЧАЛО', 'Thinking…', 'Пустой sendMessageDraft показывает ожидание. Запрос ИИ выполняется отдельно.'],
      ['02 · ПОТОК', 'Временное превью', 'Отправляем накопленный текст. Черновик живёт 30 секунд без обновления.'],
      ['03 · ОСТАНОВКА', 'Stop → отмена', 'Проверяем chat + draft_id, закрываем поток. Частичный текст помечаем.'],
      ['04 · СОХРАНЕНИЕ', 'sendMessage', 'Постоянный ответ отправляем отдельно и сохраняем его message_id.'],
    ], note: 'keep_on_stop не делает черновик постоянным. Готовый текст в базе ещё не означает, что финал доставлен.' },
];
const escape = value => value.replaceAll('&', '&amp;').replaceAll('<', '&lt;').replaceAll('>', '&gt;');
const manifest = [];
for (const d of diagrams) {
  for (const mobile of [false, true]) {
    const width = mobile ? 390 : 1080;
    const filename = `${d.slug}${mobile ? '-mobile' : ''}.html`;
    const cards = d.steps.map(([label, title, description]) => `<section class="box"><small>${escape(label)}</small><h2>${escape(title)}</h2><p>${escape(description)}</p></section>`).join('');
    const html = `<!doctype html><html lang="ru"><meta charset="utf-8"><title>${escape(d.title)}</title><style>
      *{box-sizing:border-box}body{margin:0;font-family:"Segoe UI",Arial,sans-serif;color:#192c3b;background:#e9eff6}
      .canvas{width:${width}px;padding:${mobile ? 22 : 40}px;background:linear-gradient(145deg,#edf7fc,#f0f1fa)}
      .badge{display:inline-block;padding:6px 11px;background:#dcecf6;color:#285878;font-size:${mobile ? 12 : 14}px;letter-spacing:1px;border-radius:7px;font-weight:600}
      h1{font-size:${mobile ? 29 : 40}px;line-height:1.16;margin:16px 0 10px;letter-spacing:-.6px}.subtitle{font-size:${mobile ? 17 : 22}px;line-height:1.45;color:#526577;margin:0 0 24px}
      .grid{display:grid;grid-template-columns:${mobile ? '1fr' : '1fr 1fr'};gap:14px}.box{background:white;border:1px solid #dce6ef;border-radius:15px;padding:${mobile ? 19 : 25}px;min-width:0}
      small{font-size:${mobile ? 12 : 14}px;color:#22668b;font-weight:700;letter-spacing:.8px}h2{font-size:${mobile ? 24 : 29}px;line-height:1.2;margin:10px 0;overflow-wrap:anywhere}p{font-size:${mobile ? 17 : 21}px;line-height:1.43;margin:0}
      .note{margin-top:18px;border-left:4px solid #6485b6;background:#e0eaf6;padding:16px 18px;border-radius:0 10px 10px 0;font-size:${mobile ? 16 : 20}px;line-height:1.45}
      footer{margin-top:18px;color:#65778b;font-size:13px}
      </style><main class="canvas"><div class="badge">СХЕМА ПРИМЕРА</div><h1>${escape(d.title)}</h1><p class="subtitle">${escape(d.subtitle)}</p><div class="grid">${cards}</div><div class="note">${escape(d.note)}</div><footer>Концептуальная схема · не снимок интерфейса</footer></main></html>`;
    fs.writeFileSync(path.join(output, filename), html);
    manifest.push({ slug: d.slug, name: 'lifecycle', filename, mobile, width,
      alt: `${d.title}: ${d.steps.map(s => s[1]).join(' → ')}. ${d.note}`,
      caption: 'Схема состояний учебного примера. Это не снимок интерфейса Telegram или Google.' });
  }
}
fs.writeFileSync(path.join(output, 'manifest.json'), JSON.stringify(manifest, null, 2) + '\n');
