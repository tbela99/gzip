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

import {
	SW,
	undef
} from "../serviceworker.js";

//const undef = null;
/**
 * @param {FetchEvent} event
 */

self.addEventListener("fetch", (event) => {
	event.respondWith((async function () {

		if (!event.url || (event.request.cache === 'only-if-cached' && event.request.mode !== 'same-origin')) {

			return fetch(event.request);
		}

		let response;

		const router = SW.routes.getRouter(event);

		if (router != undef) {

			try {

				response = await router.handler.handle(event);

				if (response instanceof Response) {

					return response;
				}

				for (response of await SW.routes.resolve('fail', event, response)) {

					if (response instanceof Response) {

						return response;
					}
				}

				// offline page should be returned from the previous loop
			} catch (error) {

				console.error("😭", error);
			}
		}

		return fetch(event.request);
	})());
});