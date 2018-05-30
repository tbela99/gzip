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

/**
 * - url
 * - method
 * - timestamp ((getHeader(Date) || Date.now()) + maxAge)
 **/

/** global undef */

// @ts-check
SW.expiration = (function() {
	const expiration = Object.create(undef);

	/**
	 * @property {DBType} db
	 * @class CacheExpiration
	 */

	class CacheExpiration {
		constructor(options) {
			//cacheName = "gzip_sw_worker_expiration_cache_private",
			//	limit = 0,
			//	maxAge = 0

			const self = this;
			this.limit = +options.limit || 0;
			this.maxAge = +options.maxAge || 0;

			DB(
				options.cacheName || "gzip_sw_worker_expiration_cache_private",
				"url"
			).then(db => (self.db = db instanceof Error ? undef : db));
		}

		async precheck(event) {
			try {
				if (this.db == undef) {
					return true;
				}

				const entries = await this.db.getAll(event.request.url);
				const response = await caches
					.open(CACHE_NAME)
					.then(cache => cache.match(event.request));

				console.log({entries, response});
			} catch (e) {
				console.error(CRY, e);
			}

			// todo ->delete expired
			// todo -> delete if count > limit

			return true;

			//	return (
			//		entries == undef || Date.now() - entry.timestamp < this.maxAge
			//	);
		}

		async postcheck(event) {
			return this.db.put({
				url: event.request.url,
				method: event.request.method,
				timestamp: Date.now() + this.maxAge
			});
		}
	}

	expiration.CacheExpiration = CacheExpiration;
	return expiration;
})();
