// @ts-check
/* eslint wrap-iife: 0 */
/* global SW */
SW.strategies = function () {

	const map = new Map;

	const strategy = {

		/**
		 *
		 * @param {String} name
		 * @param {function} fetch
		 */
		add: (name, handle) => map.set(name, {handle}),
		keys: () => map.keys(),
		values: () => map.values(),
		entries: () => map.entries(),
		get: (name) => map.get(name),
		has: (name) => map.has(name),
		delete: (name) => map.delete(name),
		/**
		 *
		 * @param {Response} response
		 */
	//	isCacheableResponse: (response) => response != null && response.type == 'basic' && response.ok && !response.bodyUsed
		isCacheableResponse: (response) => {

		//	console.log({response, type: response && response.type, ok: response && response.ok, bodyUsed: response && response.bodyUsed});
		//	console.log(new Error().stack);

			return response != undef && response.type == 'basic' && response.ok && !response.bodyUsed;
		}

			// if opaque response <- crossorigin? you should use cache.addAll instead of cache.put dude <- stop it!
			// if http response != 200 <- hmmm don't want to cache this <- stop it!
			// if auth != basic <- are you private? <- stop it!



	};

	strategy[Symbol.iterator] = () => map[Symbol.iterator]();
	Object.defineProperty(strategy, 'size', { get: () => map.size });

	return strategy;
}();
