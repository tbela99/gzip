// @ts-check
/* eslint wrap-iife: 0 */
/* global SW */

// or simply don't call event.respondWith, which will result in default browser behaviour
SW.strategies.add("no", (event) => fetch(event.request));
