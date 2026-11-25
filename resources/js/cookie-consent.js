import { ANALYTICS_BLOCK_KEY as BLOCK_KEY, readAnalyticsConsent } from './acquisition';
export const METRIKA_GOAL_SEEN_KEY = 'botgate_metrika_goal_events';

export function clearMetrikaStorage() {
    // Only first-party Metrika data: never clear the session or all browser storage.
    const domains = [null];
    const parts = location.hostname.split('.');
    for (let i = 0; i < parts.length - 1; i++) domains.push(parts.slice(i).join('.'));
    for (const cookie of document.cookie.split(';')) {
        const name = cookie.split('=')[0].trim();
        if (!name.startsWith('_ym')) continue;
        for (const domain of domains) {
            document.cookie = `${name}=; Max-Age=0; Path=/; SameSite=Lax${domain ? `; Domain=${domain}` : ''}`;
        }
    }
    for (const storageName of ['localStorage', 'sessionStorage']) {
        try {
            const storage = window[storageName];
            for (const key of Object.keys(storage)) {
                if (key.startsWith('_ym') || key === METRIKA_GOAL_SEEN_KEY) storage.removeItem(key);
            }
        } catch { /* A storage restriction must not enable analytics. */ }
    }
}

export function initCookieConsent(acquisition = null) {
    const root = document.querySelector('[data-cookie-consent]');
    if (!root) return;

    const banner = root.querySelector('[data-cookie-banner]');
    const open = root.querySelector('[data-cookie-open]');
    const accept = root.querySelector('[data-cookie-accept]');
    const decline = root.querySelector('[data-cookie-decline]');
    const settings = root.querySelector('[data-cookie-settings]');
    const settingsPanel = root.querySelector('[data-cookie-settings-panel]');
    const dismiss = root.querySelector('[data-cookie-dismiss]');
    const status = root.querySelector('[data-cookie-status]');
    const error = root.querySelector('[data-cookie-error]');
    const counterId = Number(root.dataset.metrikaId);
    let consent = null;
    let counter = null;
    let loading = null;
    let busy = false;
    let reviewing = false;
    let dismissed = false;
    let revision = 0;
    let expiryTimer;
    let blocked = false;
    let ready = false;
    let channel;

    try { blocked = localStorage.getItem(BLOCK_KEY) === '1'; } catch { /* Storage can be disabled. */ }
    try { channel = new BroadcastChannel('botgate-cookie-consent'); } catch { /* Optional tab sync. */ }

    function show(visible) {
        banner.hidden = !visible;
        open.setAttribute('aria-expanded', String(visible));
        status.textContent = !ready ? 'Не удалось проверить настройку. Метрика и рекламная атрибуция отключены.'
            : allowed() ? 'Метрика и рекламная атрибуция разрешены.' : 'Метрика и рекламная атрибуция отключены.';
    }

    function showSettings(visible) {
        settingsPanel.hidden = !visible;
        settings.setAttribute('aria-expanded', String(visible));
    }

    function stop() {
        acquisition?.clear();
        clearTimeout(expiryTimer);
        try { counter?.destruct(); } catch { /* Cleanup must also run if a third-party script fails. */ }
        counter = null;
        clearMetrikaStorage();
    }

    function allowed() {
        // No choice enables the default counter, but is never recorded as consent.
        return ready && !blocked && consent?.choice !== 'declined';
    }

    function start() {
        acquisition?.update(allowed());
        if (!counterId || !allowed() || counter) return;
        if (!loading) {
            loading = new Promise((resolve, reject) => {
                const script = document.createElement('script');
                script.async = true;
                script.src = 'https://mc.yandex.ru/metrika/tag.js';
                script.onload = resolve;
                script.onerror = () => { script.remove(); loading = null; reject(new Error('Metrika unavailable')); };
                document.head.append(script);
            });
        }
        loading.then(() => {
            // An in-flight download must not initialize after a withdrawal.
            if (!allowed() || counter || !window.Ya?.Metrika2) return;
            counter = new window.Ya.Metrika2({
                id: counterId,
                webvisor: false,
                clickmap: false,
                trackLinks: false,
                accurateTrackBounce: true,
                // Use the counter's standard URL/referrer handling for advertising attribution.
                // This module is mounted only on public pages.
            });
            acquisition?.setClientId(counter);
            // ClientID may become available shortly after counter initialization.
            for (const delay of [100, 500, 1500]) {
                setTimeout(() => { if (allowed() && counter) acquisition?.setClientId(counter); }, delay);
            }
        }).catch(() => { /* Ad blockers must not affect the website. */ });
    }

    function scheduleExpiry() {
        clearTimeout(expiryTimer);
        if (!consent) return;
        expiryTimer = setTimeout(() => {
            if (consent.expiresAt <= Date.now()) {
                refresh();
            } else {
                scheduleExpiry();
            }
        }, Math.min(Math.max(consent.expiresAt - Date.now(), 1), 2147483647));
    }

    async function read() {
        return readAnalyticsConsent(root.dataset.endpoint, root.dataset.version);
    }

    async function refresh() {
        if (busy) return;
        try { if (localStorage.getItem(BLOCK_KEY) === '1') blocked = true; } catch { /* Keep the in-memory choice. */ }
        const current = ++revision;
        try {
            const value = await read();
            if (current !== revision) return;
            consent = value;
            ready = true;
            if (consent?.choice === 'declined') block();
            if (allowed()) start(); else stop();
            scheduleExpiry();
            show(reviewing || (!dismissed && !consent && !blocked));
        } catch {
            if (current !== revision) return;
            consent = null;
            ready = false;
            stop();
            show(reviewing || (!dismissed && !blocked));
        }
    }

    function block() {
        acquisition?.clear(true);
        ++revision;
        blocked = true;
        try { localStorage.setItem(BLOCK_KEY, '1'); } catch { /* Keep the in-memory refusal. */ }
        stop();
    }

    async function choose(choice) {
        if (busy) return;
        busy = true;
        ++revision;
        accept.disabled = decline.disabled = true;
        error.hidden = true;
        if (choice === 'declined') {
            block();
            channel?.postMessage('decline');
        }
        const decisionRevision = revision;
        try {
            const response = await fetch(root.dataset.endpoint, {
                method: 'POST', credentials: 'same-origin', cache: 'no-store',
                headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': root.dataset.csrf },
                body: JSON.stringify({ choice, version: root.dataset.version }),
            });
            if (!response.ok) throw new Error('Choice not saved');
            const data = await response.json();
            // Verify that the browser retained the server cookie before enabling analytics.
            const saved = await read();
            if (decisionRevision !== revision) return;
            if (!saved || saved.id !== data.consent.id) throw new Error('Cookies unavailable');
            consent = saved;
            ready = true;
            blocked = saved.choice === 'declined';
            try {
                if (blocked) localStorage.setItem(BLOCK_KEY, '1');
                else localStorage.removeItem(BLOCK_KEY);
            } catch { /* Keep the in-memory choice if storage is disabled. */ }
            if (allowed()) start(); else stop();
            scheduleExpiry();
            reviewing = false;
            show(false);
            open.focus({ preventScroll: true });
            channel?.postMessage('refresh');
        } catch {
            if (decisionRevision !== revision) return;
            block();
            stop();
            error.textContent = 'Не удалось сохранить выбор. Метрика и рекламная атрибуция отключены. Обновите страницу и попробуйте ещё раз.';
            error.hidden = false;
            show(true);
        } finally {
            busy = false;
            accept.disabled = decline.disabled = false;
        }
    }

    open.hidden = false;
    open.addEventListener('click', () => {
        reviewing = true;
        dismissed = false;
        showSettings(false);
        show(true);
        accept.focus({ preventScroll: true });
    });
    settings.addEventListener('click', () => {
        reviewing = true;
        const expanded = settingsPanel.hidden;
        showSettings(expanded);
        show(true);
        if (expanded) decline.focus({ preventScroll: true });
    });
    dismiss.addEventListener('click', () => {
        reviewing = false;
        dismissed = true;
        showSettings(false);
        show(false);
        open.focus({ preventScroll: true });
    });
    accept.addEventListener('click', () => choose('accepted'));
    decline.addEventListener('click', () => choose('declined'));
    if (channel) channel.onmessage = (event) => {
        if (event.data === 'decline') { block(); show(false); }
        else if (event.data === 'refresh') { blocked = false; refresh(); }
    };
    window.addEventListener('storage', (event) => {
        if (event.key !== BLOCK_KEY) return;
        if (event.newValue === '1') { block(); show(false); }
        else { blocked = false; refresh(); }
    });
    // Refresh saved/restored tabs, including a page returned from the back-forward cache.
    window.addEventListener('pageshow', (event) => { if (event.persisted) refresh(); });
    document.addEventListener('visibilitychange', () => { if (!document.hidden) refresh(); });
    refresh();
}
