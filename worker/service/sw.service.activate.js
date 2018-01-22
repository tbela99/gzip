// @ts-check
/* global CACHE_NAME */
self.addEventListener("activate", function(event) {
	// delete old app owned caches
	event.waitUntil(
		self.clients.claim().then(function() {
			return caches.keys().then(function(keyList) {
				const tokens = CACHE_NAME.split(/_/, 2);
				const search = tokens.length == 2 && tokens[0] + "_";

				return Promise.all(
					keyList.map(
						(key) =>
							search !== false &&
							key.indexOf(search) == 0 &&
							key != CACHE_NAME &&
							caches.delete(key)
					)
				);
			});
		})
	);
});
