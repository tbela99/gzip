// @ts-check
/* eslint wrap-iife: 0 */
/* main service worker file */
/* global */

'use strict;';

const SW = Object.create(null);
const CACHE_NAME = 'v1';

let undef;

// importScripts('serviceworkerhelper.min.js');
// importScripts('serviceworkerhelper.js');

self.addEventListener('install', function(event) {
	event.waitUntil(self.skipWaiting());
});

self.addEventListener('activate', function(event) {

    event.waitUntil(self.clients.claim());
});

/**
 * @param {Request} event
 */
self.addEventListener('fetch', (event) => {

    if (event.request.method !== 'GET') {

        return;
    }
	const scope = '{scope}';
	const strategies = SW.strategies;
	const url = new URL(event.request.url);

	if (url.pathname.match(new RegExp(scope + '/administrator/')) != undef) {

		return;
	}

	let strategyToUse = (url.pathname.match(new RegExp(scope + '/media/z/([a-z]{2})/')) || [])[1];

	if (!strategies.has(strategyToUse)) {

		strategyToUse = 'cn';
	}

	console.log({strategyToUse});

	//no cn cf nf no co
//	if (strategyToUse != undef && ) {

		// strategyToUse = 'no';
		// match will ignore the cache strategy?
		event.respondWith(
			strategies.get(strategyToUse).handle(event)
		);
//	}

});