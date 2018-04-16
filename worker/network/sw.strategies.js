// @ts-check
/* eslint wrap-iife: 0 */
/* global SW, undef */

/**
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

SW.strategies = (function() {
	const map = new Map();

	const strategy = {
		/**
		 *
		 * @param {String} name
		 * @param {function} handle
		 */
		add: (name, handle, scheme) =>
			map.set(name, {
				name,
				handle: async event => {
					//	await SW.resolve("prefetch", event.request);
					const response = await handle(event);
					//	await SW.resolve("postfetch", event.request, response);

					console.log({ mode: event.request.mode, response });

					return response;
				}
			}),
		keys: () => map.keys(),
		values: () => map.values(),
		entries: () => map.entries(),
		get: name => map.get(name),
		has: name => map.has(name),
		delete: name => map.delete(name),
		/**
		 *
		 * @param {Request} request
		 * @param {Response} response
		 */
		// https://www.w3.org/TR/SRI/#h-note6
		isCacheableRequest: (request, response) =>
			("cors" == request.mode ||
				new URL(request.url, self.origin).origin == self.origin) &&
			request.method == "GET" &&
			response != undef &&
			(response.type == "basic" || response.type == "default") &&
			response.ok &&
			!response.bodyUsed
	};

	// if opaque response <- crossorigin? you should use cache.addAll instead of cache.put dude <- stop it!
	// if http response != 200 <- hmmm don't want to cache this <- stop it!
	// if auth != basic <- are you private? <- stop it!

	strategy[Symbol.iterator] = () => map[Symbol.iterator]();
	Object.defineProperty(strategy, "size", { get: () => map.size });

	return strategy;
})();
