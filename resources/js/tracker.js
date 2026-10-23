/*
 | OpenRevise page-view counter. No cookies, nothing stored on the device,
 | no third party: it sends the page address, where the visitor came from,
 | the screen width and time zone, then how long the page was open.
 */
(() => {
    const d = document, n = navigator, b = d.body.dataset;
    if (n.doNotTrack === '1' || n.webdriver || location.protocol === 'file:') return;

    const key = Math.random().toString(36).slice(2, 10) + Date.now().toString(36).slice(-6);
    const url = '/_t';
    let shownFor = 0, shownAt = d.visibilityState === 'visible' ? Date.now() : 0, sent = false;

    const send = (data) => {
        const body = JSON.stringify({ k: key, ...data });
        if (!(n.sendBeacon && n.sendBeacon(url, body))) {
            fetch(url, { method: 'POST', body, keepalive: true, headers: { 'Content-Type': 'text/plain' } }).catch(() => {});
        }
    };

    send({
        p: location.pathname,
        r: d.referrer,
        w: screen.width,
        z: (Intl.DateTimeFormat().resolvedOptions().timeZone || ''),
        t: b.page || '',
        s: b.subject || '',
        o: b.board || '',
        l: b.lesson || '',
    });

    d.addEventListener('visibilitychange', () => {
        if (d.visibilityState === 'hidden') {
            if (shownAt) shownFor += Date.now() - shownAt;
            shownAt = 0;
            if (!sent && shownFor > 1000) {
                sent = true;
                send({ d: Math.min(shownFor, 36e5) });
            }
        } else {
            shownAt = Date.now();
        }
    });
})();
