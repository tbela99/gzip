// @ts-check
/* eslint wrap-iife: 0 */
/* global SW, CACHE_NAME */
SW.strategies.add('cf', (event) => {

	'use strict;';

	return caches.match(event.request).then((response) => {

		if (response != undef) {

			return response;
		}

		return fetch(event.request).then((response) => {

			if (SW.strategies.isCacheableResponse(response)) {

				const cloned = response.clone();
				caches.open(CACHE_NAME).then(function (cache) {

					cache.put(event.request, cloned);
				});
			}

			return response;
		})
	});
});