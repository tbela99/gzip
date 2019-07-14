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

import {
	DB
} from "../db/db.js";
import {
	sprintf
} from "../utils/sw.string.js";

/**
 * enforce the limitation of the number of files in the cache
 */
export const cleanup = (async function () {

	let cache = await caches.open('{CACHE_NAME}');

	const preloaded_urls = "{preloaded_urls}".map(url => new URL(url, self.location).href);

	const limit = "{pwa_cache_max_file_count}";
	const db = await DB(
		"gzip_sw_worker_expiration_cache_private",
		"url",
		[{
				name: "url",
				key: "url"
			},
			{
				name: "version",
				key: "version"
			},
			{
				name: "route",
				key: "route"
			}
		]
	);

	return async function () {

		let count = await db.count();

		if (count > limit) {

			console.info(sprintf('cleaning up [%s] items present. [%s] items allowed', count, limit));

			for (let metadata of await db.getAll()) {

				if (preloaded_urls.includes(metadata.url)) {

					console.info(sprintf('skipped preloaded resource [%s]', metadata.url));
					continue;
				}

				console.info(sprintf('removing [%s]', metadata.url));

				await cache.delete(metadata.url);
				await db.delete(metadata.url);

				if (--count <= limit) {

					break;
				}
			}

			console.info(sprintf('cleaned up [%s] items present. [%s] items allowed', count, limit));
		}
	}
});