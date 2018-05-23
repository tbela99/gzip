/**
 *
 * @package     GZip Plugin
 * @subpackage  System.Gzip *
 * @copyright   Copyright (C) 2005 - 2018 Thierry Bela.
 *
 * dual licensed
 *
 * @license     LGPL v3
 * @license     MIT License
 */

// @ts-check
/* global SW, CACHE_NAME */
/* eslint wrap-iife: 0 */
// stale while revalidate

SW.strategies.add(
	"cn",
	async event => {
		"use strict;";

		const response = await caches.match(event.request, {
			cacheName: CACHE_NAME
		});

		const fetchPromise = fetch(event.request).then(networkResponse => {
			// validate response before
			if (
				SW.strategies.isCacheableRequest(event.request, networkResponse)
			) {
				const cloned = networkResponse.clone();
				caches.open(CACHE_NAME).then(function(cache) {
					cache.put(event.request, cloned);
				});
			}

			return networkResponse;
		});

		return response || fetchPromise;
		//	});
	},
	"Cache and Network Update"
);
