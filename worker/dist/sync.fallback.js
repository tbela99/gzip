(function () {
	'use strict';

	/* LICENSE: MIT LICENSE | https://github.com/msandrini/minimal-indexed-db */
	/* global window */

	/**
	 * @typedef DBType
	 * @function count
	 * @function getEntry
	 * @function getAll
	 * @function put
	 * @function delete
	 * @function flush
	 * @function then
	 * @function catch
	 */

	/**
	 *
	 * @var {DBType} DB
	 * */

	async function DB(dbName, key = "id", indexes = []) {
		return new Promise((resolve, reject) => {
			const openDBRequest = indexedDB.open(dbName, 1);
			const storeName = `${dbName}_store`;
			let db;
			const _upgrade = () => {
				db = openDBRequest.result;
				const store = db.createObjectStore(storeName, {
					keyPath: key
				});

				let index;

				for (index of indexes) {
					store.createIndex(index.name, index.key, index.options);
				}
			};
			const _query = (method, readOnly, param = null, index = null) =>
				new Promise((resolveQuery, rejectQuery) => {
					const permission = readOnly ? "readonly" : "readwrite";
					if (db.objectStoreNames.contains(storeName)) {
						const transaction = db.transaction(storeName, permission);
						const store = transaction.objectStore(storeName);
						const isMultiplePut =
							method === "put" &&
							param &&
							typeof param.length !== "undefined";
						let listener;
						if (isMultiplePut) {
							listener = transaction;
							param.forEach(entry => {
								store.put(entry);
							});
						} else {
							if (index) {
								store.index(index);
							}

							listener = store[method](param);
						}

						listener.oncomplete = event => {
							resolveQuery(event.target.result);
						};
						listener.onsuccess = event => {
							resolveQuery(event.target.result);
						};
						listener.onerror = event => {
							rejectQuery(event);
						};
					} else {
						rejectQuery(new Error("Store not found"));
					}
				});

			const methods = {
				count: async () => {

					const transaction = db.transaction(storeName, "readonly");
					const store = transaction.objectStore(storeName);

					const countRequest = store.count();

					return new Promise(function (resolve, reject) {

						countRequest.onsuccess = function(event) {

							resolve(event.target.result);
						};

						countRequest.onerror = function(error) {
							reject(error);
						};
					});
				},
				get: (keyToUse, index) => _query("get", true, keyToUse, index),
				getAll: (keyToUse, index) =>
					_query("getAll", true, keyToUse, index),
				put: entryData => _query("put", false, entryData),
				delete: keyToUse => _query("delete", false, keyToUse),
				clear: () => _query("clear", false),
				deleteDatabase: () => new Promise(function (resolve, reject) {

					const result = indexedDB.deleteDatabase;

					result.onerror = reject;
					result.onsuccess = resolve;
				})
			};
			const _successOnBuild = () => {
				db = openDBRequest.result;
				resolve(methods);
			};
			const _errorOnBuild = e => {
				reject(new Error(e.originalTarget && e.originalTarget.error || e));
			};
			openDBRequest.onupgradeneeded = _upgrade.bind(this);
			openDBRequest.onsuccess = _successOnBuild.bind(this);
			openDBRequest.onerror = _errorOnBuild.bind(this);
		});
	}

	/**
	 *
	 * @package     GZip Plugin
	 * @copyright   Copyright (C) 2005 - 2018 Thierry Bela.
	 *
	 * dual licensed
	 *
	 * @license     LGPL v3
	 * @license     MIT License
	 */

	// @ts-check
	/* eslint wrap-iife: 0 */

	const undef = null;

	const Utils = {
		implement(target) {
			const proto = target.prototype,
				args = [].slice.call(arguments, 1);
			let i, source, key;

			function makefunc(fn, previous, parent) {
				return function () {
					const self = this,
						hasPrevious = "previous" in self,
						hasParent = "parent" in self,
						oldPrevious = self.previous,
						oldParent = self.parent;

					self.previous = previous;
					self.parent = parent;

					const result = fn.apply(self, arguments);

					if (hasPrevious) {
						self.previous = oldPrevious;
					}

					if (hasParent) {
						self.parent = oldParent;
					}

					return result;
				};
			}

			for (i = 0; i < args.length; i++) {
				for (key in args[i]) {
					source = args[i][key];

					switch (typeof source) {
						case "function":
							proto[key] = makefunc(source, target[key], proto[key]);

							break;

						case "object":
							proto[key] = merge(
								true,
								Array.isArray(source) ? [] : {},
								source
							);
							break;

						default:
							proto[key] = source;
							break;
					}
				}
			}

			return target;
		},
		merge,
		reset,
		/**
		 *  extend a function to accept either a key/value or an object as arguments
		 * 	ex set(name, value, [...]) or set({name: value, name2: value2}, [...])
		 * @param {Function} fn
		 */
		extendArgs(fn) {
			return function (key) {
				if (typeof key == "object") {
					const args = [].slice.call(arguments, 1);
					let k;

					for (k in key) {
						fn.apply(this, [k, key[k]].concat(args));
					}
				} else {
					fn.apply(this, arguments);
				}

				return this;
			};
		}
	};

	function merge(target) {
		const args = [].slice.call(arguments, 1);
		let deep = typeof target == "boolean",
			i,
			source,
			prop,
			value;

		if (deep === true) {
			deep = target;
			target = args.shift();
		}

		for (i = 0; i < args.length; i++) {
			source = args[i];

			if (source == undef) {
				continue;
			}

			for (prop in source) {
				value = source[prop];

				switch (typeof value) {
					case "object":
						if (value == undef || !deep) {
							target[prop] = value;
						} else {
							target[prop] = merge(
								deep,
								typeof target[prop] == "object" &&
								target[prop] != undef ?
								target[prop] :
								Array.isArray(value) ? [] : {},
								//
								value
							);
						}

						break;

					default:
						target[prop] = value;
						break;
				}
			}
		}

		return target;
	}

	function reset(object) {
		const properties = Utils.getAllPropertiesName(object);
		let name,
			descriptor,
			i = properties.length;

		while (i && i--) {
			name = properties[i];
			descriptor = Object.getOwnPropertyDescriptor(object, name);

			//
			if (
				object[name] == undef ||
				typeof object[name] != "object" ||
				descriptor == undef ||
				(!("value" in descriptor) ||
					!(descriptor.writable && descriptor.configurable))
			) {
				continue;
			}

			object[name] = merge(
				true,
				Array.isArray(object[name]) ? [] : {},
				reset(object[name])
			);
		}

		return object;
	}

	/**
	 *
	 * @package     GZip Plugin
	 * @copyright   Copyright (C) 2005 - 2018 Thierry Bela.
	 *
	 * dual licensed
	 *
	 * @license     LGPL v3
	 * @license     MIT License
	 */

	function hashCode(string) {
		var hash = 0,
			i, chr;
		if (string.length === 0) return hash;
		for (i = 0; i < string.length; i++) {
			chr = string.charCodeAt(i);
			hash = ((hash << 5) - hash) + chr;
			hash |= 0; // Convert to 32bit integer
		}
		return Number(hash).toString(36);
	}

	/**
	 *
	 * @package     GZip Plugin
	 * @copyright   Copyright (C) 2005 - 2018 Thierry Bela.
	 *
	 * dual licensed
	 *
	 * @license     LGPL v3
	 * @license     MIT License
	 */
	// @ts-check
	/* eslint wrap-iife: 0 */

	const isCacheableRequest = (request, response) =>
		response instanceof Response &&
		("cors" == response.type ||
			new URL(request.url, self.origin).origin == self.origin) &&
		request.method == "GET" &&
		response.ok && ["default", "cors", "basic", "navigate"].includes(response.type) &&
		!response.bodyUsed;

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

	// promisified event api on(event, handler) => resolve(event, [args...])
	// promisified event api on({event: handler, event2: handler2}) => resolve(event, [args...])

	//const undef = null;
	const extendArgs = Utils.extendArgs;

	const Event = {
		$events: {},
		$pseudo: {},
		// accept (event, handler)
		// Example: promisify('click:once', function () { console.log('clicked'); }) <- the event handler is fired once and removed
		// accept object with events as keys and handlers as values
		// Example promisify({'click:once': function () { console.log('clicked once'); }, 'click': function () { console.log('click'); }})
		on: extendArgs(function (name, fn, sticky) {
			const self = this;

			if (fn == undef$1) {
				return;
			}

			name = name.toLowerCase();

			let i, ev;

			const original = name;
			const event = {
				fn: fn,
				cb: fn,
				name: name,
				original: name,
				parsed: [name]
			};

			if (name.indexOf(":") != -1) {
				const parsed = name.match(/([^:]+):([^(]+)(\(([^)]+)\))?/);

				if (parsed == undef$1) {
					event.name = name = name.split(":", 1)[0];
				} else {
					event.original = name;
					event.name = parsed[1];
					event.parsed = parsed;

					name = parsed[1];

					if (parsed[2] in self.$pseudo) {
						self.$pseudo[parsed[2]](event);
					}
				}
			}

			if (!(name in self.$events)) {
				self.$events[name] = [];
			}

			i = self.$events[name].length;

			while (i && i--) {
				ev = self.$events[name][i];

				if (ev.fn == fn && ev.original == original) {
					return;
				}
			}

			//    sticky = !!sticky;
			Object.defineProperty(event, "sticky", {
				value: !!sticky
			});

			self.$events[name].push(event);
		}),
		off: extendArgs(function (name, fn, sticky) {
			const self = this;
			let undef, event, i;

			name = name.toLowerCase().split(":", 1)[0];

			const events = self.$events[name];

			if (events == undef) {
				return;
			}

			sticky = !!sticky;

			i = events.length;

			while (i && i--) {
				event = events[i];

				// do not remove sticky events, unless sticky === true
				if (
					(fn == undef && !sticky) ||
					(event.fn == fn && (!event.sticky || event.sticky == sticky))
				) {
					self.$events[name].splice(i, 1);
				}
			}

			if (events.length == 0) {
				delete self.$events[name];
			}
		}),
		/*
			// invoke event handlers
			trigger(name) {

				name = name.toLowerCase();

				const self = this;
				const args = arguments.length > 1 ? [].slice.call(arguments, 1) : [];
				const events = self.$events[name] || [];

				let i = 0;

				for (; i < events.length; i++) {

					events[i].cb.apply(self, args)
				}

				return this;
			},*/
		// invoke event handler using a promise
		resolve(name) {
			name = name.toLowerCase();

			const self = this;
			const args = arguments.length > 1 ? [].slice.call(arguments, 1) : [];

			return Promise.all(
				(self.$events[name] || []).concat().map(
					(event) =>
					new Promise((resolve) => {
						resolve(event.cb.apply(self, args));
					})
				)
			);
		},
		addPseudo(name, fn) {
			this.$pseudo[name] = fn;
			return this;
		}
	};

	Event.addPseudo("once", function (event) {
		event.cb = function () {
			const context = this;

			const value = event.fn.apply(context, arguments);
			context.off(event.name, event.fn);

			return value;
		};

		return this;
	});

	/**
	 *
	 * @package     GZip Plugin
	 * @copyright   Copyright (C) 2005 - 2018 Thierry Bela.
	 *
	 * dual licensed
	 *
	 * @license     LGPL v3
	 * @license     MIT License
	 */

	/**
	 * 
	 * @param {[]<string>|string} method 
	 * @return []<string>
	 */
	function normalize(method = "GET") {

		if (!Array.isArray(method)) {

			method = [method];
		}

		method.forEach(method => {

			if (method == undef$1 || method == "HEAD") {
				return "GET";
			}

			return method.toUpperCase();
		});

		return method;
	}

	/**
	 * request route class
	 *
	 * @property {Object.<string, Router[]>} routes
	 * @property {Object.<string, routerHandle>} defaultRouter
	 * @method {function} route
	 * @method {function} on
	 * @method {function} off
	 * @method {function} resolve
	 *
	 * @class Route
	 */
	class Route {
		constructor() {
			this.routers = Object.create(undef$1);
			this.defaultRouter = Object.create(undef$1);
		}


		/**
		 * get the handler that matches the request event
		 *
		 * @param {FetchEvent} event
		 * @return {RouteHandler}
		 */
		getRouter(event) {
			const method = (event != undef$1 && event.request.method) || "GET";
			const routes = this.routers[method] || [];
			const j = routes.length;
			let route,
				i = 0;

			for (; i < j; i++) {
				route = routes[i];

				if (route.match(event)) {

					return route;
				}
			}

			return this.defaultRouter[method];
		}

		/**
		 * register a handler for an http method
		 *
		 * @param {Router} router router instance
		 * @param {[]<string>|string} method http method
		 */
		registerRoute(router, method = "GET") {

			normalize(method).forEach(method => {

				if (!(method in this.routers)) {
					this.routers[method] = [];
				}

				this.routers[method].push(router);
			});

			return this;
		}

		/**
		 * unregister a handler for an http method
		 *
		 * @param {Router} router router instance
		 * @param {string} method http method
		 */
		unregisterRoute(router, method) {

			normalize(method).forEach(method => {

				const routers = this.routers[method] || [];
				const index = routers.indexOf(router);

				if (index != -1) {
					routers.splice(index, 1);
				}
			});

			return this;
		}

		/**
		 * set the default request handler
		 *
		 * @param {Router} router
		 * @param {string} method http method
		 */
		setDefaultRouter(router, method) {

			normalize(method).forEach(method => this.defaultRouter[method] = router);
		}

		/**
		 * @returns {Router}
		 */
		getDefaultRouter() {


			for (let method in this.defaultRouter) {

				return this.defaultRouter[method];
			}

			return undef$1;
		}
	}

	Utils.merge(true, Route.prototype, Event);

	/**
	 * @property {[]} plugins plugins that respond to events on this router
	 * @property {string} strategy router strategy name
	 * @property {routerPath} path path used to match requests
	 * @property {routerHandleObject} handler
	 * @property {object} options
	 * @method {Router} on
	 * @method {Router} off
	 * @method {Router} resolve
	 *
	 * @class Router
	 */
	class Router {
		/**
		 *
		 * @param {routerPath} path
		 * @param {routerHandle} handler
		 * @param {RouterOptions} options
		 */
		constructor(path, handler, options = null) {
			const self = this;

			self.plugins = [];
			self.options = Object.assign(
				Object.create(undef$1), {
					mime: []
				},
				options || {}
			);

			self.path = path;
			self.strategy = handler.name;
			self.handler = {
				handle: async event => {

					let plugin, response;

					for (plugin of self.plugins) {

						try {

							response = await plugin.precheck(event);

							if (response instanceof Response) {

								return response;
							}
						} catch (error) {

							console.error({
								error
							});
						}
					}

					response = await handler.handle(event);

					if (response instanceof Response) {

						for (plugin of self.plugins) {

							try {

								await plugin.postcheck(event, response);
							} catch (error) {

								console.error(error.message);
							}
						}
					}

					return response;
				}
			};
		}
		addPlugin(plugin) {

			if (!this.plugins.includes(plugin)) {

				this.plugins.push(plugin);
			}

			return this;
		}

		/**
		 * @param {FetchEvent}
		 * @param {Response}
		 */
		match(event, response) {

			return true;
		}
	}

	Utils.merge(true, Router.prototype, Event);

	/**
	 *
	 * main service worker file
	 * @package     GZip Plugin
	 * @copyright   Copyright (C) 2005 - 2018 Thierry Bela.
	 *
	 * dual licensed
	 *
	 * @license     LGPL v3
	 * @license     MIT License
	 */

	const undef$1 = null; //

	/**
	 * service worker configuration issue
	 * {SWType} SW
	 */
	const SW = Object.create(undef$1);
	//const CRY = "😭";
	//const scope = "{scope}";

	Utils.merge(true, SW, Event);

	Object.defineProperties(SW, {
		/**
		 * app config
		 */
		app: {
			value: Object.create(undef$1)
		},
		/**
		 * app routes
		 */
		routes: {
			value: new Route()
		}
	});
	Object.defineProperties(SW.app, {
		/**
		 * app name
		 */
		name: {
			value: "gzip",
			enumerable: true
		},
		/**
		 * service worker scope
		 */
		scope: {
			value: "{scope}",
			enumerable: true
		},
		/**
		 * cache path prefix
		 */
		route: {
			value: "{ROUTE}",
			enumerable: true
		},
		/**
		 * IndexedDb cache name
		 */
		cacheName: {
			value: "{CACHE_NAME}",
			enumerable: true
		},
		/**
		 * app code name
		 */
		codeName: {
			value: "Joomla Website Optimizer Plugin",
			enumerable: true
		},
		/**
		 * service worker build number
		 */
		build: {
			value: "{VERSION}",
			enumerable: true
		},
		/**
		 * service worker build id
		 */
		buildid: {
			value: "build-id",
			enumerable: true
		},
		/**
		 * service worker buid date
		 */
		builddate: {
			value: "build-date",
			enumerable: true
		},
		/**
		 * cdn hosts
		 */
		urls: {
			value: "{CDN_HOSTS}",
			enumerable: true
		},
		/**
		 * background sync settings
		 */
		backgroundSync: {
			value: "{BACKGROUND_SYNC}",
			enumerable: true
		},
		/**
		 * offline page
		 */
		offline: {
			value: "{pwa_offline_page}"
		},
		/**
		 * cache settings
		 */
		network: {
			value: "{pwa_cache_settings}"
		},
		customNetwork: {

			value: "{pwa_custom_cache_settings}"
		},
		/**
		 * precached resources
		 */
		precache: {
			value: "{preloaded_urls}".map(url => new URL(url, self.origin).href),
			enumerable: true
		},
		homepage: {
			value: "https://github.com/tbela99/gzip",
			enumerable: true
		}
	});

	/**
	 *
	 * @package     GZip Plugin
	 * @copyright   Copyright (C) 2005 - 2018 Thierry Bela.
	 *
	 * dual licensed
	 *
	 * @license     LGPL v3
	 * @license     MIT License
	 */

	const cacheName = "{CACHE_NAME}";
	//let undef = null;

	const serializableProperties = [
	    'method',
	    'referrer',
	    'referrerPolicy',
	    'mode',
	    'credentials',
	    'cache',
	    'redirect',
	    'integrity',
	    'keepalive',
	];
	/*
	function nextRetry(n, max = 1000 * 60 * 60) {

	    // 1 hour
	    return Math.min(max, 1 / 2 * (2 ** n - 1));
	}
	*/

	// store and replay syncs

	class SyncManager {

	    /**
	     * 
	     * @param {Request} request 
	     */
	    async push(request) {

	        const data = await this.cloneRequestData(request);
	        const db = await this.getDB();

	        await db.put({
	            id: hashCode(request.url + serializableProperties.map(async name => {

	                let value = data[name];

	                if (value == undef$1) {

	                    return '';
	                }

	                if (name == 'headers') {

	                    if (value instanceof Headers) {

	                        return [...value.values()].filter(value => value != undef$1).join('');
	                    }

	                    return Object.values(value).map(value => data[name][value] != undef$1 ? data[name][value] : '').join('');
	                }

	                if (name == 'body') {

	                    return await value.text();
	                }

	                return value;
	            }).join('')),
	            //    retry: 0,
	            lastRetry: Date.now() + 1000 * 60 * 60 * 24,
	            url: request.url,
	            request: data
	        });

	        return this;
	    }

	    /**
	     * 
	     * @param {Request} request 
	     */
	    async cloneRequestData(request) {

	        const requestData = {
	            headers: {},
	        };

	        // Set the body if present.
	        if (request.method !== 'GET') {
	            // Use ArrayBuffer to support non-text request bodies.
	            // NOTE: we can't use Blobs because Safari doesn't support storing
	            // Blobs in IndexedDB in some cases:
	            // https://github.com/dfahlander/Dexie.js/issues/618#issuecomment-398348457
	            requestData.body = await request.clone().arrayBuffer();
	        }

	        // Convert the headers from an iterable to an object.
	        for (const [key, value] of request.headers.entries()) {

	            requestData.headers[key] = value;
	        }

	        // Add all other serializable request properties
	        for (const prop of serializableProperties) {

	            if (request[prop] !== undefined) {

	                requestData[prop] = request[prop];
	            }
	        }

	        // If the request's mode is `navigate`, convert it to `same-origin` since
	        // navigation requests can't be constructed via script.
	        if (requestData.mode === 'navigate') {

	            requestData.mode = 'same-origin';
	        }

	        return requestData;
	    }

	    /**
	     * @returns {Promise<DBType>}
	     */
	    async getDB() {

	        if (this.db == undef$1) {

	            this.db = await DB('gzip_sw_worker_sync_requests', 'id');
	        }

	        return this.db;
	    }

	    async replay(tag) {

	        if (tag != "{SYNC_API_TAG}") {

	            return
	        }

	        const db = await this.getDB();
	        const requests = await db.getAll();

	        if (requests.length > 0) {

	            console.info('attempting to sync background requests ...');
	        }

	        const cache = await caches.open(cacheName);

	        for (const data of requests) {

	            let remove = false;

	            try {

	                console.info('attempting to replay background requests: [' + data.request.method + '] ' + data.url);

	                const request = new Request(data.url, data.request);

	                let response = await cache.match(request);

	                remove = response != undef$1;

	                if (!remove) {

	                    response = await fetch(request.clone());

	                    remove = response != undef$1 && response.ok;

	                    if (remove && isCacheableRequest(request, response)) {

	                        await cache.put(request, response);
	                    }
	                }

	            } catch (e) {

	            }

	            if (remove || data.lastRetry <= Date.now()) {

	                await db.delete(data.id);
	            }
	        }
	    }
	}

	/**
	 *
	 * @package     GZip Plugin
	 * @copyright   Copyright (C) 2005 - 2018 Thierry Bela.
	 *
	 * dual licensed
	 *
	 * @license     LGPL v3
	 * @license     MIT License
	 */

	// @ts-check
	/* eslint wrap-iife: 0 */

	/**
	 *
	 * Exponentially increase the delay until it reaches max hours. It will no longer increase from that point.
	 * - 0
	 * - 1 minute
	 * - 2 minutes
	 * - 4 minutes
	 * - 8 minutes
	 * - 16 minutes
	 * - 32 minutes
	 * - max hours ...
	 * @param {number} max
	 * @returns {function(number): number}
	 */
	function expo(max = 1) {

		max *= 3600;

		return function (n) {

			// 1 hour max
			if (n > 12.8139) {

				n = 12.8139;
			}

			return 1000 * Math.min(max, 1 / 2 * (2 ** n - 1));
		}
	}

	/**
	 *
	 * @package     GZip Plugin
	 * @copyright   Copyright (C) 2005 - 2018 Thierry Bela.
	 *
	 * dual licensed
	 *
	 * @license     LGPL v3
	 * @license     MIT License
	 */

	// @ts-check
	const syncSettings = "{BACKGROUND_SYNC}";

	if (syncSettings.enabled) {

	    const manager = new SyncManager;
	    let timeout = 0;

	    // retry using back off algorithm
	    // - 0
	    // - 1 minute
	    // - 2 minutes
	    // - 4 minutes
	    // - 8 minutes
	    // - 16 minutes
	    // - 32 minutes
	    // - 60 minutes ...
	    const nextRetry = expo();

	    async function replay() {

	        await manager.replay("{SYNC_API_TAG}");

	        setTimeout(replay, nextRetry(++timeout));
	    }

	    setTimeout(replay, nextRetry(timeout));

	}

}());
