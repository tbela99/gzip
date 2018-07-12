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
import {SW} from "../serviceworker.js";

/**
 * @param {FetchEvent} event
 */

self.addEventListener("fetch", (event) => {
	const router = SW.routes.getRouter(event);

	if (router != null) {
		event.respondWith(
			router.handler.handle(event).catch((error) => {
				console.error("😭", error);
				return fetch(event.request);
			})
		);
	}
	//	}
});
