import {
    BarController,
    BarElement,
    CategoryScale,
    Chart,
    DoughnutController,
    Filler,
    LinearScale,
    LineController,
    LineElement,
    PointElement,
    Tooltip,
} from 'chart.js'

// Only the chart types this app actually draws are registered, which keeps the
// bundle small.
Chart.register(
    BarController,
    BarElement,
    LineController,
    LineElement,
    PointElement,
    DoughnutController,
    CategoryScale,
    LinearScale,
    Filler,
    Tooltip,
)

/**
 * Read a design token off the document so charts follow the user's theme —
 * including a custom palette — without any colour being written here.
 */
let token = (name, fallback = '#64748b') => {
    let value = getComputedStyle(document.documentElement).getPropertyValue(`--color-${name}`)

    return value.trim() || fallback
}

let baseOptions = () => ({
    responsive: true,
    maintainAspectRatio: false,
    interaction: { mode: 'index', intersect: false },
    plugins: {
        legend: { display: false },
        tooltip: {
            backgroundColor: token('card'),
            titleColor: token('foreground'),
            bodyColor: token('muted-foreground'),
            borderColor: token('border'),
            borderWidth: 1,
            padding: 10,
            cornerRadius: 8,
            displayColors: true,
            boxPadding: 4,
        },
    },
})

let axisOptions = (formatter) => ({
    x: {
        grid: { display: false },
        border: { color: token('hairline') },
        ticks: { color: token('muted-foreground'), font: { size: 11 }, maxRotation: 0, autoSkipPadding: 12 },
    },
    y: {
        beginAtZero: true,
        grid: { color: token('hairline'), drawTicks: false },
        border: { display: false },
        ticks: {
            color: token('muted-foreground'),
            font: { size: 11 },
            maxTicksLimit: 5,
            callback: (value) => formatter(value),
        },
    },
})

/*
 * Register the service worker, which is what lets a phone install the site as
 * an application rather than a bookmark.
 *
 * It caches the icons and the fingerprinted build assets and nothing else —
 * pages, and therefore anybody's figures, always come from the network. See
 * public/service-worker.js.
 *
 * A failure here is not worth a broken page: an unsupported browser or a plain
 * HTTP origin simply carries on without it.
 */
if ('serviceWorker' in navigator) {
    // Registered as the module runs rather than on `load`. Module scripts are
    // already deferred until the document is parsed, and hanging this off the
    // load event only made it depend on every image and font first.
    navigator.serviceWorker.register('/service-worker.js').then((registration) => {
        /*
        | Telling a tab that has been open for hours a new build exists.
        |
        | A deploy fingerprints new build assets under new filenames, so a
        | fresh navigation always gets the current ones — that half already
        | worked. What it does not cover is a tab nobody has navigated in
        | since: a PWA is usually opened once and left running, not
        | reloaded, and that tab has no reason to ever ask the network
        | "has anything changed?" on its own. It looked, from outside, like
        | some phones just never updated — they had, the running tab simply
        | never checked.
        |
        | `updatefound` fires the moment a byte-different service-worker.js
        | is seen; the reload prompt only appears once that worker finishes
        | installing while ANOTHER one is already controlling this page —
        | the condition that is true on a real update and false on the
        | very first visit, when there is nothing yet to compare against.
        */
        registration.addEventListener('updatefound', () => {
            let installing = registration.installing

            if (! installing) return

            installing.addEventListener('statechange', () => {
                if (installing.state === 'installed' && navigator.serviceWorker.controller) {
                    window.dispatchEvent(new CustomEvent('app-update-available'))
                }
            })
        })

        // Catches an update that finished installing while this tab's own
        // browser process was asleep — a phone backgrounds a PWA far more
        // readily than it closes one, and the check above only ever fires
        // for an install this specific page load witnessed happen.
        document.addEventListener('visibilitychange', () => {
            if (document.visibilityState === 'visible') registration.update().catch(() => {})
        })

        setInterval(() => registration.update().catch(() => {}), 30 * 60 * 1000)
    }).catch(() => {})
}

/**
 * A Livewire action that hits a genuine server error — a stale reference to
 * something deleted from under it, a session that expired mid-form, a 500 —
 * used to leave Livewire's own diagnostic overlay on screen: the raw
 * response (a plain "404 | Not Found" page, say) shown in a full-screen
 * iframe. That overlay is not one of this app's own modals, so nothing in
 * it — the sheet's own "X", the backdrop — was ever wired to close it,
 * which is exactly what "stuck, can't close the page" looked like from the
 * outside. A fresh load gets back to a working page; whatever was mid-typed
 * is lost either way once a request has actually failed like this.
 */
document.addEventListener('livewire:init', () => {
    Livewire.hook('request', ({ fail }) => {
        fail(({ status, preventDefault }) => {
            if (status === 0) return

            preventDefault()

            window.dispatchEvent(new CustomEvent('notify', {
                detail: {
                    message: document.body.dataset.requestFailedMessage || 'Something went wrong. Reloading…',
                    tone: 'danger',
                    timeout: 3000,
                },
            }))

            setTimeout(() => window.location.reload(), 1200)
        })
    })
})

document.addEventListener('alpine:init', () => {
    let Alpine = window.Alpine

    /**
     * A new build has finished installing behind the one this tab is
     * running. Reloading is left to the person, not done for them the
     * moment it is available — this is a page with money being typed into
     * it, and a form's contents disappearing out from under someone because
     * the app decided to refresh itself is a worse morning than seeing last
     * week's layout for a few minutes longer.
     */
    Alpine.store('appUpdate', {
        available: false,

        init() {
            window.addEventListener('app-update-available', () => {
                this.available = true
            })
        },

        reload() {
            window.location.reload()
        },
    })

    /**
     * Whether this browser can put the app on a home screen, and how to ask.
     *
     * Chrome and Edge hand over a prompt we can fire on a tap, so there one
     * tap is the whole install. Nothing else does: Safari never will, and
     * Chrome stops offering the moment the app is already installed — which
     * is indistinguishable, from script, from a browser that simply never
     * offered. So the button is always there and falls back to instructions,
     * because a hidden button is the one thing nobody can be walked through.
     *
     * The only case with genuinely nothing to say is a page already running
     * from its own icon.
     */
    Alpine.store('install', {
        /** The deferred beforeinstallprompt event, caught in the page head. */
        event: null,
        installed: false,
        ios: false,
        dismissed: false,

        init() {
            this.event = window.installPrompt?.event ?? null

            // Already on a home screen: the app is running from its own icon,
            // so there is nothing left to offer.
            this.installed = window.matchMedia('(display-mode: standalone)').matches
                || navigator.standalone === true

            this.ios = /iphone|ipad|ipod/i.test(navigator.userAgent)
                // An iPad reports itself as a Mac; touch points give it away.
                || (/macintosh/i.test(navigator.userAgent) && navigator.maxTouchPoints > 1)

            this.dismissed = this.remembered()

            // The event usually arrives after Alpine has started.
            window.addEventListener('install-available', () => {
                this.event = window.installPrompt.event
            })

            window.addEventListener('appinstalled', () => {
                this.event = null
                this.installed = true
            })
        },

        get offered() {
            return ! this.installed
        },

        /*
         * The banner asks unprompted, so it holds itself to a higher bar than
         * the menu entries: a real prompt to fire, or an iPhone, where the
         * Share menu is genuinely the only route and worth pointing out. A
         * browser that has gone quiet because the app is already installed
         * gets no banner, only the entry in the menu.
         */
        get bannerVisible() {
            return this.offered && ! this.dismissed && (this.event !== null || this.ios)
        },

        /**
         * Fire the browser's own install dialog, or fall back to instructions.
         */
        async start() {
            if (! this.event) {
                window.dispatchEvent(new CustomEvent('open-install-guide'))

                return
            }

            this.event.prompt()

            let { outcome } = await this.event.userChoice

            // The event is single-use whichever way it was answered.
            this.event = null
            window.installPrompt.event = null

            if (outcome === 'accepted') this.installed = true
        },

        /*
         * The banner is dismissed per browser rather than per account: which
         * device already has the icon is not something the server needs to
         * know, and the answer is different on every phone somebody signs in
         * from.
         */
        dismiss() {
            this.dismissed = true

            try {
                localStorage.setItem('install-dismissed', '1')
            } catch (e) {}
        },

        remembered() {
            try {
                return localStorage.getItem('install-dismissed') === '1'
            } catch (e) {
                return false
            }
        },
    })

    /**
     * Renders a chart and redraws it whenever Livewire sends new data or the
     * theme changes.
     *
     * Usage: <div x-data="chart({ type: 'bar', data: {...}, labels: [...] })">
     */
    Alpine.data('chart', (config) => ({
        instance: null,

        init() {
            this.$nextTick(() => this.render(config))

            // Livewire replaces the payload on the element's dataset when the
            // period filter changes.
            this.$watch('$el.dataset.chart', () => this.refresh())

            window.addEventListener('theme-changed', () => this.refresh())
        },

        refresh() {
            let payload = this.$el.dataset.chart

            if (! payload) return

            this.render(JSON.parse(payload))
        },

        render(spec) {
            let canvas = this.$refs.canvas

            if (! canvas) return

            this.instance?.destroy()

            this.instance = new Chart(canvas, {
                type: spec.type,
                data: this.datasets(spec),
                options: this.options(spec),
            })
        },

        /**
         * Canvas cannot read CSS custom properties, so a token reference such
         * as "var(--color-success)" is resolved to a real colour first. This is
         * what keeps charts in step with a custom theme.
         */
        resolve(color) {
            let match = /^var\(\s*(--[\w-]+)\s*\)$/.exec(color ?? '')

            if (! match) return color

            return getComputedStyle(document.documentElement).getPropertyValue(match[1]).trim() || '#64748b'
        },

        datasets(spec) {
            if (spec.type === 'doughnut') {
                return {
                    labels: spec.labels,
                    datasets: [{
                        data: spec.series[0].values,
                        backgroundColor: spec.series[0].colors.map((color) => this.resolve(color)),
                        borderColor: token('card'),
                        borderWidth: 2,
                        hoverOffset: 6,
                    }],
                }
            }

            return {
                labels: spec.labels,
                datasets: spec.series.map((series) => {
                    let color = this.resolve(series.color)

                    return {
                    label: series.label,
                    data: series.values,
                    backgroundColor: spec.type === 'bar'
                        ? this.fade(color, 1, 0.45)
                        : (series.fill ? this.fade(color, 0.3, 0) : color),
                    hoverBackgroundColor: spec.type === 'bar' ? color : undefined,
                    borderColor: color,
                    borderWidth: spec.type === 'line' ? 2 : 0,
                    borderRadius: spec.type === 'bar' ? 8 : 0,
                    maxBarThickness: 28,
                    fill: series.fill ?? false,
                    tension: 0.35,
                    // A line needs two readings to draw a segment. Looking at a
                    // single month would otherwise show an empty chart, so the
                    // lone reading is drawn as a point instead.
                    pointRadius: spec.type === 'line' && series.values.length < 2 ? 4 : 0,
                    pointHoverRadius: 4,
                    }
                }),
            }
        },

        options(spec) {
            let options = baseOptions()

            options.plugins.tooltip.callbacks = {
                label: (context) => {
                    let label = context.dataset.label ? `${context.dataset.label}: ` : ''

                    return label + this.formatFull(context.parsed.y ?? context.parsed)
                },
            }

            if (spec.type === 'doughnut') {
                options.cutout = '62%'

                return options
            }

            options.scales = axisOptions((value) => this.formatCompact(value))

            return options
        },

        /**
         * A vertical fade of one colour, built against the plot area.
         *
         * A bar painted in flat colour reads as a solid block; the same bar
         * fading towards its base sits back into the page and lets the axis
         * stay legible through it. Chart.js calls this per draw, and the plot
         * area does not exist on the very first one — returning the flat colour
         * then is correct, and the gradient replaces it a frame later.
         */
        fade(color, from, to) {
            return (context) => {
                let { ctx, chartArea } = context.chart

                if (! chartArea) return color

                let gradient = ctx.createLinearGradient(0, chartArea.top, 0, chartArea.bottom)

                gradient.addColorStop(0, this.alpha(color, from))
                gradient.addColorStop(1, this.alpha(color, to))

                return gradient
            }
        },

        /** A hex colour at a given opacity, e.g. alpha('#1f7a5a', 0.5). */
        alpha(color, opacity) {
            if (! color.startsWith('#') || color.length !== 7) return color

            return color + Math.round(Math.min(1, Math.max(0, opacity)) * 255)
                .toString(16)
                .padStart(2, '0')
        },

        translucent(color) {
            return this.alpha(color, 0.15)
        },

        /** Compact axis labels, e.g. "1,3 jt" — the symbol is added server-side. */
        formatCompact(value) {
            let absolute = Math.abs(value)
            let suffixes = JSON.parse(this.$el.dataset.suffixes ?? '{}')

            if (absolute >= 1_000_000_000) return this.number(value / 1_000_000_000, 1) + ' ' + (suffixes.billion ?? 'B')
            if (absolute >= 1_000_000) return this.number(value / 1_000_000, 1) + ' ' + (suffixes.million ?? 'M')
            if (absolute >= 1_000) return this.number(value / 1_000, 0) + ' ' + (suffixes.thousand ?? 'K')

            return this.number(value, 0)
        },

        formatFull(value) {
            return `${this.$el.dataset.symbol ?? ''} ${this.number(value, 0)}`.trim()
        },

        number(value, decimals) {
            return new Intl.NumberFormat(document.documentElement.lang || 'id', {
                minimumFractionDigits: decimals,
                maximumFractionDigits: decimals,
            }).format(value)
        },
    }))

    /**
     * A currency field that stays correctly grouped while it is being edited.
     *
     * Usage: <input x-data="moneyInput({ group: '.', decimal: ',', decimals: 0 })">
     *
     * Alpine's own $money mask deliberately leaves the value alone whenever a
     * single character was deleted — an escape hatch that lets you remove a
     * separator by hand, but which also meant backspacing through "3.000.000"
     * left the field reading "3.000.00". Here the whole value is rebuilt on
     * every keystroke, so the separators can never fall out of step with the
     * digits, and the caret is put back after the same digit it was left on.
     */
    Alpine.data('moneyInput', ({ group, decimal, decimals }) => ({
        init() {
            // A value that arrived from the server reads "Rp 1.250.000"; the
            // symbol belongs to the prefix beside the field, not inside it.
            this.regroup(false)
        },

        /** True for characters a person actually chose, as opposed to grouping. */
        typed(character) {
            return /[0-9]/.test(character) || character === decimal
        },

        regroup(keepCaret = true) {
            let el = this.$el
            let caret = el.selectionStart ?? el.value.length
            let before = [...el.value.slice(0, caret)].filter((character) => this.typed(character)).length
            let formatted = this.format(el.value)

            let regrouped = formatted !== el.value

            if (regrouped) el.value = formatted

            if (keepCaret) {
                let position = 0

                for (let seen = 0; position < formatted.length && seen < before; position++) {
                    if (this.typed(formatted[position])) seen++
                }

                el.setSelectionRange(position, position)
            }

            // Livewire reads whatever the input event carried, which is the
            // text the browser held *before* this ran — not the grouping put
            // right here. That only ever matched while the field held plain
            // digits: once a `wire:model.live` round-trip writes the server's
            // own grouped text back ("50.000"), the next digit typed onto it
            // makes "50.0000", which reaches the server as fifty rupiah with
            // a fraction while the field on screen reads "500.000". Telling
            // Livewire about the regrouped value keeps the two the same.
            // Re-entry stops on its own: the second pass finds nothing left
            // to regroup.
            if (regrouped) el.dispatchEvent(new Event('input', { bubbles: true }))
        },

        format(value) {
            let sign = value.trimStart().startsWith('-') ? '-' : ''
            let [whole, ...rest] = value.split(decimal)

            // Leading zeros are an artefact of deleting from the front, never
            // something anybody means to write.
            let digits = whole.replace(/\D/g, '').replace(/^0+(?=\d)/, '')
            let grouped = digits.replace(/\B(?=(\d{3})+(?!\d))/g, group)
            let fractional = decimals > 0 && rest.length > 0

            if (! fractional) return grouped === '' ? sign : sign + grouped

            let fraction = rest.join('').replace(/\D/g, '').slice(0, decimals)

            return sign + (grouped === '' ? '0' : grouped) + decimal + fraction
        },
    }))

    /**
     * The running total under a group of split fields (a budget or bill's
     * per-member amounts/percentages) — summed straight from the fields'
     * own DOM values on every keystroke, not from whatever the slowest of
     * their debounced `wire:model.live`s last confirmed to the server.
     *
     * Each field syncs to Livewire on its own 400ms debounce (see
     * x-money-input's own note on why it isn't truly live), so mid-typing
     * across several fields the server-computed total could be reading a
     * mix of some fields' new values and others' half-typed or not-yet-sent
     * ones — a total that matches nothing actually on screen. This reads
     * every field itself, in the browser, the instant any of them changes.
     *
     * The sentence itself stays server-translated — only the number inside
     * it (marked by `placeholder`) is swapped for the live figure — so
     * nothing here hardcodes copy in one language.
     *
     * Usage: <div x-data="allocationTotal({ group: '.', decimal: ',', decimals: 0, limitFieldId: 'form.amount', template: 'Total: __AMOUNT__', placeholder: '__AMOUNT__' })" x-on:input="recompute()">
     * — `limitFieldId` is the id of the field this total is checked
     * against; omit it (percentage mode) to check against a flat 100.
     */
    Alpine.data('allocationTotal', ({ group, decimal, decimals, limitFieldId, template, placeholder, symbol = null }) => ({
        totalRaw: 0,
        stopListening: null,

        init() {
            this.recompute()

            // The fields' values are filled in by wire:model after this
            // component's own init — switching rupiah/percentage swaps this
            // whole block in with a total of 0 otherwise. Values the server
            // writes back ("Bagi rata", a mode switch) fire no input event
            // either, so every finished Livewire request recomputes too.
            this.$nextTick(() => this.recompute())

            if (window.Livewire?.hook) {
                this.stopListening = window.Livewire.hook('commit', ({ succeed }) => {
                    succeed(() => this.$nextTick(() => this.recompute()))
                })
            }
        },

        destroy() {
            if (typeof this.stopListening === 'function') this.stopListening()
        },

        parse(raw) {
            if (! raw) return 0

            let clean = raw.split(group).join('').split(decimal).join('.')
            let value = parseFloat(clean)

            return Number.isFinite(value) ? value : 0
        },

        recompute() {
            let sum = 0

            this.$el.querySelectorAll('input[type="text"]').forEach((el) => {
                sum += this.parse(el.value)
            })

            this.totalRaw = sum
        },

        limit() {
            if (! limitFieldId) return 100

            let el = document.getElementById(limitFieldId)

            return el ? this.parse(el.value) : 0
        },

        matches() {
            return Math.abs(this.totalRaw - this.limit()) < (decimals > 0 ? 0.005 : 0.5)
        },

        formatted() {
            let rounded = decimals > 0 ? Math.round(this.totalRaw * 100) / 100 : Math.round(this.totalRaw)
            let [whole, fraction] = Math.abs(rounded).toFixed(decimals > 0 ? 2 : 0).split('.')
            let grouped = whole.replace(/\B(?=(\d{3})+(?!\d))/g, group)
            let sign = rounded < 0 ? '-' : ''

            if (! fraction) return sign + grouped

            // Trailing zeroes in a percentage's own decimals read as noise
            // ("33,30%") once nobody typed them — the same trim the
            // server's own number_format() does for the same field.
            let trimmed = fraction.replace(/0+$/, '')

            return trimmed === '' ? sign + grouped : sign + grouped + decimal + trimmed
        },

        text() {
            let amount = symbol ? symbol + ' ' + this.formatted() : this.formatted()

            return template.replace(placeholder, amount)
        },
    }))

    /**
     * A calendar sheet that draws its own grid instead of leaving the choice
     * to whichever picker the phone's OS happens to supply.
     *
     * That used to be a native `<input type="date">`, and the trouble with
     * one is that "the picker" is not one thing: iOS draws a spinning wheel,
     * Android draws a Material calendar, and desktop Chrome draws a third
     * shape again — three different pieces of UI for the same field, none
     * of them skinned to match the rest of the app, and — the part that
     * actually caused a bug — not all of them reliably grey out a day past
     * `max` the same way. This one is exactly one thing everywhere.
     *
     * Usage: <div x-data="datePicker({ min: '2026-01-01', max: '' })">
     * — both bounds are 'YYYY-MM-DD' strings, or '' for no bound. The
     * component never keeps the chosen value in its own state for display;
     * that stays server-rendered Blade (see date-input.blade.php), so nothing
     * here can ever fall out of step with a value something else — a
     * validation clamp, another field's own updated hook — changed from
     * underneath it. Only the sheet's own navigation (which month is shown,
     * open or closed) lives in Alpine, and it re-reads the real value fresh,
     * from `$el.dataset.value`, every time it opens.
     */
    Alpine.data('datePicker', ({ min, max }) => ({
        open: false,
        viewYear: 0,
        viewMonth: 0, // 0-11, JavaScript's own convention.
        selected: null,
        weekdayNames: [],
        // Computed fresh every time the sheet opens, in openSheet() below —
        // fixed-position coordinates, since the panel is teleported to
        // <body> (see the comment on the panel itself for why) and can no
        // longer rely on an `absolute` ancestor to place it.
        panelStyle: {},

        // Seeded on load so the field already shows the right day/label the
        // instant a pick happens, without waiting on it. `wire:model`
        // (deferred, no `.live`) only reaches the server — and only then
        // re-renders the Blade-side `$display` this button falls back to —
        // on whatever request the rest of the form triggers next (an
        // unrelated field's own change, or Simpan). Until that request, the
        // button used to keep showing whatever day it opened with, which
        // reads as the tap having done nothing right up until some later,
        // unrelated click makes the correct day suddenly appear.
        init() {
            let current = this.parseIso(this.$root.dataset.value);

            this.selected = current ? this.toIso(current) : null;
        },

        displayLabel() {
            if (! this.selected) return null;

            let date = this.parseIso(this.selected);

            return date
                ? date.toLocaleDateString(document.documentElement.lang || 'id', { day: 'numeric', month: 'long', year: 'numeric' })
                : null;
        },

        // One flat run of cells, {key, iso, label, disabled, selected,
        // today} or {key} alone for padding either end of the month — flat,
        // not weeks of 7, because `grid-cols-7` already does the wrapping
        // and a nested `x-for` inside an `x-for` does not survive Alpine's
        // template cloning: the inner loop's nodes are inserted against the
        // outer template's own marker, so the weeks landed in the wrong
        // order (the last week of the month drawn second). Rebuilt in full
        // — never read live from a method called during `x-for` — every
        // time the viewed month or the selection changes.
        //
        // It used to be a plain method Alpine called fresh on every reactive
        // tick from inside `x-for`, which is the ordinary way to do this and
        // almost always fine — until a Livewire morph landed mid-navigation
        // (this sheet has no server round trip of its own, but the rest of
        // the form it lives in can still trigger one) and caught Alpine's
        // reactivity between ticks: the header (plain interpolation,
        // recomputed synchronously) showed the new month correctly while
        // the grid (rebuilt lazily) still had days from the old one keyed to
        // the wrong weekday column entirely. A stored array Alpine already
        // tracks as one piece of state does not have that gap — the whole
        // month is replaced in one assignment, never assembled mid-render.
        grid: [],

        openSheet() {
            // $root, not $el: called from the trigger's @click, $el is the
            // button, which carries no data-value, so the sheet always
            // opened on today with nothing marked as picked.
            let current = this.parseIso(this.$root.dataset.value);
            let base = current ?? new Date();

            this.viewYear = base.getFullYear();
            this.viewMonth = base.getMonth();
            this.selected = current ? this.toIso(current) : null;
            this.rebuildGrid();

            // Tapping this button after typing into an earlier field (the
            // usual order: Nominal, Keterangan, then Tanggal) dismisses
            // that field's on-screen keyboard — a resize of the visual
            // viewport that is still animating when the click handler
            // itself runs. positionPanel() measured against that
            // mid-animation, keyboard-still-up layout used to land the
            // panel wherever the field sat then, not where it ends up once
            // the keyboard finishes closing and the sheet's fields shift
            // down to fill the space back in. Blurring up front starts
            // that dismissal as early as possible instead of leaving it to
            // whatever the tap's own focus change would have triggered.
            if (document.activeElement instanceof HTMLElement && document.activeElement !== this.$refs.trigger) {
                document.activeElement.blur();
            }

            requestAnimationFrame(() => {
                this.positionPanel();
                this.open = true;

                // Best-effort position is already showing; if a keyboard
                // dismissal is still resizing the viewport a frame or two
                // later, catch its settle and re-measure against the
                // layout it leaves behind rather than the cramped one.
                if (window.visualViewport) {
                    let vv = window.visualViewport;
                    let reposition = () => this.open && this.positionPanel();

                    vv.addEventListener('resize', reposition, { once: true });
                    setTimeout(() => vv.removeEventListener('resize', reposition), 400);
                }
            });
        },

        /**
         * Where the panel lands, in viewport coordinates — computed once per
         * open rather than kept in sync with scroll/resize, since the sheet
         * that hosts the field is itself scroll-locked (`x-trap.noscroll`)
         * while this is open.
         *
         * Teleported to <body> (see the panel's own `x-teleport`), so
         * `position: fixed` here is relative to the viewport, not to
         * whatever `overflow-hidden`/`overflow-y-auto` ancestor the trigger
         * button happens to sit inside — a modal's rounded-corner clipping,
         * or its own scrollable body, used to cut the calendar off (fully,
         * on a short sheet) whenever the field sat close to an edge.
         */
        positionPanel() {
            let rect = this.$refs.trigger.getBoundingClientRect();
            let width = Math.min(320, window.innerWidth - 16);
            let left = Math.min(Math.max(rect.left, 8), window.innerWidth - width - 8);
            let estimatedHeight = 400;
            let spaceBelow = window.innerHeight - rect.bottom;
            let openUpward = spaceBelow < estimatedHeight && rect.top > spaceBelow;

            this.panelStyle = {
                position: 'fixed',
                left: `${left}px`,
                width: `${width}px`,
                ...(openUpward
                    ? { bottom: `${window.innerHeight - rect.top + 8}px` }
                    : { top: `${rect.bottom + 8}px` }),
            };
        },

        /**
         * `@click.outside` assumes the element it watches contains
         * everything that counts as "inside" — not true once the panel is
         * teleported out to a sibling of the trigger rather than a
         * descendant of it, so it is done by hand here against both pieces
         * instead.
         */
        closeIfClickedOutside(event) {
            if (! this.open) return;
            if (this.$refs.trigger.contains(event.target)) return;
            if (this.$refs.panel && this.$refs.panel.contains(event.target)) return;

            this.open = false;
        },

        /**
         * Some callers (an assistant proposal card) draw this from a value
         * that came back from the browser rather than out of a validated
         * form, so a malformed string reads as "nothing picked yet" rather
         * than breaking the sheet open.
         */
        parseIso(iso) {
            if (! iso) return null;

            let [y, m, d] = iso.split('-').map(Number);
            let date = new Date(y, (m || 1) - 1, d || 1);

            return Number.isNaN(date.getTime()) ? null : date;
        },

        toIso(date) {
            let y = date.getFullYear();
            let m = String(date.getMonth() + 1).padStart(2, '0');
            let d = String(date.getDate()).padStart(2, '0');

            return `${y}-${m}-${d}`;
        },

        shiftMonth(delta) {
            let next = new Date(this.viewYear, this.viewMonth + delta, 1);

            this.viewYear = next.getFullYear();
            this.viewMonth = next.getMonth();
            this.rebuildGrid();
        },

        monthLabel() {
            return new Date(this.viewYear, this.viewMonth, 1)
                .toLocaleDateString(document.documentElement.lang || 'id', { month: 'long', year: 'numeric' });
        },

        /**
         * Sunday-first, matching the calendar on a phone rather than the
         * Monday-first week the ISO standard prefers — the same convention
         * {@see InteractsWithPeriod} already keeps server-side.
         */
        weekdayLabels() {
            if (this.weekdayNames.length) return this.weekdayNames;

            let formatter = new Intl.DateTimeFormat(document.documentElement.lang || 'id', { weekday: 'short' });

            // 1 Jan 2023 was a Sunday — an arbitrary anchor, only ever used
            // to read back what this locale calls each day of the week.
            this.weekdayNames = Array.from({ length: 7 }, (_, i) => formatter.format(new Date(2023, 0, i + 1)).toUpperCase());

            return this.weekdayNames;
        },

        rebuildGrid() {
            let firstOfMonth = new Date(this.viewYear, this.viewMonth, 1);
            let daysInMonth = new Date(this.viewYear, this.viewMonth + 1, 0).getDate();
            let today = this.toIso(new Date());
            let cells = Array(firstOfMonth.getDay()).fill(null);

            for (let day = 1; day <= daysInMonth; day++) {
                let iso = this.toIso(new Date(this.viewYear, this.viewMonth, day));

                cells.push({
                    iso,
                    label: day,
                    disabled: (min && iso < min) || (max && iso > max),
                    selected: this.selected === iso,
                    today: iso === today,
                });
            }

            while (cells.length % 7 !== 0) cells.push(null);

            this.grid = cells.map((cell, i) => ({ key: `${this.viewYear}-${this.viewMonth}-${i}`, cell }));
        },

        pick(cell) {
            if (! cell || cell.disabled) return;

            this.selected = cell.iso;

            // The hidden field wire:model actually binds to. Written the
            // same way the assistant composer's dictation writes into the
            // draft box: set on the real element, then dispatch the event
            // Livewire is listening for, rather than trying to reach past
            // Alpine into `$wire` directly.
            this.$refs.input.value = this.selected;
            this.$refs.input.dispatchEvent(new Event('input'));

            this.open = false;
        },

        goToday() {
            let today = new Date();
            let iso = this.toIso(today);

            if ((min && iso < min) || (max && iso > max)) return;

            this.viewYear = today.getFullYear();
            this.viewMonth = today.getMonth();
            this.rebuildGrid();
            this.pick({ iso, disabled: false });
        },
    }))

    Alpine.data('toasts', (flashed = null) => ({
        items: [],

        init() {
            window.addEventListener('notify', (event) => this.push(event.detail))

            // A confirmation flashed into the session by the request that
            // sent us here, shown once the page it belongs to has arrived.
            if (flashed && flashed.message) {
                this.push(flashed)
            }
        },

        push(detail) {
            let id = Date.now() + Math.random()

            this.items.push({ id, ...detail })

            setTimeout(() => this.dismiss(id), detail.timeout ?? 5000)
        },

        dismiss(id) {
            this.items = this.items.filter((item) => item.id !== id)
        },
    }))
})
