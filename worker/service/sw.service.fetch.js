// @ts-check
/* global CACHE_NAME */

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
