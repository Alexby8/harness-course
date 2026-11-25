import { ANALYTICS_BLOCK_KEY, readAnalyticsConsent } from './acquisition';
import { clearMetrikaStorage, METRIKA_GOAL_SEEN_KEY as SEEN_KEY } from './cookie-consent';

const PATHS = { register: '/email/verify', register_final: '/dashboard', bot_create: '/bots/created' };

export function initMetrikaGoal() {
    const root = document.querySelector('[data-metrika-goal]');
    if (!root || PATHS[root.dataset.goal] !== root.dataset.path) return;
    const id = Number(root.dataset.metrikaId);
    if (!Number.isSafeInteger(id) || id <= 0) return;
    let counter = null;
    let loading = null;
    let sent = false;
    let discarded = false;
    let revision = 0;
    let expiryTimer;
    let shutdownTimer;

    function blocked() {
        try { return localStorage.getItem(ANALYTICS_BLOCK_KEY) === '1'; } catch { return false; }
    }

    function seen() {
        try {
            const value = JSON.parse(sessionStorage.getItem(SEEN_KEY) || '[]');
            return Array.isArray(value) ? value.filter((item) => typeof item === 'string').slice(-100) : [];
        } catch { return []; }
    }

    function destroyCounter() {
        clearTimeout(shutdownTimer);
        try { counter?.destruct(); } catch { /* Analytics must not affect the page. */ }
        counter = null;
    }

    function stop(withdrawal = false) {
        ++revision;
        discarded = true;
        clearTimeout(expiryTimer);
        destroyCounter();
        if (withdrawal) clearMetrikaStorage();
    }

    async function start(current) {
        if (sent || discarded || seen().includes(root.dataset.eventId)) return;
        if (!loading) loading = new Promise((resolve, reject) => {
            const script = document.createElement('script');
            script.src = 'https://mc.yandex.ru/metrika/tag.js';
            script.async = true;
            // Even the script download must not receive a private page as its referrer.
            script.referrerPolicy = 'no-referrer';
            script.onload = resolve;
            script.onerror = reject;
            document.head.append(script);
        });
        await loading;
        if (current !== revision || blocked() || discarded || sent || !window.Ya?.Metrika2) return;
        const url = location.origin + root.dataset.path;
        const referrer = location.origin + '/';
        counter = new window.Ya.Metrika2({
            id, defer: true, url, referrer, sendTitle: false,
            webvisor: false, clickmap: false, trackLinks: false, trackHash: false,
            accurateTrackBounce: false, ecommerce: false, disableYtm: true,
        });
        // hit sets the URL used by subsequent reachGoal calls, including their page-ref.
        // These virtual pageviews never contain query strings, account/bot IDs or private titles.
        counter.hit(url, { title: 'BotGate', referer: referrer })?.catch?.(() => {});
        if (current !== revision || blocked() || discarded) { stop(blocked()); return; }
        sent = true;
        try { sessionStorage.setItem(SEEN_KEY, JSON.stringify([...seen(), root.dataset.eventId].slice(-100))); } catch { /* DOM state still deduplicates. */ }
        shutdownTimer = setTimeout(destroyCounter, 10000);
        counter.reachGoal(root.dataset.goal, {}, destroyCounter)?.catch?.(() => {});
    }

    async function refresh() {
        if (discarded) return;
        const current = ++revision;
        if (blocked()) { stop(true); return; }
        try {
            const consent = await readAnalyticsConsent(root.dataset.consentEndpoint, root.dataset.version);
            if (current !== revision) return;
            if (consent?.choice === 'declined') { stop(true); return; }
            clearTimeout(expiryTimer);
            if (consent) expiryTimer = setTimeout(refresh, Math.min(Math.max(consent.expiresAt - Date.now(), 1), 2147483647));
            await start(current);
        } catch { if (current === revision) stop(); }
    }

    window.addEventListener('storage', (event) => {
        if (event.key === ANALYTICS_BLOCK_KEY && event.newValue === '1') stop(true);
    });
    try {
        const channel = new BroadcastChannel('botgate-cookie-consent');
        channel.onmessage = (event) => event.data === 'decline' ? stop(true) : refresh();
    } catch { /* Optional tab synchronization. */ }
    window.addEventListener('pageshow', (event) => { if (event.persisted) refresh(); });
    document.addEventListener('visibilitychange', () => { if (!document.hidden) refresh(); });
    refresh();
}
