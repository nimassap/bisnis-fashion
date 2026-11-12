/*
| Service worker.
|
| Deliberately small, and deliberately incurious about your business.
|
| The only things it stores are the icons and the fingerprinted build assets —
| files that are identical for every visitor and never change under a given
| name. Pages are always fetched from the network, because a page here holds
| live figures: serving a cached one risks showing a stale balance, or worse,
| showing it to whoever picks the phone up after a different person signs in.
|
| Anything that is not a plain same-origin GET — every form post, every
| Livewire update, every export — is passed straight through untouched.
|
| Every use of the cache is optional. A private window, a browser with site
| data switched off, or a device that has simply run out of room can all make
| CacheStorage unavailable, and an unhandled rejection in here would leave the
| worker redundant and the site worse off than with no worker at all. So each
| access is guarded and failure means "carry on without caching".
*/

// Bump this when the rules below change; the old cache is dropped on activate.
const VERSION = 'v2'
const CACHE = `bisnis-fashion-${VERSION}`
const OFFLINE = '/offline.html'

// Immutable by construction: Vite fingerprints these filenames with a hash of
// their contents, so a given URL can be trusted forever.
const IMMUTABLE = ['/build/', '/icons/']

self.addEventListener('install', (event) => {
    event.waitUntil(warmOfflinePage().then(() => self.skipWaiting()))
})

self.addEventListener('activate', (event) => {
    event.waitUntil(dropOldCaches().then(() => self.clients.claim()))
})

self.addEventListener('fetch', (event) => {
    const request = event.request

    if (request.method !== 'GET') return

    const url = new URL(request.url)

    if (url.origin !== self.location.origin) return

    if (IMMUTABLE.some((prefix) => url.pathname.startsWith(prefix))) {
        event.respondWith(cacheFirst(request))

        return
    }

    // A page load. Always the network; the cache is only ever a way of saying
    // "you are offline" rather than a way of answering the question.
    if (request.mode === 'navigate') {
        event.respondWith(networkFirst(request))
    }
})

/**
 * The cache, or null if this browser will not give us one.
 */
async function openCache() {
    try {
        return await caches.open(CACHE)
    } catch {
        return null
    }
}

async function warmOfflinePage() {
    const cache = await openCache()

    if (! cache) return

    try {
        if (! await cache.match(OFFLINE)) {
            await cache.add(OFFLINE)
        }
    } catch {
        // The page will be fetched again next time there is a network.
    }
}

async function dropOldCaches() {
    try {
        const keys = await caches.keys()

        await Promise.all(keys.filter((key) => key !== CACHE).map((key) => caches.delete(key)))
    } catch {
        // Nothing to clean up, or nothing we are allowed to clean up.
    }
}

async function cacheFirst(request) {
    const cache = await openCache()

    if (! cache) return fetch(request)

    const cached = await cache.match(request).catch(() => undefined)

    if (cached) return cached

    const response = await fetch(request)

    // Opaque and error responses are not worth keeping, and caching one would
    // pin a failure in place until the next version bump.
    if (response.ok && response.type === 'basic') {
        cache.put(request, response.clone()).catch(() => {})
    }

    return response
}

async function networkFirst(request) {
    try {
        const response = await fetch(request)

        // Keep the offline page fresh while there is a network to fetch it
        // with, which also covers an install that could not warm it.
        warmOfflinePage()

        return response
    } catch {
        const cache = await openCache()
        const offline = cache ? await cache.match(OFFLINE).catch(() => undefined) : undefined

        return offline ?? offlineFallback()
    }
}

/**
 * A last resort, for when even the offline page never reached the cache.
 */
function offlineFallback() {
    return new Response(
        '<!doctype html><meta charset="utf-8">'
        + '<meta name="viewport" content="width=device-width,initial-scale=1">'
        + '<title>Tidak ada koneksi</title>'
        + '<body style="font-family:system-ui,sans-serif;display:grid;place-items:center;'
        + 'min-height:100dvh;margin:0;padding:1.5rem;text-align:center">'
        + '<p>Tidak ada koneksi. Coba lagi setelah jaringan kembali.</p>',
        { status: 503, headers: { 'Content-Type': 'text/html; charset=utf-8' } },
    )
}
