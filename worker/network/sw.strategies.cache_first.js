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
/* global SW, CACHE_NAME */

SW.strategies.add(
	"cf",
	async event => {
		"use strict;";

		let response = await caches.match(event.request, {
			cacheName: CACHE_NAME
		});

		if (response != undef) {
			return response;
		}

		response = await fetch(event.request);

		if (SW.strategies.isCacheableRequest(event.request, response)) {
			const cloned = response.clone();
			caches
				.open(CACHE_NAME)
				.then(cache => cache.put(event.request, cloned));
		}

		return response;
	},
	"Cache fallback to Network"
);
