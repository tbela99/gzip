// @ts-check
/* eslint wrap-iife: 0 */
/* main service worker file */
/* global */
// validator https://www.pwabuilder.com/
// pwa app image generator http://appimagegenerator-pre.azurewebsites.net/

"use strict;";

const SW = Object.create(null);
const CACHE_NAME = "{CACHE_NAME}";
const scope = "{scope}";
const defaultStrategy = "{defaultStrategy}";

let undef; //

// -> importScript indexDb
self.addEventListener("install", function(event) {
	event.waitUntil(
		caches.
			open(CACHE_NAME).
			then(function(cache) {
				return cache.addAll("{preloaded_urls}");
			}).
			then(function() {
				return self.skipWaiting();
			})
	);
});

self.addEventListener("activate", function(event) {
	// delete old app owned caches
	caches.keys().then(function(keyList) {
		const tokens = CACHE_NAME.split(/_/, 2);
		const search = tokens.length == 2 && tokens[0] + "_";

		return (
			search !== false &&
			Promise.all(
				keyList.map(
					(key) =>
						key.indexOf(search) == 0 &&
						key != CACHE_NAME &&
						caches.delete(key)
				)
			)
		);
	});

	event.waitUntil(self.clients.claim());
});

/**
 * @param {Request} event
 */
self.addEventListener("fetch", (event) => {
	if (event.request.method !== "GET") {
		return;
	}

	const strategies = SW.strategies;

	// guess stategy from url
	let strategyToUse = (new URL(event.request.url).pathname.match(
		new RegExp(scope + "/media/z/([a-z]{2})/")
	) || [])[1];

	// fallback to default configured in the plugin settings
	if (strategyToUse == undef) {
		strategyToUse = defaultStrategy;
	}

	if (!strategies.has(strategyToUse)) {
		// default browser behavior
		strategyToUse = "no";
	}

	console.info({ strategyToUse, url: event.request.url });

	if (event.request.url.indexOf("data:") != 0) {
		event.respondWith(
			strategies.
				get(strategyToUse).
				handle(event).
				catch((error) => {
					console.error("😭", error);
					return fetch(event.request);
				})
		);
	}
});
