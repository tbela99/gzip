// @ts-check
/* global SW, CACHE_NAME */

SW.getCacheSize = function() {
	return caches.open(CACHE_NAME).then(keys => {
		let cacheSize = 0;

		await Promise.all(
			keys.map(key => {
				const response = await cache.match(key);
				const blob = await response.size();
				//	total += blob.size;
				cacheSize += blob.size;
			})
		);

		return cacheSize;
	});
};
