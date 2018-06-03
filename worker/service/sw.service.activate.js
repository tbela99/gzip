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
/* global CACHE_NAME, SW */

self.addEventListener("activate", event => {
	// delete old app owned caches
	event.waitUntil(
		(async () => {
			try {
				await SW.resolve("activate", event);
			} catch (e) {
				console.error(CRY, e);
			}
			return self.clients.claim();
		})()
	);
});
