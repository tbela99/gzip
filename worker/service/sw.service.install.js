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
/* global CACHE_NAME */

self.addEventListener("install", event => {
	event.waitUntil(
		caches.open(CACHE_NAME).then(async cache => {
			await cache.addAll("{preloaded_urls}");
			await SW.resolve("install");
			return self.skipWaiting();
		})
	);
});
