// Uses only a disposable localhost server/database and a synthetic Metrika counter.
import assert from 'node:assert/strict';
import { createRequire } from 'node:module';
import { execFileSync } from 'node:child_process';

const require = createRequire(import.meta.url);
const { chromium } = require(process.env.PLAYWRIGHT_MODULE || 'playwright');
const base = process.env.COOKIE_TEST_URL || 'http://127.0.0.1:8766';
const database = process.env.ACQUISITION_TEST_DATABASE;
assert(['localhost', '127.0.0.1'].includes(new URL(base).hostname));
assert(database && database.replaceAll('\\', '/').includes('/storage/app/acquisition-qa/'));
const browser = await chromium.launch({ headless: true, channel: 'msedge' });
let checks = 0;
const acquisitionCookies = async (ctx) => (await ctx.cookies()).filter((c) => c.name.startsWith('botgate_acquisition_'));
const rows = (email) => JSON.parse(execFileSync(process.env.PHP85_BINARY || 'C:/WorkSpace/php/php-8.5.10/php.exe', [
    '-r', '$db=new PDO("sqlite:".$argv[1]); $q=$db->prepare("SELECT a.* FROM user_acquisitions a JOIN users u ON u.id=a.user_id WHERE u.email=?"); $q->execute([$argv[2]]); echo json_encode($q->fetchAll(PDO::FETCH_ASSOC));',
    database, email,
], { encoding: 'utf8' }));

async function context(blockCounter = false) {
    const ctx = await browser.newContext();
    await ctx.route('**/*', async (route) => {
        const url = new URL(route.request().url());
        if (url.hostname === 'mc.yandex.ru') {
            if (blockCounter) return route.abort();
            return route.fulfill({ contentType: 'text/javascript', body: `window.Ya={Metrika2:class {
                getClientID(){return '12345678901234567890'} hit(){} reachGoal(g,p,callback){callback?.()} destruct(){} }};` });
        }
        if (url.origin !== new URL(base).origin) return route.abort();
        if (route.request().isNavigationRequest() && ['/', '/docs'].includes(url.pathname)) {
            const response = await route.fetch();
            return route.fulfill({ response, body: (await response.text()).replace('data-metrika-id=""', 'data-metrika-id="123456"') });
        }
        return route.continue();
    });
    return ctx;
}

async function signup(page, label) {
    const email = `${label}-${Date.now()}@example.test`;
    await page.goto(`${base}/register`);
    await page.locator('[data-acquisition-allowed][value="1"]').waitFor({ state: 'attached', timeout: 5000 }).catch(() => {});
    await page.locator('[name=email]').fill(email);
    await page.locator('[name=password]').fill('password123');
    await page.locator('[name=password_confirmation]').fill('password123');
    await page.locator('[name=terms]').check();
    await Promise.all([page.waitForURL('**/email/verify'), page.getByRole('button', { name: 'Создать аккаунт' }).click()]);
    return email;
}

async function decline(page) {
    await page.locator('[data-cookie-settings]').click();
    await page.locator('[data-cookie-decline]').click();
    await page.waitForFunction(() => document.querySelector('[data-cookie-banner]').hidden);
}

try {
    const ctx = await context();
    const page = await ctx.newPage();
    await Promise.all([
        page.waitForResponse((r) => r.url().endsWith('/acquisition') && r.request().postDataJSON()?.client_id),
        page.goto(`${base}/?utm_source=yandex&utm_medium=cpc&utm_campaign=123&utm_content=456&utm_term=${encodeURIComponent('телеграм бот')}&yclid=12345678901234567890&group_id=7`),
    ]);
    assert.equal((await acquisitionCookies(ctx)).length, 3);
    assert((await acquisitionCookies(ctx)).every((c) => c.httpOnly && c.sameSite === 'Lax'));
    await Promise.all([
        page.waitForResponse((r) => r.url().endsWith('/acquisition') && r.request().postDataJSON()?.parameters?.utm_source === 'telegram'),
        page.goto(`${base}/docs?utm_source=telegram`),
    ]);
    const email = await signup(page, 'normal');
    const row = rows(email)[0];
    assert.equal(JSON.parse(row.first_touch).parameters.yclid, '12345678901234567890');
    assert.equal(JSON.parse(row.first_touch).parameters.utm_term, 'телеграм бот');
    assert.deepEqual(JSON.parse(row.last_tagged_touch).parameters, { utm_source: 'telegram' });
    assert.equal(row.metrika_client_id, '12345678901234567890');
    assert.equal((await acquisitionCookies(ctx)).length, 0); checks++;
    await page.goto(base);
    await page.locator('[data-cookie-banner]').waitFor({ state: 'visible' });
    await decline(page);
    assert.deepEqual(rows(email), []); checks++;
    await ctx.close();

    const adBlocked = await context(true);
    const adPage = await adBlocked.newPage();
    await Promise.all([
        adPage.waitForResponse((r) => r.url().endsWith('/acquisition') && r.request().method() === 'POST'),
        adPage.goto(`${base}/?yclid=888&utm_source=yandex`),
    ]);
    const blockedEmail = await signup(adPage, 'adblock');
    assert.equal(rows(blockedEmail)[0].metrika_client_id, null);
    assert.equal(JSON.parse(rows(blockedEmail)[0].first_touch).parameters.yclid, '888'); checks++;
    await adBlocked.close();

    const refusal = await context();
    const refusalPage = await refusal.newPage();
    await Promise.all([
        refusalPage.waitForResponse((r) => r.url().endsWith('/acquisition') && r.request().method() === 'POST'),
        refusalPage.goto(`${base}/?yclid=999`),
    ]);
    await decline(refusalPage);
    await refusalPage.waitForTimeout(300);
    assert.equal((await acquisitionCookies(refusal)).length, 0);
    const refusedEmail = await signup(refusalPage, 'refused');
    assert.deepEqual(rows(refusedEmail), []); checks++;
    await refusal.close();

    const local = await context();
    const localPage = await local.newPage();
    await Promise.all([
        localPage.waitForResponse((r) => r.url().endsWith('/acquisition') && r.request().method() === 'POST'),
        localPage.goto(`${base}/?yclid=777`),
    ]);
    await localPage.evaluate(() => localStorage.setItem('botgate_analytics_blocked', '1'));
    // Simulate a failed attempt to persist refusal: old encrypted cookies remain.
    await local.route('**/acquisition', (route) => route.request().method() === 'DELETE' ? route.fulfill({ status: 500, body: '{}' }) : route.continue());
    const localEmail = await signup(localPage, 'local-refusal');
    assert.deepEqual(rows(localEmail), []); checks++;
    await local.close();

    const failure = await context();
    const failurePage = await failure.newPage();
    await Promise.all([
        failurePage.waitForResponse((r) => r.url().endsWith('/acquisition') && r.request().method() === 'POST'),
        failurePage.goto(`${base}/?yclid=666`),
    ]);
    await failure.route('**/cookie-consent', (route) => route.fulfill({ status: 500, body: '{}' }));
    const failureEmail = await signup(failurePage, 'check-failed');
    assert.deepEqual(rows(failureEmail), []); checks++;
    await failure.close();

    const csrfContext = await context();
    for (const method of ['post', 'delete']) {
        const response = await csrfContext.request[method](`${base}/acquisition`, {
            headers: { 'X-CSRF-TOKEN': 'wrong', Accept: 'application/json' },
            data: { analytics_allowed: true, withdrawal: true },
        });
        assert.equal(response.status(), 419);
    }
    await csrfContext.close(); checks++;
    console.log(`Acquisition browser checks passed: ${checks}`);
} finally {
    await browser.close();
}
