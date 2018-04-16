// @ts-check
/* eslint wrap-iife: 0 */
/* global SW, CACHE_NAME */

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
