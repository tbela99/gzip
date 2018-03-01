// @ts-check
/* global CACHE_NAME */
self.addEventListener("install", event => {
	event.waitUntil(
		caches.open(CACHE_NAME).then(async cache => {
			await cache.addAll("{preloaded_urls}");
			return self.skipWaiting();
		})
	);
});
