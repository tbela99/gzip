// @ts-check
/* global SW, CACHE_NAME */
/* eslint wrap-iife: 0 */
SW.strategies.add('nf', (event, cache) => {

	'use strict;';

    return fetch(event.request).then((response) => {

		if (response == undef) {

			throw new Error('Network error');
		}

		if (SW.strategies.isCacheableResponse(response)) {

			const cloned = response.clone();
			caches.open(CACHE_NAME).then(function (cache) {

				cache.put(event.request, cloned);
			})
		}

      return response;

    }).catch(() => cache.match(event.request)
  );
});
