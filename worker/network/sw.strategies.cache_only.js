// @ts-check
/* global SW */
/* eslint wrap-iife: 0 */

// If a match isn't found in the cache, the response
// will look like a connection error);
SW.strategies.add("co", (event) => caches.match(event.request));
