/* global SW, CACHE_NAME */

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
SW.getCacheSize = async function() {

    const cache = await caches.open(CACHE_NAME);

//	return .then(async cache => {
		let cacheSize = 0;

		const keys = await cache.keys();

		await Promise.all(
			keys.map(async key => {
				const response = await cache.match(key);
				const blob = await response.size();
				//	total += blob.size;
				cacheSize += blob.size;
            });
            
        return cacheSize;
//	});
};
