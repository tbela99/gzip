// @ts-check
/* global SW, CACHE_NAME */
/* eslint wrap-iife: 0 */
// stale while revalidate
SW.strategies.add('cn', function(event) {

    'use strict;';

	return caches.match(event.request).then(function(response) {

			const fetchPromise = fetch(event.request).then(function(networkResponse) {

				// validate response before
				if (SW.strategies.isCacheableResponse(networkResponse)) {

				//	console.log('cache put ' + event.request.url);

					const cloned = networkResponse.clone();
					caches.open(CACHE_NAME).then(function (cache) {

						cache.put(event.request, cloned);
					});
				}

			//	else {

			//		console.log('cannot put ' + event.request.url);
			//	}

				return networkResponse;
			});
		//	.catch(function () { return response; });

			return response || fetchPromise;
		});
	}
);
