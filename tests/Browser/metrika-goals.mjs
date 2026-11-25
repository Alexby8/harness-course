// Exercise the built JS on a temporary localhost server; all Yandex requests are mocked.
import assert from 'node:assert/strict';
import { createRequire } from 'node:module';
import { readFile } from 'node:fs/promises';
import { createServer } from 'node:http';

const require = createRequire(import.meta.url);
const { chromium } = require(process.env.PLAYWRIGHT_MODULE || 'playwright');
const manifest = JSON.parse(await readFile(new URL('../../public/build/manifest.json', import.meta.url)));
const bundle = await readFile(new URL(`../../public/build/${manifest['resources/js/app.js'].file}`, import.meta.url));
const paths = { register: '/email/verify', register_final: '/dashboard', bot_create: '/bots/created' };
const eventId = 'ba658f22-98d2-49a0-8ee9-a7486a94b442';
const version = '2026-10-30-goals';
const server = createServer((req, res) => {
    if (req.url === '/app.js') {
        res.setHeader('Content-Type', 'text/javascript'); res.end(bundle); return;
    }
    res.setHeader('Content-Type', 'application/json'); res.end('{"consent":null}');
});
await new Promise((resolve) => server.listen(0, '127.0.0.1', resolve));
const base = `http://127.0.0.1:${server.address().port}`;
const browser = await chromium.launch({ headless: true, channel: 'msedge' });
let checks = 0;
const mock = `window.__hits=[];window.__goals=[];window.__options=[];window.Ya={Metrika2:class {
    constructor(o){window.__options.push(o);document.cookie='_ym_uid=synthetic; path=/'}
    hit(u,o){window.__hits.push([u,o]);if(window.__blockOnHit){localStorage.setItem('botgate_analytics_blocked','1');window.dispatchEvent(new StorageEvent('storage',{key:'botgate_analytics_blocked',newValue:'1'}))}}
    reachGoal(g,p,callback){window.__goals.push(g);callback?.()}
    destruct(){window.__stopped=true}
}};`;

async function scenario(goal = 'bot_create', options = {}) {
    const ctx = await browser.newContext();
    let downloads = 0;
    if (options.blocked) await ctx.addInitScript(() => localStorage.setItem('botgate_analytics_blocked', '1'));
    if (options.blockOnHit) await ctx.addInitScript(() => { window.__blockOnHit = true; });
    await ctx.route('**/*', async (route) => {
        const url = new URL(route.request().url());
        if (url.hostname === 'mc.yandex.ru') {
            downloads++;
            if (options.load) await options.load;
            if (options.adblock) return route.abort();
            return route.fulfill({ contentType: 'text/javascript', body: mock });
        }
        assert.equal(url.origin, base, 'No external requests are allowed');
        if (route.request().isNavigationRequest()) {
            const id = url.pathname.endsWith('/second') ? 'ce963eb6-334c-40b1-87de-724559039491' : eventId;
            return route.fulfill({ contentType: 'text/html', body: `<!doctype html><html><head>
                <title>Private Bot private@example.test</title></head><body><input value="private-token">
                <div hidden data-metrika-goal data-goal="${goal}" data-path="${paths[goal]}"
                data-event-id="${id}" data-metrika-id="${options.dev ? '' : '109789253'}"
                data-consent-endpoint="/cookie-consent" data-version="${version}"></div>
                <script type="module" src="/app.js"></script></body></html>` });
        }
        if (url.pathname === '/cookie-consent' && options.failure) return route.fulfill({ status: 500, body: '{}' });
        if (url.pathname === '/cookie-consent' && options.declined) return route.fulfill({ contentType: 'application/json', body: JSON.stringify({
            consent: { id: eventId, choice: 'declined', version: 'older', expiresAt: Date.now() + 60000 },
        }) });
        return route.continue();
    });
    const page = await ctx.newPage();
    const errors = [];
    page.on('pageerror', (error) => errors.push(error.message));
    await page.goto(`${base}/bots/private-bot-id?signature=private-signature&email=private@example.test`, { waitUntil: 'domcontentloaded' });
    return { ctx, page, errors, downloads: () => downloads };
}

try {
    for (const goal of Object.keys(paths)) {
        const test = await scenario(goal);
        await test.page.waitForFunction(() => window.__goals?.length === 1);
        assert.deepEqual(await test.page.evaluate(() => window.__goals), [goal]);
        const telemetry = await test.page.evaluate(() => ({ hits: window.__hits, options: window.__options }));
        assert.equal(telemetry.options[0].id, 109789253);
        assert.equal(telemetry.options[0].defer, true);
        assert.equal(telemetry.options[0].webvisor, false);
        assert.equal(telemetry.options[0].sendTitle, false);
        assert.deepEqual(telemetry.hits, [[base + paths[goal], { title: 'BotGate', referer: base + '/' }]]);
        assert.equal(telemetry.options[0].url, base + paths[goal]);
        assert.equal(telemetry.options[0].referrer, base + '/');
        assert.equal(await test.page.evaluate(() => window.__stopped), true);
        assert.equal((await test.ctx.cookies()).some((c) => c.name === '_ym_uid'), true, 'A successful goal must preserve ClientID cookies');
        for (const secret of ['private-bot-id', 'private@example.test', 'private-signature', 'private-token']) {
            assert(!JSON.stringify(telemetry).includes(secret));
        }
        await test.page.reload();
        await test.page.waitForTimeout(100);
        assert.equal(test.downloads(), 1, 'Reload must not replay the same event');
        await test.page.goto(`${base}/bots/second`);
        await test.page.waitForFunction(() => window.__goals?.length === 1);
        assert.equal(test.downloads(), 2, 'A different successful event may be tracked');
        assert.deepEqual(test.errors, []);
        await test.ctx.close(); checks++;
    }
    for (const options of [{ blocked: true }, { declined: true }, { failure: true }, { dev: true }]) {
        const test = await scenario('register', options);
        await test.page.waitForTimeout(200);
        assert.equal(test.downloads(), 0);
        assert.deepEqual(test.errors, []);
        await test.ctx.close(); checks++;
    }
    const adblock = await scenario('register', { adblock: true });
    await adblock.page.waitForTimeout(200);
    assert.deepEqual(adblock.errors, []);
    await adblock.ctx.close(); checks++;

    let release;
    const load = new Promise((resolve) => { release = resolve; });
    const delayed = await scenario('register_final', { load });
    for (let attempt = 0; delayed.downloads() === 0 && attempt < 100; attempt++) await delayed.page.waitForTimeout(20);
    assert.equal(delayed.downloads(), 1);
    await delayed.page.evaluate(() => {
        localStorage.setItem('botgate_analytics_blocked', '1');
        window.dispatchEvent(new StorageEvent('storage', { key: 'botgate_analytics_blocked', newValue: '1' }));
    });
    release();
    await delayed.page.waitForFunction(() => !!window.Ya);
    assert.deepEqual(await delayed.page.evaluate(() => window.__goals), []);
    assert.deepEqual(await delayed.page.evaluate(() => window.__options), []);
    await delayed.ctx.close(); checks++;

    const between = await scenario('bot_create', { blockOnHit: true });
    await between.page.waitForFunction(() => window.__stopped);
    assert.deepEqual(await between.page.evaluate(() => window.__goals), []);
    assert.equal((await between.ctx.cookies()).some((c) => c.name === '_ym_uid'), false);
    await between.ctx.close(); checks++;
    console.log(`Metrika goals: ${checks} browser scenarios passed (synthetic counter, no external requests).`);
} finally {
    await browser.close();
    await new Promise((resolve) => server.close(resolve));
}
