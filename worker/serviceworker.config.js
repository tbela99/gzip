// @ts-check
/* eslint wrap-iife: 0 */
/* main service worker file */
/* global SW, scope */

"use strict;";

// do not cache administrator content
SW.Filter.addRule(SW.Filter.Rules.Prefetch, function(request) {
	return request.url.indexOf(scope + "/administrator/") != -1;
});
