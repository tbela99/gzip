// @ts-check
/* global CACHE_NAME */
self.addEventListener("activate", event => {
	// delete old app owned caches
	event.waitUntil(
		self.clients.claim().then(async () => {
			const keyList = await caches.keys();
			const tokens = CACHE_NAME.split(/_/, 2);
			const search = tokens.length == 2 && tokens[0] + "_";

			// delete older instances
			return Promise.all(
				keyList.map(
					key =>
						search !== false &&
						key.indexOf(search) == 0 &&
						key != CACHE_NAME &&
						caches.delete(key)
				)
			);
		})
	);
});