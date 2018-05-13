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

SW.strategies.add("nf", async (event, cache) => {
	"use strict;";

	try {
		const response = await fetch(event.request);

		//	.then(response => {
		if (response == undef) {
			throw new Error("Network error");
		}

		if (SW.strategies.isCacheableRequest(event.request, response)) {
			const cloned = response.clone();
			caches.open(CACHE_NAME).then(function(cache) {
				cache.put(event.request, cloned);
			});
		}

		return response;
		//	})
	} catch (e) {}

	return cache.match(event.request);
});
