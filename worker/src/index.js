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
 * @property {[]<string>} mime
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
	CacheExpiration
} from "./expiration/sw.expiration.js";
import {
	//	Router,
	ExpressRouter,
	RegExpRouter
} from "./router/sw.router.js";
import {
	strategies
} from "./network/index.js";
import {
	SW,
	undef
} from "./serviceworker.js";

//const undef = null;
const route = SW.routes;
const scope = SW.app.scope;
const networkSettings = SW.app.network;
const caching = networkSettings.caching;
//const cacheExpiryStrategy = "{cacheExpiryStrategy}";
let entry;
let router;

let maxAge;
let maxFileSize;
let limit;
let cacheName;
let strategy;

({
	limit,
	maxAge,
	cacheName,
	strategy,
	maxFileSize
} = networkSettings);

const defaultCacheSettings = {
	limit,
	maxAge,
	strategy,
	maxFileSize
};

//let option;

// excluded urls fallback on network only
for (entry of "{exclude_urls}") {
	route.registerRoute(
		new RegExpRouter(new RegExp(entry), strategies.get("no"))
	);
}

// excluded urls fallback on network only
//const network_strategies = "{network_strategies}";

for (entry of networkSettings.settings) {

	router = new RegExpRouter(
		new RegExp('(' + entry.ext.join(')|(') + ')', "i"),
		strategies.get(entry.strategy),
		entry
	);

	if (caching) {

		({
			limit,
			maxAge,
			//	cacheName,
			maxFileSize
		} = entry);

		router.addPlugin(new CacheExpiration({
			limit,
			maxAge,
			maxFileSize
		}));
	}

	route.registerRoute(router);
}

/*
// implement encrypted file support as well as expiry date?
router = new ExpressRouter(
	scope + "{ROUTE}/e/",
	entry[1]
);
if (caching) {

	router.addPlugin(new CacheExpiration(defaultCacheSettings));
}

route.registerRoute(router);
*/

// register strategies routers
for (entry of strategies) {

	router = new ExpressRouter(
		scope + "{ROUTE}/media/z/" + entry[0] + "/",
		entry[1]
	);

	if (caching) {

		router.addPlugin(new CacheExpiration(defaultCacheSettings));
	}

	route.registerRoute(router);
}

router = new ExpressRouter(scope, strategies.get(networkSettings.strategy));

if (caching) {

	router.addPlugin(new CacheExpiration(defaultCacheSettings));
}

route.setDefaultRouter(router);

cacheName = SW.app.cacheName;

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

		await caches.open(cacheName).then(async cache => await cache.addAll(SW.app.precache));
	},
	async activate() {
		console.info("🚁 service worker activate event");

		const db = await DB("gzip_sw_worker_config_cache_private", "name");

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
import './expiration/index.js';
import './offline/index.js';