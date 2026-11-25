export function initGuideView() {
    const article = document.querySelector('[data-guide-view]');
    if (!article) {
        return;
    }

    function recordWhenVisible() {
        if (document.visibilityState !== 'visible') {
            return;
        }

        document.removeEventListener('visibilitychange', recordWhenVisible);
        fetch(article.dataset.guideView, {
            method: 'POST',
            credentials: 'same-origin',
            referrerPolicy: 'no-referrer',
            headers: { 'X-CSRF-TOKEN': article.dataset.csrf },
            keepalive: true,
        }).catch(() => {});
    }

    document.addEventListener('visibilitychange', recordWhenVisible);
    recordWhenVisible();
}
