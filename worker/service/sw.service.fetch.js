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

/**
 * @param {FetchEvent} event
 */
self.addEventListener("fetch", (event) => {
	const handler = SW.router.getHandler(event.request.url, event);

	if (handler != undef) {
		event.respondWith(
			handler.handle(event).catch((error) => {
				console.error("😭", error);
				return fetch(event.request);
			})
		);
	}
	//	}
});
