// @ts-check
/* global CACHE_NAME */
self.addEventListener("install", function(event) {
	event.waitUntil(
		caches.
			open(CACHE_NAME).
			then(function(cache) {
				return cache.addAll("{preloaded_urls}");
			}).
			then(function() {
				return self.skipWaiting();
			})
	);
});
