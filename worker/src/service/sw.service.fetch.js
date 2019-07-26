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
	SW
} from "../serviceworker.js";

const undef = null;
/**
 * @param {FetchEvent} event
 */

self.addEventListener("fetch", (event) => {
	event.respondWith((async function () {

		let response, resp;

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
				return fetch(event.request);

			} catch (error) {

				console.error("😭", error);

				return fetch(event);
			}
		}

		return fetch(event.request).catch(() => offline(event))
	})());
});