/*
 | OpenRevise - the small amount of JavaScript the public sites need:
 | remembered course choices, answer reveals, self-marking, the mock exam
 | timer and formula rendering. Pages work without it; it only adds to them.
 */

import Alpine from 'alpinejs';

const storage = {
    get(key, fallback) {
        try {
            const value = localStorage.getItem('or:' + key);
            return value === null ? fallback : JSON.parse(value);
        } catch {
            return fallback;
        }
    },
    set(key, value) {
        try {
            localStorage.setItem('or:' + key, JSON.stringify(value));
        } catch {
            // Private browsing: choices just are not remembered.
        }
    },
};

/* ---- Your course: board, tier and science course ------------------ */

Alpine.store('prefs', {
    board: storage.get('board', null),
    tier: storage.get('tier', null),       // 'foundation' | 'higher' | null
    course: storage.get('course', null),   // 'combined' | 'separate' | null

    set(key, value) {
        this[key] = this[key] === value ? null : value;
        storage.set(key, this[key]);
    },

    /** Should an item tagged with these data attributes be shown? */
    shows(data) {
        if (this.board && data.boards && !data.boards.split(',').includes(this.board)) return false;
        if (this.tier === 'foundation' && data.tier === 'higher') return false;
        if (this.course === 'combined' && data.course === 'separate') return false;
        return true;
    },

    label() {
        const parts = [];
        if (this.board) parts.push(this.board.toUpperCase());
        if (this.tier) parts.push(this.tier === 'higher' ? 'Higher' : 'Foundation');
        if (this.course) parts.push(this.course === 'separate' ? 'Separate' : 'Combined');
        return parts.length ? parts.join(' · ') : 'All boards';
    },
});

/* ---- Menu, saved lessons and recently viewed ------------------------ */

Alpine.store('ui', { menu: false });

/** Lessons saved with the bookmark button. Kept in this browser only. */
Alpine.store('saved', {
    items: storage.get('saved', []),

    has(url) {
        return this.items.some((i) => i.url === url);
    },

    toggle(item) {
        this.items = this.has(item.url) ? this.items.filter((i) => i.url !== item.url) : [{ ...item, at: Date.now() }, ...this.items];
        storage.set('saved', this.items);
    },

    remove(url) {
        this.items = this.items.filter((i) => i.url !== url);
        storage.set('saved', this.items);
    },
});

Alpine.store('recent', {
    items: storage.get('recent', []),

    add(item) {
        this.items = [item, ...this.items.filter((i) => i.url !== item.url)].slice(0, 6);
        storage.set('recent', this.items);
    },
});

/* ---- Search ----------------------------------------------------------- */

Alpine.data('siteSearch', (endpoint, page) => ({
    q: '',
    results: [],
    open: false,
    active: -1,
    loading: false,
    controller: null,

    async find() {
        const q = this.q.trim();
        if (q.length < 2) {
            this.results = [];
            this.open = false;
            return;
        }
        this.controller?.abort();
        this.controller = new AbortController();
        this.loading = true;
        try {
            const res = await fetch(endpoint + '?q=' + encodeURIComponent(q), { signal: this.controller.signal, headers: { Accept: 'application/json' } });
            this.results = (await res.json()).results || [];
            this.active = -1;
            this.open = true;
        } catch {
            // A newer search replaced this one.
        } finally {
            this.loading = false;
        }
    },

    move(step) {
        if (!this.results.length) return;
        this.open = true;
        this.active = (this.active + step + this.results.length) % this.results.length;
    },

    go(event) {
        if (this.active >= 0 && this.results[this.active]) {
            event.preventDefault();
            location.href = this.results[this.active].url;
        }
    },
}));

/* ---- Exam questions -------------------------------------------------- */

/**
 * A set of questions on one page. Answers and self-marks are kept in this
 * browser only, so a student can come back to them.
 */
Alpine.data('questionSet', (key, questions) => ({
    key,
    questions,
    state: storage.get('q:' + key, {}),

    q(id) {
        if (!this.state[id]) this.state[id] = { answer: '', choice: null, scheme: false, model: false, mark: null };
        return this.state[id];
    },

    save() {
        storage.set('q:' + this.key, this.state);
    },

    toggle(id, part) {
        this.q(id)[part] = !this.q(id)[part];
        this.save();
    },

    /** Multiple choice marks itself. */
    choose(id, option) {
        const question = this.questions.find((q) => q.id === id);
        const q = this.q(id);
        q.choice = option;
        if (question?.answer) q.mark = option === question.answer ? question.marks : 0;
        this.save();
    },

    setMark(id, mark) {
        const q = this.q(id);
        q.mark = q.mark === mark ? null : mark;
        this.save();
    },

    get scored() {
        return this.questions.reduce((sum, q) => sum + (this.state[q.id]?.mark ?? 0), 0);
    },

    get marked() {
        return this.questions.filter((q) => this.state[q.id]?.mark !== null && this.state[q.id]?.mark !== undefined).length;
    },

    get total() {
        return this.questions.reduce((sum, q) => sum + q.marks, 0);
    },

    reset() {
        if (!confirm('Clear your answers and marks on this page?')) return;
        this.state = {};
        this.save();
    },
}));

/* ---- Mock exams ------------------------------------------------------ */

Alpine.data('mockExam', (key, minutes, questions) => ({
    key,
    questions,
    minutes,
    stage: 'cover', // cover | sitting | marking
    timed: true,
    startedAt: null,
    now: Date.now(),
    ticker: null,
    state: {},

    init() {
        const saved = storage.get('mock:' + key, null);
        if (saved) {
            Object.assign(this, { stage: saved.stage, timed: saved.timed, startedAt: saved.startedAt, state: saved.state || {} });
            if (this.stage === 'sitting') this.tick();
        }
    },

    q(id) {
        if (!this.state[id]) this.state[id] = { answer: '', choice: null, mark: null, scheme: false, model: false };
        return this.state[id];
    },

    save() {
        storage.set('mock:' + this.key, { stage: this.stage, timed: this.timed, startedAt: this.startedAt, state: this.state });
    },

    start(timed) {
        this.timed = timed;
        this.stage = 'sitting';
        this.startedAt = Date.now();
        this.save();
        this.tick();
        window.scrollTo({ top: 0 });
    },

    tick() {
        clearInterval(this.ticker);
        this.ticker = setInterval(() => {
            this.now = Date.now();
            if (this.timed && this.remaining <= 0) {
                this.finish(true);
            }
        }, 1000);
    },

    get elapsed() {
        return this.startedAt ? Math.floor((this.now - this.startedAt) / 1000) : 0;
    },

    get remaining() {
        return Math.max(0, this.minutes * 60 - this.elapsed);
    },

    clock(seconds) {
        const h = Math.floor(seconds / 3600);
        const m = Math.floor((seconds % 3600) / 60);
        const s = seconds % 60;
        return (h ? h + ':' + String(m).padStart(2, '0') : m) + ':' + String(s).padStart(2, '0');
    },

    finish(timeUp = false) {
        if (!timeUp && !confirm('Finish the paper and see the mark scheme?')) return;
        clearInterval(this.ticker);
        // Multiple choice marks itself; the rest is marked with the mark scheme.
        this.questions.forEach((question) => {
            if (question.answer) {
                const q = this.q(question.id);
                q.mark = q.choice === question.answer ? question.marks : 0;
            }
        });
        this.stage = 'marking';
        this.save();
        window.scrollTo({ top: 0 });
    },

    restart() {
        if (!confirm('Start this paper again? Your answers will be cleared.')) return;
        this.stage = 'cover';
        this.state = {};
        this.startedAt = null;
        this.save();
        window.scrollTo({ top: 0 });
    },

    toggle(id, part) {
        this.q(id)[part] = !this.q(id)[part];
        this.save();
    },

    groupScore(ids) {
        return ids.reduce((sum, id) => sum + (this.markFor(id) ?? 0), 0);
    },

    choose(id, option) {
        if (this.stage !== 'sitting') return;
        this.q(id).choice = option;
        this.save();
    },

    setMark(id, mark) {
        const q = this.q(id);
        q.mark = q.mark === mark ? null : mark;
        this.save();
    },

    markFor(id) {
        const m = this.state[id]?.mark;
        return m === null || m === undefined ? null : m;
    },

    get scored() {
        return this.questions.reduce((sum, q) => sum + (this.markFor(q.id) ?? 0), 0);
    },

    get total() {
        return this.questions.reduce((sum, q) => sum + q.marks, 0);
    },

    get percent() {
        return this.total ? Math.round((this.scored / this.total) * 100) : 0;
    },
}));

/* ---- Report a mistake ----------------------------------------------- */

Alpine.data('reportForm', (endpoint, lessonId) => ({
    open: false,

    init() {
        window.addEventListener('open-report', () => {
            this.open = true;
            this.status = this.status === 'sent' ? 'sent' : 'idle';
            this.$nextTick(() => this.$refs.message?.focus());
        });
    },

    message: '',
    status: 'idle', // idle | sending | sent | error

    async send() {
        if (this.message.trim().length < 5) return;
        this.status = 'sending';
        try {
            const res = await fetch(endpoint, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
                body: JSON.stringify({ message: this.message, lesson_id: lessonId, page: location.href, website: '' }),
            });
            this.status = res.ok ? 'sent' : 'error';
            if (res.ok) this.message = '';
        } catch {
            this.status = 'error';
        }
    },
}));

// Links can pick the board with ?board=aqa (the landing page's board buttons do).
const boardParam = new URLSearchParams(location.search).get('board');
if (boardParam && /^[a-z0-9-]{2,30}$/.test(boardParam)) {
    Alpine.store('prefs').board = boardParam;
    storage.set('board', boardParam);
    history.replaceState(null, '', location.pathname + location.hash);
}

window.Alpine = Alpine;
Alpine.start();

/* ---- Formulas: KaTeX is only loaded on pages that have some ---------- */

function renderMaths(root = document) {
    const nodes = root.querySelectorAll('[data-tex]:not([data-rendered])');
    if (!nodes.length) return;

    Promise.all([import('katex'), import('katex/dist/katex.min.css')]).then(([{ default: katex }]) => {
        nodes.forEach((node) => {
            try {
                katex.render(node.dataset.tex, node, {
                    displayMode: node.tagName === 'DIV',
                    throwOnError: false,
                    output: 'htmlAndMathml',
                });
                node.dataset.rendered = '1';
            } catch {
                // The plain text stays visible.
            }
        });
    });
}

renderMaths();
window.renderMaths = renderMaths;

/* ---- "On this page": highlight the section being read --------------- */

const tocLinks = [...document.querySelectorAll('.toc a[href^="#"]')];
if (tocLinks.length && 'IntersectionObserver' in window) {
    const byId = new Map(tocLinks.map((a) => [decodeURIComponent(a.hash.slice(1)), a]));
    const visible = new Set();
    const observer = new IntersectionObserver((entries) => {
        entries.forEach((e) => (e.isIntersecting ? visible.add(e.target.id) : visible.delete(e.target.id)));
        const headings = [...byId.keys()].map((id) => document.getElementById(id)).filter(Boolean);
        const current = headings.find((h) => visible.has(h.id))
            || headings.filter((h) => h.getBoundingClientRect().top < 120).pop()
            || headings[0];
        tocLinks.forEach((a) => a.classList.toggle('is-active', byId.get(current?.id) === a));
    }, { rootMargin: '-80px 0px -60% 0px' });
    byId.forEach((_, id) => {
        const el = document.getElementById(id);
        if (el) observer.observe(el);
    });
}
