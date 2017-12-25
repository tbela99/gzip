// @ts-check
/* global SW, CACHE_NAME */
/* eslint wrap-iife: 0 */
// stale while revalidate
SW.strategies.add("cn", async event => {
	"use strict;";

	const response = await caches.match(event.request);

	const fetchPromise = fetch(event.request).then(function(networkResponse) {
		// validate response before
		if (SW.strategies.isCacheableResponse(networkResponse)) {
			const cloned = networkResponse.clone();
			caches.open(CACHE_NAME).then(function(cache) {
				cache.put(event.request, cloned);
			});
		}

		return networkResponse;
	});

	return response || fetchPromise;
	//	});
});
