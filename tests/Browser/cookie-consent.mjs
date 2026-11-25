// Run against an isolated local Laravel server, never a production URL.
// PLAYWRIGHT_MODULE may point to the bundled Playwright package; no npm install is needed.
import assert from 'node:assert/strict';
import { createRequire } from 'node:module';
import { mkdir } from 'node:fs/promises';

const require = createRequire(import.meta.url);
const { chromium } = require(process.env.PLAYWRIGHT_MODULE || 'playwright');
const base = process.env.COOKIE_TEST_URL || 'http://127.0.0.1:8765';
assert(['127.0.0.1', 'localhost'].includes(new URL(base).hostname), 'Only local test servers are allowed');
const output = process.env.COOKIE_TEST_OUTPUT || 'storage/app/cookie-qa';
await mkdir(output, { recursive: true });
const browser = await chromium.launch({ headless: true, channel: process.env.COOKIE_TEST_BROWSER || 'msedge' });
let checks = 0;
const metrika = `
window.__counterStarts = 0; window.__counterStops = 0;
window.Ya = { Metrika2: class {
  constructor(options) { window.__counterStarts++; window.__counterOptions = options;
    document.cookie = '_ym_uid=test; path=/'; localStorage.setItem('_ym_uid', 'test'); }
  destruct() { window.__counterStops++; }
} };`;

async function context(options = {}) {
    const ctx = await browser.newContext(options);
    let requests = 0;
    // Supply a synthetic counter only on public pages, without contacting Yandex.
    await ctx.route('**/*', async (route) => {
        const url = new URL(route.request().url());
        if (url.hostname === 'mc.yandex.ru') {
            requests++;
            return route.fulfill({ contentType: 'text/javascript', body: metrika });
        }
        if (url.origin !== new URL(base).origin) return route.abort();
        if (route.request().isNavigationRequest() && (['/', '/docs'].includes(url.pathname) || url.pathname.startsWith('/guides'))) {
            const response = await route.fetch();
            return route.fulfill({ response, body: (await response.text()).replace('data-metrika-id=""', 'data-metrika-id="123456"') });
        }
        return route.continue();
    });
    return { ctx, requests: () => requests };
}

async function choice(page, value) {
    if (value === 'decline' && !(await page.locator('[data-cookie-decline]').isVisible())) {
        await page.locator('[data-cookie-settings]').click();
    }
    await page.locator(`[data-cookie-${value}]`).click();
    await page.waitForFunction(() => document.querySelector('[data-cookie-banner]').hidden);
}

try {
    const { ctx, requests } = await context({ viewport: { width: 1440, height: 1000 } });
    const page = await ctx.newPage();
    let automaticWrites = 0;
    page.on('request', (request) => { if (request.url().endsWith('/cookie-consent') && request.method() === 'POST') automaticWrites++; });
    await page.goto(`${base}/?utm_source=yandex&utm_medium=cpc&yclid=123`);
    await page.locator('[data-cookie-banner]').waitFor({ state: 'visible' });
    await page.waitForFunction(() => window.__counterStarts === 1);
    assert.equal(requests(), 1);
    assert.equal(automaticWrites, 0);
    assert.equal((await ctx.request.get(`${base}/cookie-consent`)).status(), 200);
    assert.equal((await (await ctx.request.get(`${base}/cookie-consent`)).json()).consent, null);
    assert.equal(await page.locator('[data-cookie-close]').count(), 0);
    assert.equal(await page.evaluate(() => 'url' in window.__counterOptions || 'referrer' in window.__counterOptions), false);
    await page.keyboard.press('Escape');
    assert.equal(await page.locator('[data-cookie-banner]').isVisible(), true); checks++;
    await page.screenshot({ path: `${output}/desktop.png` });
    await page.locator('[data-cookie-settings]').click();
    assert.equal(await page.locator('[data-cookie-settings-panel]').isVisible(), true);
    assert.equal(await page.locator('[data-cookie-settings]').getAttribute('aria-expanded'), 'true');
    await page.locator('[data-cookie-settings]').click();
    assert.equal(await page.locator('[data-cookie-settings-panel]').isVisible(), false);
    assert.equal(await page.locator('[data-cookie-banner]').isVisible(), true);
    await page.locator('[data-cookie-settings]').click();
    await Promise.all([
        page.waitForResponse((r) => r.url().endsWith('/cookie-consent')),
        page.evaluate(() => document.dispatchEvent(new Event('visibilitychange'))),
    ]);
    assert.equal(await page.locator('[data-cookie-settings-panel]').isVisible(), true); checks++;
    await page.locator('[data-cookie-dismiss]').click();
    assert.equal(await page.locator('[data-cookie-banner]').isVisible(), false);
    assert.equal((await (await ctx.request.get(`${base}/cookie-consent`)).json()).consent, null);
    await Promise.all([
        page.waitForResponse((r) => r.url().endsWith('/cookie-consent')),
        page.evaluate(() => document.dispatchEvent(new Event('visibilitychange'))),
    ]);
    assert.equal(await page.locator('[data-cookie-banner]').isVisible(), false);
    await page.locator('[data-cookie-open]').click();
    assert.equal(await page.locator('[data-cookie-banner]').isVisible(), true);
    assert.equal(await page.locator('[data-cookie-settings-panel]').isVisible(), false); checks++;
    await choice(page, 'decline');
    await Promise.all([page.waitForResponse((r) => r.url().endsWith('/cookie-consent')), page.reload()]);
    assert.equal(await page.locator('[data-cookie-banner]').isVisible(), false);
    assert.equal(requests(), 1); checks++;

    await page.locator('[data-cookie-open]').click();
    assert.equal(await page.locator('[data-cookie-settings-panel]').isVisible(), false);
    await Promise.all([
        page.waitForResponse((response) => response.url().endsWith('/cookie-consent')),
        page.evaluate(() => document.dispatchEvent(new Event('visibilitychange'))),
    ]);
    assert.equal(await page.locator('[data-cookie-banner]').isVisible(), true); checks++;
    await choice(page, 'accept');
    await page.waitForFunction(() => window.__counterStarts === 1);
    assert.equal(requests(), 2);
    assert.equal(await page.evaluate(() => window.__counterOptions.webvisor), false); checks++;
    await page.locator('[data-cookie-open]').click();
    await choice(page, 'accept');
    assert.equal(requests(), 2);
    assert.equal(await page.evaluate(() => window.__counterStarts), 1); checks++;

    const second = await ctx.newPage();
    await second.goto(`${base}/docs`);
    await second.waitForFunction(() => window.__counterStarts === 1);
    await page.locator('[data-cookie-open]').click();
    await choice(page, 'decline');
    await second.waitForFunction(() => window.__counterStops === 1);
    assert.equal(await page.evaluate(() => window.__counterStops), 1);
    assert.equal(await page.evaluate(() => localStorage.getItem('_ym_uid')), null);
    assert.equal((await ctx.cookies()).some((cookie) => cookie.name === '_ym_uid'), false); checks++;
    await second.close();

    await page.locator('[data-cookie-open]').click();
    await choice(page, 'accept');
    const beforePrivate = requests();
    await page.goto(`${base}/login`);
    assert.equal(await page.locator('[data-cookie-consent]').count(), 0);
    assert.equal(requests(), beforePrivate); checks++;
    const csrfResponse = await ctx.request.post(`${base}/cookie-consent`, {
        headers: { 'X-CSRF-TOKEN': 'wrong', 'Accept': 'application/json' },
        data: { choice: 'accepted', version: '2026-10-30-goals' },
    });
    assert.equal(csrfResponse.status(), 419); checks++;
    await ctx.close();

    const mobile = await context({ viewport: { width: 375, height: 812 }, isMobile: true });
    const phone = await mobile.ctx.newPage();
    await phone.goto(base);
    await phone.locator('[data-cookie-banner]').waitFor({ state: 'visible' });
    const mobileBanner = await phone.locator('[data-cookie-banner]').boundingBox();
    assert(mobileBanner.x + mobileBanner.width <= 375 && mobileBanner.y + mobileBanner.height <= 813);
    await phone.screenshot({ path: `${output}/mobile.png` }); checks++;
    await phone.waitForFunction(() => window.__counterStarts === 1);
    // A failed refusal still stops analytics and survives a reload.
    await phone.route('**/cookie-consent', (route) => route.request().method() === 'POST'
        ? route.fulfill({ status: 500, contentType: 'application/json', body: '{}' }) : route.continue());
    await phone.locator('[data-cookie-settings]').click();
    await phone.screenshot({ path: `${output}/mobile-settings.png` });
    await phone.locator('[data-cookie-decline]').click();
    await phone.locator('[data-cookie-error]').waitFor({ state: 'visible' });
    assert.equal(await phone.evaluate(() => window.__counterStops), 1);
    await Promise.all([phone.waitForResponse((r) => r.url().endsWith('/cookie-consent')), phone.reload()]);
    assert.equal(mobile.requests(), 1); checks++;
    await mobile.ctx.close();

    const noJs = await context({ javaScriptEnabled: false });
    const staticPage = await noJs.ctx.newPage();
    await staticPage.goto(base);
    assert.equal(noJs.requests(), 0);
    assert.equal(await staticPage.locator('[data-cookie-banner]').isVisible(), false); checks++;
    await noJs.ctx.close();

    const race = await context();
    const racePage = await race.ctx.newPage();
    let pendingScript;
    await racePage.route('https://mc.yandex.ru/**', (route) => { pendingScript = route; });
    await racePage.goto(base);
    await racePage.locator('[data-cookie-banner]').waitFor({ state: 'visible' });
    await racePage.waitForFunction(() => !!document.querySelector('script[src*="mc.yandex.ru"]'));
    await choice(racePage, 'decline');
    assert(pendingScript);
    await pendingScript.fulfill({ contentType: 'text/javascript', body: metrika });
    await racePage.waitForFunction(() => !!window.Ya);
    assert.equal(await racePage.evaluate(() => window.__counterStarts), 0); checks++;
    await race.ctx.close();

    const unavailable = await context();
    const unavailablePage = await unavailable.ctx.newPage();
    await unavailablePage.route('**/cookie-consent', (route) => route.fulfill({ status: 500, body: '{}' }));
    await unavailablePage.goto(base);
    await unavailablePage.locator('[data-cookie-banner]').waitFor({ state: 'visible' });
    assert.equal(unavailable.requests(), 0); checks++;
    await unavailable.ctx.close();

    const malformed = await context();
    const malformedPage = await malformed.ctx.newPage();
    await malformedPage.route('**/cookie-consent', (route) => route.fulfill({ contentType: 'application/json', body: '{}' }));
    await malformedPage.goto(base);
    await malformedPage.locator('[data-cookie-banner]').waitFor({ state: 'visible' });
    assert.equal(malformed.requests(), 0); checks++;
    await malformed.ctx.close();

    const failedAccept = await context();
    const failedAcceptPage = await failedAccept.ctx.newPage();
    await failedAcceptPage.goto(base);
    await failedAcceptPage.waitForFunction(() => window.__counterStarts === 1);
    await failedAcceptPage.route('**/cookie-consent', (route) => route.request().method() === 'POST'
        ? route.fulfill({ status: 500, contentType: 'application/json', body: '{}' }) : route.continue());
    await failedAcceptPage.locator('[data-cookie-accept]').click();
    await failedAcceptPage.locator('[data-cookie-error]').waitFor({ state: 'visible' });
    assert.equal(await failedAcceptPage.evaluate(() => window.__counterStops), 1);
    assert.equal((await (await failedAccept.ctx.request.get(`${base}/cookie-consent`)).json()).consent, null); checks++;
    await failedAccept.ctx.close();

    // A successful POST body is insufficient if the browser has no saved cookie.
    const missingCookie = await context();
    const missingCookiePage = await missingCookie.ctx.newPage();
    await missingCookiePage.goto(base);
    await missingCookiePage.waitForFunction(() => window.__counterStarts === 1);
    await missingCookiePage.route('**/cookie-consent', (route) => route.request().method() === 'POST'
        ? route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify({ consent: {
            id: '5309e964-1911-458b-a42d-24cfeef21bc5', version: '2026-10-30-goals', choice: 'declined',
            expiresAt: Date.now() + 86400000,
        } }) }) : route.continue());
    await missingCookiePage.locator('[data-cookie-settings]').click();
    await missingCookiePage.locator('[data-cookie-decline]').click();
    await missingCookiePage.locator('[data-cookie-error]').waitFor({ state: 'visible' });
    assert.equal((await (await missingCookie.ctx.request.get(`${base}/cookie-consent`)).json()).consent, null);
    assert.equal(await missingCookiePage.evaluate(() => window.__counterStops), 1);
    await Promise.all([
        missingCookiePage.waitForResponse((r) => r.url().endsWith('/cookie-consent')),
        missingCookiePage.reload(),
    ]);
    assert.equal(missingCookie.requests(), 1); checks++;
    await missingCookie.ctx.close();

    const legacy = await context();
    const legacyPage = await legacy.ctx.newPage();
    await legacyPage.route('**/cookie-consent', (route) => route.fulfill({ contentType: 'application/json', body: JSON.stringify({
        consent: { id: 'legacy-refusal', choice: 'declined', version: '2026-09-26', expiresAt: Date.now() + 60000 },
    }) }));
    await legacyPage.goto(`${base}/guides/telegram-bot-api-proxy`);
    await legacyPage.waitForFunction(() => localStorage.getItem('botgate_analytics_blocked') === '1');
    assert.equal(legacy.requests(), 0);
    await legacyPage.unroute('**/cookie-consent');
    await Promise.all([legacyPage.waitForResponse((r) => r.url().endsWith('/cookie-consent')), legacyPage.reload()]);
    assert.equal(legacy.requests(), 0);
    await legacyPage.locator('[data-cookie-open]').click();
    await choice(legacyPage, 'accept');
    await legacyPage.waitForFunction(() => window.__counterStarts === 1); checks++;
    await legacy.ctx.close();
    console.log(`Cookie consent: ${checks} browser scenarios passed (synthetic Metrika, no external requests).`);
} finally {
    await browser.close();
}
