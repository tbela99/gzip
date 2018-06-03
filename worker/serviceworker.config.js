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
/* global SW, scope, undef */
/** @var {string} scope */
/** @var {SWType} SW */

"use strict;";

// do not cache administrator content -> this can be done in the plugin settings / joomla addministrator
//SW.Filter.addRule(SW.Filter.Rules.Prefetch, function(request) {
//	return request.url.indexOf(scope + "/administrator/") != -1;
//});

const strategies = SW.strategies;
const Router = SW.Router;
const route = SW.route;
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

	//	console.log({option});

	route.registerRoute(
		new Router.RegExpRouter(
			new RegExp(entry[1], "i"),
			strategies.get(entry[0]),
			option == undef
				? option
				: {plugins: [new SW.expiration.CacheExpiration(option)]}
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
	async install() {
		console.info("🛠️ service worker install event");

		await caches.open(CACHE_NAME).then(async cache => {
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
					console.info({storeName});

					store = await DB(storeName, "url", [
						{name: "url", key: "url"},
						{name: "version", key: "version"},
						{name: "route", key: "route"}
					]);

					if (store != undef) {
						store.clear();
					}
				}
			}
		}

		await db.put(SW.app);

		// delete obsolet caches
		const keyList = await caches.keys();
		const tokens = CACHE_NAME.split(/_/, 2);
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
						key != CACHE_NAME &&
						caches.delete(key)
				)
			);
		}
	}
});
