/**
 *
 * @package     GZip Plugin
 * @copyright   Copyright (C) 2005 - 2018 Thierry Bela.
 *
 * dual licensed
 *
 * @license     LGPL v3
 * @license     MIT License
 */

// @ts-check
/* eslint wrap-iife: 0 */
// stale while revalidate

import {strategies} from "./sw.strategies.js";
import {cacheName} from "../serviceworker.js";

export async function cacheNetwork(event) {
	"use strict;";

	const response = await caches.match(event.request, {
		cacheName
	});

	const fetchPromise = fetch(event.request).then(networkResponse => {
		// validate response before
		if (strategies.isCacheableRequest(event.request, networkResponse)) {
			const cloned = networkResponse.clone();
			caches
				.open(cacheName)
				.then(cache => cache.put(event.request, cloned));
		}

		return networkResponse;
	}).catch(error => console.error("😭", error));

	return response || fetchPromise;
	//	});
}
