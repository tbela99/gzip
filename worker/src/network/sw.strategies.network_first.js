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

export async function networkFirst(event) {
	"use strict;";

	try {
		const response = await fetch(event.request);

		//	.then(response => {
		if (response == null) {
			throw new Error("Network error");
		}

		if (strategies.isCacheableRequest(event.request, response)) {
			const cloned = response.clone();
			caches
				.open(cacheName)
				.then(cache => cache.put(event.request, cloned));
		}

		return response;
		//	})
	} catch (error) {
		console.error("😭", error);
	}

	return caches.match(event.request, {cacheName});
}
