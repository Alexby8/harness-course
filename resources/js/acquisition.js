export const ANALYTICS_BLOCK_KEY = 'botgate_analytics_blocked';

export async function readAnalyticsConsent(endpoint, version) {
    const response = await fetch(endpoint, {
        credentials: 'same-origin', cache: 'no-store', headers: { Accept: 'application/json' },
    });
    if (!response.ok) throw new Error('Consent unavailable');
    const { consent } = await response.json();
    if (consent === null) return null;
    if (!consent || !['accepted', 'declined'].includes(consent.choice)
        || typeof consent.version !== 'string' || typeof consent.id !== 'string'
        || !Number.isFinite(consent.expiresAt) || consent.expiresAt <= Date.now()
        || (consent.choice === 'accepted' && consent.version !== version)) {
        throw new Error('Invalid consent response');
    }
    return consent;
}

export function initAcquisition() {
    const root = document.querySelector('[data-acquisition]');
    if (!root) return null;
    const field = document.querySelector('[data-acquisition-allowed]');
    const publicControls = document.querySelector('[data-cookie-consent]');
    let allowed = false;
    let captured = false;
    let captureSucceeded = false;
    let clientId = null;
    let revision = 0;
    let pending = Promise.resolve();

    function locallyBlocked() {
        try { return localStorage.getItem(ANALYTICS_BLOCK_KEY) === '1'; } catch { return false; }
    }

    async function send(method, body) {
        const response = await fetch(root.dataset.endpoint, {
            method, credentials: 'same-origin', cache: 'no-store', keepalive: true,
            headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': root.dataset.csrf },
            ...(body ? { body: JSON.stringify(body) } : {}),
        });
        if (!response.ok) throw new Error('Attribution unavailable');
        return response.json();
    }

    function clear(withdrawal = false) {
        allowed = false;
        captured = false;
        captureSucceeded = false;
        clientId = null;
        ++revision;
        if (field) field.value = '0';
        // Serialize after pending captures so an older Set-Cookie cannot undo this deletion.
        pending = pending.catch(() => {}).then(() => send('DELETE', { withdrawal }))
            .catch(() => { /* Server refusal and the registration flag still prevent attachment. */ });
    }

    function enqueue(body, isCapture = false) {
        const current = revision;
        pending = pending.catch(() => {}).then(async () => {
            if (!allowed || locallyBlocked() || current !== revision) return;
            const result = await send('POST', { ...body, analytics_allowed: true });
            if (!allowed || locallyBlocked() || current !== revision) return;
            if (!result.stored) { clear(); return; }
            if (isCapture) captureSucceeded = true;
            if (field) field.value = '1';
        }).catch(() => {
            if (isCapture) captured = false;
            if (field) field.value = '0';
        });
    }

    function update(value) {
        if (!value || locallyBlocked()) {
            clear();
            return;
        }
        allowed = true;
        if (field) field.value = '1';
        if (!captured && root.dataset.capture === '1') {
            captured = true;
            const query = new URLSearchParams(location.search);
            const parameters = {};
            for (const key of JSON.parse(root.dataset.parameters)) {
                if (query.getAll(key).length === 1 && query.get(key)) parameters[key] = query.get(key);
            }
            let referrerHost = null;
            try { referrerHost = new URL(document.referrer).hostname; } catch { /* No referrer. */ }
            enqueue({ parameters, landing_path: location.pathname, referrer_host: referrerHost }, true);
        }
    }

    function setClientId(counter) {
        if (!allowed || locallyBlocked()) return;
        try {
            const value = counter.getClientID();
            if (typeof value === 'string' && /^[0-9]{1,64}$/.test(value) && value !== clientId) {
                clientId = value;
                enqueue({ client_id: value });
            }
        } catch { /* A missing or blocked counter must never break registration. */ }
    }

    // An old form restored from browser history must recheck the current decision.
    async function refresh() {
        if (field) field.value = '0';
        captured = false;
        captureSucceeded = false;
        const current = ++revision;
        if (locallyBlocked()) { clear(true); return; }
        try {
            const consent = await readAnalyticsConsent(root.dataset.consentEndpoint, root.dataset.version);
            if (current === revision) {
                if (consent?.choice === 'declined') clear(true);
                else update(true);
            }
        } catch {
            if (current === revision) clear();
        }
    }

    if (field) field.form.addEventListener('submit', async (event) => {
        event.preventDefault();
        field.value = '0';
        // Finish our own cookie write, but do not hold registration for an unavailable service.
        let finished = false;
        await Promise.race([
            pending.then(() => { finished = true; }),
            new Promise((resolve) => setTimeout(resolve, 2000)),
        ]);
        field.value = finished && allowed && !locallyBlocked() && captureSucceeded ? '1' : '0';
        HTMLFormElement.prototype.submit.call(field.form);
    });

    window.addEventListener('storage', (event) => {
        if (event.key === ANALYTICS_BLOCK_KEY && event.newValue === '1') clear(true);
        else if (event.key === ANALYTICS_BLOCK_KEY && !publicControls) refresh();
    });
    if (!publicControls) {
        try {
            const channel = new BroadcastChannel('botgate-cookie-consent');
            channel.onmessage = (event) => event.data === 'decline' ? clear(true) : refresh();
        } catch { /* Optional tab sync. */ }
        window.addEventListener('pageshow', (event) => { if (event.persisted) refresh(); });
        document.addEventListener('visibilitychange', () => { if (!document.hidden) refresh(); });
        refresh();
    }

    return { update, clear, setClientId };
}
