// @ts-check
/* eslint wrap-iife: 0 */
/* main service worker file */
/* global SW, scope */

"use strict;";

// do not cache administrator content -> this can be done in the plugin settings / joomla addministrator
//SW.Filter.addRule(SW.Filter.Rules.Prefetch, function(request) {
//	return request.url.indexOf(scope + "/administrator/") != -1;
//});

const excluded = "{exclude_urls}";

SW.Filter.addRule(SW.Filter.Rules.Prefetch, function(request) {
	let i = excluded.length;

	while (i && i--) {
		if (request.url.indexOf(excluded[i]) == -1) {
			return false;
		}
	}

	return true;
});
