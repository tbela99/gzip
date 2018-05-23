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

self.addEventListener("activate", event => {
	// delete old app owned caches
	event.waitUntil(
		self.clients.claim().then(async () => {
			const keyList = await caches.keys();
			const tokens = CACHE_NAME.split(/_/, 2);
			/**
			 * @var {boolean|string}
			 */
			const search = tokens.length == 2 && tokens[0] + "_";

			// delete older instances
			if (search != false) {
				await Promise.all(
					keyList.map(
						key =>
							key.indexOf(search) == 0 &&
							key != CACHE_NAME &&
							caches.delete(key)
					)
				);
			}

			return SW.resolve("activate");
		})
	);
});
