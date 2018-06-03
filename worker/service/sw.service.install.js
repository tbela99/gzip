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
/* global CACHE_NAME */

self.addEventListener("install", event => {
	event.waitUntil(
		(async () => {
			try {
				await SW.resolve("install", event);
			} catch (e) {
				console.error(CRY, e);
			}
			return self.skipWaiting();
		})()
	);
});
