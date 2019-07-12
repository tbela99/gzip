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
	SW
} from "../serviceworker.js";
import {
	hashCode,
	getObjectHash
} from "../crypto/sw.crypto.js";
import {
	num2FileSize
} from '../utils/sw.file.js';
import {
	sprintf,
	capitalize,
	ellipsis
} from "../utils/sw.string.js";
import {
	expo
} from "../utils/sw.backoff.js";

// @ts-check
//SW.expiration = (function() {
const CRY = "😭";
const undef = null;
let cache;

caches.open(SW.app.cacheName).then(c => cache = c);

/**
 * @property {DBType} db
 * @class CacheExpiration
 */

export class CacheExpiration {

	constructor(options) {

		this.setOptions(options);
	}

	getRouteTag(url) {
		const route = SW.app.route;
		let host;

		for (host of SW.app.urls) {
			if (new RegExp("^https?://" + host + SW.app.scope + route + "/").test(url)) {
				return route;
			}
		}

		return undef;
	}

	async setOptions(options) {

		const date = new Date;
		const now = +date;

		this.maxAge = 0;
		this.limit = +options.limit || 0;
		this.maxFileSize = +options.maxFileSize || 0;

		const match = options.maxAge.match(/([+-]?\d+)(.*)$/);

		if (match != null) {

			switch (match[2]) {

				//	case 'seconds':
				case 'months':
				case 'minutes':
				case 'hours':

					let name = capitalize(match[2]);

					if (name == 'months') {

						name = 'month';
					}

					date['set' + name](+match[1] + date['get' + name]());
					this.maxAge = date - now;
			}
		}

		try {
			this.db = await DB(
				options.cacheName != undef ?
				options.cacheName :
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

			if (this.limit > 0) {

				const db = this.db;
				const limit = this.limit;
				const retry = expo();
				let tick = 0;

				const cleanup = async function () {

					try {

						let count = await db.count();

						if (count > limit) {

							console.info(sprintf('cleaning up [%s] items present. [%s] items allowed', count, limit));

							for (let metadata of await db.getAll()) {

								console.info(sprintf('removing [%s]', metadata.url));

								cache.delete(metadata.url);
								db.delete(metadata.url);

								if (--count <= limit) {

									break;
								}
							}

							console.info(sprintf('cleaned up [%s] items present. [%s] items allowed', count, limit));
						}
					} catch (error) {

					}


					setTimeout(cleanup, retry(tick++));
				}

				setTimeout(cleanup, retry(tick++));
			}
		} catch (e) {
			console.error(CRY, e);
		}
	}

	async precheck(event) {
		try {
			if (this.db == undef || this.maxAge == 0) {
				return true;
			}

			const version = hashCode(getObjectHash(event.request));
			const entry = await this.db.get(event.request.url, "url");

			if (
				entry != undef &&
				(entry.version != version || entry.timestamp < Date.now())
			) {

				await caches.delete(event.request);
				return true;
			}

			return await cache.match(event.request);
		} catch (e) {
			console.error(CRY, e);
		}

		// todo ->delete expired
		// todo -> delete if count > limit

		return true;
	}

	/**
	 * 
	 * @param {FetchEvent} event 
	 * @param {Response} response 
	 */
	async postcheck(event, response) {

		if (this.db == undef) {
			return true;
		}

		if (this.maxFileSize > 0) {

			if (response.body != undef) {

				const blob = await response.clone().blob();

				if (blob.size > this.maxFileSize) {

					console.info(sprintf('cache limit exceeded. Deleting item [%s]', ellipsis(response.url)));

					// delete any cached response
					await this.db.delete(response.url);
					await cache.delete(response);
					throw new Error(sprintf('[%s][cache failed] cache size limit exceeded %s of %s', ellipsis(response.url, 42), num2FileSize(blob.size), num2FileSize(this.maxFileSize)));
				}
			}
		}

		try {
			const url = event.request.url;
			const entry = await this.db.get(url, "url");
			const version = hashCode(getObjectHash(event.request));

			if (
				entry == undef ||
				entry.version != version ||
				entry.timestamp < Date.now()
			) {

				// need to update
				return await this.db.put({
					url,
					//	method: event.request.method,
					timestamp: Date.now() + this.maxAge,
					route: this.getRouteTag(url),
					version
				});
			}

			return url;
		} catch (e) {
			console.error(CRY, e);
		}

		return true;
	}
}