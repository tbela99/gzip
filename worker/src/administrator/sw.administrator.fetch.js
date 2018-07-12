/**
 *
 * runtime configuration settings. placeholders are replaced when the service worker settings are saved from the plugin settings page.
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
/*

Object.defineProperties(SW.app, {
	name: {value: "gzip", enumerable: true},
	//	cacheName: {value: CACHE_NAME, enumerable: true},
	route: {value: "{ROUTE}", enumerable: true},
	codeName: {value: "Page Optimizer Plugin", enumerable: true},
	build: {value: "{VERSION}", enumerable: true},
	buildid: {value: "build-id", enumerable: true},
	builddate: {value: "build-date", enumerable: true},
	urls: {value: "{CDN_HOSTS}", enumerable: true},
	homepage: {value: "https://github.com/tbela99/gzip", enumerable: true}
});

*/

"use strict;";

// do not cache administrator content -> this can be done in the plugin settings / joomla administrator

self.addEventListener("fetch", (event) => {
	event.respondWith(fetch(event.request));
});
