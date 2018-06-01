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

/** global undef, CRY, CACHE_NAME */

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
			//

			const self = this;
			this.limit = +options.limit || 0;
			this.maxAge = +options.maxAge * 1000 || 0;

			DB(
				options.cacheName || "gzip_sw_worker_expiration_cache_private",
				"url"
			)
				.then(db => (self.db = db))
				.catch(e => console.error(CRY, e));
		}

		async precheck(event) {
			try {
				if (this.db == undef) {
					return true;
				}

				const version = SW.Utils.getObjectHash(event.request);
				const entry = await this.db.get(event.request.url);
				const cache = await caches
					.open(CACHE_NAME)
					.then(cache => cache);

				if (
					entry != undef &&
					(entry.version != version || entry.timestamp < Date.now())
				) {
					console.info(
						"CacheExpiration [precheck][obsolete][" +
							version +
							"] " +
							event.request.url
					);

					caches.delete(event.request);
					return true;
				}

				return await cache.match(event.request);
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
			if (this.db == undef) {
				return true;
			}

			try {
				const entry = await this.db.get(event.request.url);
				const version = SW.Utils.getObjectHash(event.request);

				if (
					entry == undef ||
					entry.version != version ||
					entry.timestamp < Date.now()
				) {
					console.info(
						"CacheExpiration [postcheck][update][version=" +
							version +
							"][expires=" +
							(Date.now() + this.maxAge) +
							"|" +
							new Date(Date.now() + this.maxAge).toUTCString() +
							"] " +
							event.request.url,
						this
					);

					// need to update
					return await this.db.put({
						url: event.request.url,
						method: event.request.method,
						timestamp: Date.now() + this.maxAge,
						version
					});

					return url;
				} else {
					console.info(
						"CacheExpiration [postcheck][no update][version=" +
							version +
							"][expires=" +
							entry.timestamp +
							"|" +
							new Date(entry.timestamp).toUTCString() +
							"] " +
							event.request.url,
						entry
					);
				}

				return event.request.url;
			} catch (e) {
				console.error(CRY, e);
			}

			return true;
		}
	}

	expiration.CacheExpiration = CacheExpiration;
	return expiration;
})();
