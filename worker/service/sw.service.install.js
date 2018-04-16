// @ts-check
/* global CACHE_NAME */

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
self.addEventListener("install", event => {
	event.waitUntil(
		caches.open(CACHE_NAME).then(async cache => {
			await cache.addAll("{preloaded_urls}");
			return self.skipWaiting();
		})
	);
});
