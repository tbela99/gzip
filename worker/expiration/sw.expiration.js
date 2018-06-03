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
			this.setOptions(options);
		}

		getRouteTag(url) {
			const route = SW.app.route;
			let h, host;

			for (host of SW.app.urls) {
				if (
					new RegExp("^https?://" + host + "/" + route + "/").test(
						url
					)
				) {
					return route;
				}
			}

			return undef;
		}

		async setOptions(options) {
			//cacheName = "gzip_sw_worker_expiration_cache_private",
			//	limit = 0,
			//	maxAge = 0
			//
			this.limit = +options.limit || 0;
			this.maxAge = +options.maxAge * 1000 || 0;

			this.db = await DB(
				options.cacheName != undef
					? options.cacheName
					: "gzip_sw_worker_expiration_cache_private",
				"url",
				[
					{name: "url", key: "url"},
					{name: "version", key: "version"},
					{name: "route", key: "route"}
				]
			);
		}

		async precheck(event) {
			try {
				if (this.db == undef) {
					return true;
				}

				const version = SW.Utils.getObjectHash(event.request);
				const entry = await this.db.get(event.request.url, "url");
				const cache = await caches.open(CACHE_NAME);

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
				const url = event.request.url;
				const entry = await this.db.get(url, "url");
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
							url,
						this
					);

					// need to update
					return await this.db.put({
						url,
						method: event.request.method,
						timestamp: Date.now() + this.maxAge,
						route: this.getRouteTag(url),
						version
					});
				} else {
					console.info(
						"CacheExpiration [postcheck][no update][version=" +
							version +
							"][expires=" +
							entry.timestamp +
							"|" +
							new Date(entry.timestamp).toUTCString() +
							"] " +
							url,
						entry
					);
				}

				return url;
			} catch (e) {
				console.error(CRY, e);
			}

			return true;
		}
	}

	expiration.CacheExpiration = CacheExpiration;
	return expiration;
})();
