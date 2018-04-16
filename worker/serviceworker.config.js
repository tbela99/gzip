// @ts-check
/* eslint wrap-iife: 0 */
/* global SW, scope */

/**
 *
 * main service worker file
 *
 * @package     GZip Plugin
 * @subpackage  System.Gzip *
 * @copyright   Copyright (C) 2005 - 2018 Thierry Bela.
 *
 * dual licensed
 *
 * @license     LGPL v3
 * @license     MIT License
 */

"use strict;";

// do not cache administrator content -> this can be done in the plugin settings / joomla addministrator
//SW.Filter.addRule(SW.Filter.Rules.Prefetch, function(request) {
//	return request.url.indexOf(scope + "/administrator/") != -1;
//});

//const excluded = "{exclude_urls}";

const strategies = SW.strategies;
const Router = SW.Router;
const router = SW.router;
const handler = strategies.get("no");
let entry;

let defaultStrategy = "{defaultStrategy}";

if (!strategies.has(defaultStrategy)) {
	// default browser behavior
	defaultStrategy = "no";
}

//console.log({ SW });

router.setDefaultHandler(strategies.get(defaultStrategy));

// register strategies routers
for (entry of strategies) {
	router.registerRoute(
		new Router.ExpressRouter(scope + "/media/z/" + entry[0] + "/", entry[1])
	);
}

// excluded urls fallback on network only
"{exclude_urls}".forEach((path) => {
	router.registerRoute(new Router.RegExpRouter(new RegExp(path), handler));
});

//let x;

//for (x of SW.strategies) {
//	console.log(x);
//}
