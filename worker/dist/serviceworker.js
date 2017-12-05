/* do not edit! */
// @ts-check
/* eslint wrap-iife: 0 */
/* main service worker file */
/* global */
"use strict;";

const SW = Object.create(null);

const CACHE_NAME = "v1";

let undef;

// importScripts('serviceworkerhelper.min.js');
// importScripts('serviceworkerhelper.js');
self.addEventListener("install", function(event) {
    event.waitUntil(self.skipWaiting());
});

self.addEventListener("activate", function(event) {
    event.waitUntil(self.clients.claim());
});

/**
 * @param {Request} event
 */
self.addEventListener("fetch", event => {
    if (event.request.method !== "GET") {
        return;
    }
    const scope = "{scope}";
    const strategies = SW.strategies;
    const url = new URL(event.request.url);
    if (url.pathname.match(new RegExp(scope + "/administrator/")) != undef) {
        return;
    }
    let strategyToUse = (url.pathname.match(new RegExp(scope + "/media/z/([a-z]{2})/")) || [])[1];
    if (!strategies.has(strategyToUse)) {
        strategyToUse = "cn";
    }
    console.log({
        strategyToUse: strategyToUse,
        url: event.request.url
    });
    //no cn cf nf no co
    //	if (strategyToUse != undef && ) {
    // strategyToUse = 'no';
    // match will ignore the cache strategy?
    event.respondWith(strategies.get(strategyToUse).handle(event));
});

// @ts-check
/* eslint wrap-iife: 0 */
/* global SW */
SW.strategies = function() {
    const map = new Map();
    const strategy = {
        /**
		 *
		 * @param {String} name
		 * @param {function} fetch
		 */
        add: (name, handle) => map.set(name, {
            handle: handle
        }),
        keys: () => map.keys(),
        values: () => map.values(),
        entries: () => map.entries(),
        get: name => map.get(name),
        has: name => map.has(name),
        delete: name => map.delete(name),
        /**
		 *
		 * @param {Response} response
		 */
        //	isCacheableResponse: (response) => response != null && response.type == 'basic' && response.ok && !response.bodyUsed
        isCacheableResponse: response => {
            //	console.log({response, type: response && response.type, ok: response && response.ok, bodyUsed: response && response.bodyUsed});
            //	console.log(new Error().stack);
            return response != undef && response.type == "basic" && response.ok && !response.bodyUsed;
        }
    };
    strategy[Symbol.iterator] = (() => map[Symbol.iterator]());
    Object.defineProperty(strategy, "size", {
        get: () => map.size
    });
    return strategy;
}();

// @ts-check
/* global SW, CACHE_NAME */
/* eslint wrap-iife: 0 */
SW.strategies.add("nf", (event, cache) => {
    "use strict;";
    return fetch(event.request).then(response => {
        if (response == undef) {
            throw new Error("Network error");
        }
        if (SW.strategies.isCacheableResponse(response)) {
            const cloned = response.clone();
            caches.open(CACHE_NAME).then(function(cache) {
                cache.put(event.request, cloned);
            });
        }
        return response;
    }).catch(() => cache.match(event.request));
});

// @ts-check
/* eslint wrap-iife: 0 */
/* global SW, CACHE_NAME */
SW.strategies.add("cf", event => {
    "use strict;";
    return caches.match(event.request).then(response => {
        if (response != undef) {
            return response;
        }
        return fetch(event.request).then(response => {
            if (SW.strategies.isCacheableResponse(response)) {
                const cloned = response.clone();
                caches.open(CACHE_NAME).then(function(cache) {
                    cache.put(event.request, cloned);
                });
            }
            return response;
        });
    });
});

// @ts-check
/* global SW, CACHE_NAME */
/* eslint wrap-iife: 0 */
// stale while revalidate
SW.strategies.add("cn", function(event) {
    "use strict;";
    return caches.match(event.request).then(function(response) {
        const fetchPromise = fetch(event.request).then(function(networkResponse) {
            // validate response before
            if (SW.strategies.isCacheableResponse(networkResponse)) {
                //	console.log('cache put ' + event.request.url);
                const cloned = networkResponse.clone();
                caches.open(CACHE_NAME).then(function(cache) {
                    cache.put(event.request, cloned);
                });
            }
            //	else {
            //		console.log('cannot put ' + event.request.url);
            //	}
            return networkResponse;
        });
        //	.catch(function () { return response; });
        return response || fetchPromise;
    });
});

// @ts-check
/* eslint wrap-iife: 0 */
/* global SW */
// or simply don't call event.respondWith, which will result in default browser behaviour
SW.strategies.add("no", event => fetch(event.request));

// @ts-check
/* global SW */
/* eslint wrap-iife: 0 */
SW.strategies.add("co", event => caches.match(event.request));