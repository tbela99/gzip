/**
 *
 * main service worker file
 * @package     GZip Plugin
 * @copyright   Copyright (C) 2005 - 2018 Thierry Bela.
 *
 * dual licensed
 *
 * @license     LGPL v3
 * @license     MIT License
 */

// @ts-check

/*  */

// build build-id build-date
/* eslint wrap-iife: 0 */
// validator https://www.pwabuilder.com/
// pwa app image generator http://appimagegenerator-pre.azurewebsites.net/

"use strict;";
//"{IMPORT_SCRIPTS}";

import {
	Event
} from "./event/sw.event.promise.js";
import {
	Utils
} from "./utils/sw.utils.js";

import {
	Route
} from "./router/sw.router.js";

const undef = null; //

/**
 *
 * {SWType} SW
 */
const SW = Object.create(undef);
const cacheName = "{CACHE_NAME}";
//const CRY = "😭";
//const scope = "{scope}";

Utils.merge(true, SW, Event);

Object.defineProperties(SW, {
	app: {
		value: Object.create(undef)
	},
	routes: {
		value: new Route()
	}
});
Object.defineProperties(SW.app, {
	name: {
		value: "gzip",
		enumerable: true
	},
	scope: {
		value: "{scope}",
		enumerable: true
	},
	route: {
		value: "{ROUTE}",
		enumerable: true
	},
	cacheName: {
		value: "{CACHE_NAME}",
		enumerable: true
	},
	codeName: {
		value: "Page Optimizer Plugin",
		enumerable: true
	},
	build: {
		value: "{VERSION}",
		enumerable: true
	},
	buildid: {
		value: "build-id",
		enumerable: true
	},
	builddate: {
		value: "build-date",
		enumerable: true
	},
	urls: {
		value: "{CDN_HOSTS}",
		enumerable: true
	},
	backgroundSync: {
		value: "{BACKGROUND_SYNC}",
		enumerable: true
	},
	offline: {
		value: "{pwa_offline_page}"
	},
	homepage: {
		value: "https://github.com/tbela99/gzip",
		enumerable: true
	}
});

export {
	cacheName,
	SW
};