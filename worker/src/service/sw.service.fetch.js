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

let undef;

async function offline(event) {

	console.log({
		'SW.app.offline': SW.app.offline,
		'event.request.mode': event.request.mode,
		'event.request.method': event.request.method
	});

	if (SW.app.offline.url != '' && event.request.mode == 'navigate' && SW.app.offline.methods.includes(event.request.method)) {

		const match = caches.match(SW.app.offline.url);

		if (match != undef) {

			return match;
		}

		return match;
	}
}

/**
 * @param {FetchEvent} event
 */

self.addEventListener("fetch", (event) => {
	const router = SW.routes.getRouter(event);

	event.respondWith((async function () {

		let response;

		if (router != null) {

			try {

				response = await router.handler.handle(event);
				//	.then(response => {

				if (!(response instanceof Response)) {

					let resp = await SW.routes.resolve('fail', event.request, response);

					if (resp instanceof Response) {

						response = resp;
					}
				}

				//		return response

				//	}).
				//	then(response => {

				if (response == undef) {

					response = await offline(event);
					//.then(response => {

					if (response == undef) {

						response = await fetch(event.request);
					}

					//	return response
					//	})
				}

				return response
				//	}).
			} catch (error) {
				//	catch ((error) => {

				console.error("😭", error);

				return offline(event)
				//	});
			}
		}

		return fetch(event.request).catch(() => offline(event))
	})());
});