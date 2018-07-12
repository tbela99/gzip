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
import {strategies} from "./sw.strategies.js";
import {cacheName} from "../serviceworker.js";

export async function cacheFirst(event) {
	"use strict;";

	let response = await caches.match(event.request, {
		cacheName
	});

	if (response != null) {
		return response;
	}

	response = await fetch(event.request);

	if (strategies.isCacheableRequest(event.request, response)) {
		const cloned = response.clone();
		caches.open(cacheName).then(cache => cache.put(event.request, cloned));
	}

	return response;
}
