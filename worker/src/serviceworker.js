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

import {
	Event
} from "./event/sw.event.promise.js";
import {
	Utils
} from "./utils/sw.utils.js";

import {
	Route
} from "./router/sw.router.js";

export const undef = null; //

/**
 * service worker configuration issue
 * {SWType} SW
 */
export const SW = Object.create(undef);
//const CRY = "😭";
//const scope = "{scope}";

Utils.merge(true, SW, Event);

Object.defineProperties(SW, {
	/**
	 * app config
	 */
	app: {
		value: Object.create(undef)
	},
	/**
	 * app routes
	 */
	routes: {
		value: new Route()
	}
});
Object.defineProperties(SW.app, {
	/**
	 * app name
	 */
	name: {
		value: "gzip",
		enumerable: true
	},
	/**
	 * service worker scope
	 */
	scope: {
		value: "{scope}",
		enumerable: true
	},
	/**
	 * cache path prefix
	 */
	route: {
		value: "{ROUTE}",
		enumerable: true
	},
	/**
	 * IndexedDb cache name
	 */
	cacheName: {
		value: "{CACHE_NAME}",
		enumerable: true
	},
	/**
	 * app code name
	 */
	codeName: {
		value: "Joomla Website Optimizer Plugin",
		enumerable: true
	},
	/**
	 * service worker build number
	 */
	build: {
		value: "{VERSION}",
		enumerable: true
	},
	/**
	 * service worker build id
	 */
	buildid: {
		value: "build-id",
		enumerable: true
	},
	/**
	 * service worker buid date
	 */
	builddate: {
		value: "build-date",
		enumerable: true
	},
	/**
	 * cdn hosts
	 */
	urls: {
		value: "{CDN_HOSTS}",
		enumerable: true
	},
	/**
	 * background sync settings
	 */
	backgroundSync: {
		value: "{BACKGROUND_SYNC}",
		enumerable: true
	},
	/**
	 * offline page
	 */
	offline: {
		value: "{pwa_offline_page}"
	},
	/**
	 * cache settings
	 */
	network: {
		value: "{pwa_cache_settings}"
	},
	/**
	 * precached resources
	 */
	precache: {
		value: "{preloaded_urls}".map(url => new URL(url, self.origin).href),
		enumerable: true
	},
	homepage: {
		value: "https://github.com/tbela99/gzip",
		enumerable: true
	}
});