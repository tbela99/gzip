// @ts-check
/* global SW */
/* eslint wrap-iife: 0 */

SW.strategies.add('co', (event) => caches.match(event.request)
    // If a match isn't found in the cache, the response
    // will look like a connection error);
);
