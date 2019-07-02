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

// If a match isn't found in the cache, the response
// will look like a connection error);

import {
	cacheName
} from "../serviceworker.js";

export async function cacheOnly(event) {
	return await caches.match(event.request, {
		cacheName
	});
}