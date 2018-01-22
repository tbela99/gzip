// @ts-check
/* eslint wrap-iife: 0 */
/* global SW, CACHE_NAME */
SW.strategies.add("cf", async event => {
	"use strict;";

	let response = await caches.match(event.request);

	if (response != undef) {
		return response;
	}

	response = await fetch(event.request);

	if (SW.strategies.isCacheableRequest(event.request, response)) {
		const cloned = response.clone();
		caches.open(CACHE_NAME).then(function(cache) {
			cache.put(event.request, cloned);
		});
	}

	return response;
});
