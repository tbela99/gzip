/**
 *
 * main service worker file
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
/* eslint wrap-iife: 0 */

// build build-id build-date
/* eslint wrap-iife: 0 */
// validator https://www.pwabuilder.com/
// pwa app image generator http://appimagegenerator-pre.azurewebsites.net/

/**
 *
 * type definitions file
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

/**
 * @typedef SWType
 * @property {SWPropAPP} app
 * @property {Route} routes
 * @method {callback} resolve
 * @method {callback} on
 * @method {callback} off
 * @property Expiration
 */

/**
 * @typedef SWPropAPP
 * @property {string} name
 * @property {string} scope
 * @property {string} route
 * @property {string} cacheName
 * @property {string} codeName
 * @property {string} build
 * @property {string} buildid
 * @property {string} builddate
 * @property {[]<string>} urls
 * @property {bool} backgroundSync
 * @property {string} homepage
 */

/**
 * @typedef DBType
 * @method {callback} count
 * @method {callback} getEntry
 * @method {callback} getAll
 * @method {callback} put
 * @method {callback} delete
 * @method {callback} flush
 * @method {callback} then
 * @method {callback} catch
 */

/**
 *
 * @var {DBType} DB
 * */
/**
 * @typedef RouteHandler
 * @property {Router} router
 * @property {RouterOptions} options
 *
 */

/**
 * @typedef RouterOptions
 * @property {cacheName} string cache name
 * @property {number} expiration
 *
 */

/**
 *
 * @async
 * @callback routerHandle
 * @param {FetchEvent} event
 */

/**
 * @typedef routerHandleObject
 * @property {object} handler
 * @property {routerHandle} handler.handle
 */

/**
 * @typedef {RegExp|string|URL} routerPath
 */

import {
	DB
} from "./db/db.js";
import {
	expiration
} from "./expiration/sw.expiration.js";
import {
	Router
} from "./router/sw.router.js";
import {
	strategies
} from "./network/index.js";
import {
	cacheName,
	SW
} from "./serviceworker.js";

const undef = null;
const route = SW.routes;
const scope = SW.app.scope;
const cacheExpiryStrategy = "{cacheExpiryStrategy}";
let entry;
let option;

let defaultStrategy = "{defaultStrategy}";

// excluded urls fallback on network only
for (entry of "{exclude_urls}") {
	route.registerRoute(
		new Router.RegExpRouter(new RegExp(entry), strategies.get("no"))
	);
}

// excluded urls fallback on network only
for (entry of "{network_strategies}") {
	option = entry[2] || cacheExpiryStrategy;

	route.registerRoute(
		new Router.RegExpRouter(
			new RegExp(entry[1], "i"),
			strategies.get(entry[0]),
			option == undef ?
			option : {
				plugins: [new expiration.CacheExpiration(option)]
			}
		)
	);
}

// register strategies routers
for (entry of strategies) {
	route.registerRoute(
		new Router.ExpressRouter(
			scope + "/{ROUTE}/media/z/" + entry[0] + "/",
			entry[1]
		)
	);
}

if (!strategies.has(defaultStrategy)) {
	// default browser behavior
	defaultStrategy = "no";
}

route.setDefaultRouter(
	new Router.ExpressRouter("/", strategies.get(defaultStrategy))
);

// service worker activation
SW.on({
	error(error, event) {

		console.error({
			error,
			event
		});
	},
	async install() {
		console.info("🛠️ service worker install event");

		await caches.open(cacheName).then(async cache => {
			await cache.addAll("{preloaded_urls}");
		});
	},
	async activate() {
		console.info("🚁 service worker activate event");

		const db = await DB("gzip_sw_worker_config_cache_private", "name");

		//	console.log("{STORES}");

		const settings = await db.get("gzip");

		if (settings != undef) {
			if (settings.route != "{ROUTE}") {
				// the url cache prefix has changed! delete private cache expiration data
				let storeName, store;

				for (storeName of "{STORES}") {

					store = await DB(storeName, "url", [{
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
					]);

					if (store != undef) {
						store.clear();
					}
				}
			}
		}

		await db.put(SW.app);

		// delete obsolete caches
		const keyList = await caches.keys();
		const tokens = cacheName.split(/_/, 2);
		/**
		 * @var {boolean|string}
		 */
		const search = tokens.length == 2 && tokens[0] + "_";

		// delete older app caches
		if (search != false) {
			await Promise.all(
				keyList.map(
					key =>
					key.indexOf(search) == 0 &&
					key != cacheName &&
					caches.delete(key)
				)
			);
		}
	}
});

import "./service/sw.service.activate.js";
import "./service/sw.service.fetch.js";
import "./service/sw.service.install.js";
import "./sync/index.js";