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

// @ts-check
/* eslint wrap-iife: 0 */
/*global SW, undef */

/**
 *
 */
SW.strategies = (function() {
	const map = new Map();

	const strategy = {
		/**
		 *
		 * @param {String} name
		 * @param {function} handle
		 */
		add: (name, handle) =>
			map.set(name, {
				name,
				handle: async event => {
					//	await SW.resolve("prefetch", event.request);
					const response = await handle(event);
					//	await SW.resolve("postfetch", event.request, response);

					console.info({
						strategy: name,
						responseMode: response.type,
						requestMode: event.request.mode,
						ok: response.ok,
						bodyUsed: response.bodyUsed,
						responseType: response && response.type,
						isCacheableRequest: strategy.isCacheableRequest(
							event.request,
							response
						),
						request: event.request.url,
						response: response && response.url
					});

					return response;
				}
			}),
		/**
		 *
		 * @returns {IterableIterator<any>}
		 */
		keys: () => map.keys(),
		/**
		 *
		 * @returns {IterableIterator<any>}
		 */
		values: () => map.values(),
		/**
		 *
		 * @returns {IterableIterator<[any]>}
		 */
		entries: () => map.entries(),
		/**
		 *
		 * @param {String} name
		 * @returns {any}
		 */
		get: name => map.get(name),
		/**
		 *
		 * @param {String} name
		 * @returns {boolean}
		 */
		has: name => map.has(name),
		/**
		 *
		 * @param {String} name
		 * @returns {boolean}
		 */
		delete: name => map.delete(name),
		/**
		 *
		 * @param {Request} request
		 * @param {Response} response
		 */
		// https://www.w3.org/TR/SRI/#h-note6
		isCacheableRequest: (request, response) =>
			response != undef &&
			("cors" == response.type ||
				new URL(request.url, self.origin).origin == self.origin) &&
			request.method == "GET" &&
			response.ok &&
			["default", "cors", "basic"].includes(response.type) &&
			!response.bodyUsed
	};

	// if opaque response <- crossorigin? you should use cache.addAll instead of cache.put dude <- stop it!
	// if http response != 200 <- hmmm don't want to cache this <- stop it!
	// if auth != basic <- are you private? <- stop it!

	strategy[Symbol.iterator] = () => map[Symbol.iterator]();
	Object.defineProperty(strategy, "size", {get: () => map.size});

	return strategy;
})();
