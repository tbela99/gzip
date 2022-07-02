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

	function getOwnPropertyDescriptorNames(object) {
		let properties = Object.keys(Object.getOwnPropertyDescriptors(object));
		let current = Object.getPrototypeOf(object);
		while (current) {
			properties = properties.concat(
				Object.keys(Object.getOwnPropertyDescriptors(current))
			);

			current = Object.getPrototypeOf(current);
		}

		return properties;
	}

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
	 *
	 * @class RegExpRouter
	 * @extends Router
	 * @inheritdoc
	 */
	class RegExpRouter extends Router {
		/**
		 *
		 * @param {FetchEvent} event
		 */
		match(event, response) {
			const url = event.request.url;
			return (/^https?:/.test(url) && this.path.test(url)) ||
				this.options.mime.includes(event.request.headers.get('Content-Type')) ||
				(response != undef$1 && this.options.mime.includes(response.headers.get('Content-Type')));
		}
	}

	/**
	 * @property {URL} url
	 * @class ExpressRouter
	 * @extends Router
	 * @inheritdoc
	 */
	class ExpressRouter extends Router {
		/**
		 * @inheritdoc
		 */
		/**
		 * Creates an instance of ExpressRouter.
		 * @param  {string} path
		 * @param  {routerHandle} handler
		 * @param  {object} options
		 * @memberof ExpressRouter
		 */
		constructor(path, handler, options = null) {
			super(path, handler, options);
			this.url = new URL(path, self.origin);
		}

		/**
		 *
		 * @param {FetchEvent} event
		 */
		match(event, response) {
			const url = event.request.url;
			const u = new URL(url);

			return (
					/^https?:/.test(url) &&
					u.origin == this.url.origin &&
					u.pathname.indexOf(this.url.pathname) == 0
				) ||
				this.options.mime.includes(event.request.headers.get('Content-Type')) ||
				(response != undef$1 && this.options.mime.includes(response.headers.get('Content-Type')));
		}
	}

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
			value: "79e7040",
			enumerable: true
		},
		/**
		 * service worker buid date
		 */
		builddate: {
			value: "2022-06-25 09:57:11-04:00",
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

	function getObjectHash(object) {
		let toString = "",
			property,
			value,
			key,
			i = 0,
			j;

		if ((!object && typeof object == "object") || typeof object == "string") {
			toString = "" + (object == "string" ? JSON.stringify(object) : object);
		} else {
			const properties = getOwnPropertyDescriptorNames(object);

			for (; i < properties.length; i++) {
				property = properties[i];

				try {
					value = object[property];
				} catch (e) {
					//	console.error(property, object, e);
					toString += "!Error[" + JSON.stringify(e.message) + "],";
					continue;
				}

				toString += property + ":";

				if (Array.isArray(value)) {
					toString += "[";

					for (j = 0; j < value.length; j++) {
						toString += getObjectHash(value[j]) + ",";
					}

					if (toString[toString.length - 1] == ",") {
						toString = toString.substr(0, toString.length - 2);
					}

					toString += "]";
				} else if (typeof value == "object") {
					/* eslint max-depth: 0 */
					if (!value || typeof value == "string") {
						toString += "" + value;
					} else if (value[Symbol.iterator] != null) {
						if (value.constructor && value.constructor.name) {
							toString += value.constructor.name;
						}

						if (typeof value.forEach == "function") {
							toString += "{";

							/* eslint no-loop-func: 0 */
							value.forEach(
								(value, key) =>
								(toString +=
									key +
									":" +
									getObjectHash(value) +
									",")
							);

							if (toString[toString.length - 1] == ",") {
								toString = toString.substr(0, toString.length - 2);
							}

							toString += "}";
						} else {
							toString += "[";

							for (key of value) {
								toString += getObjectHash(key) + ",";
							}

							if (toString[toString.length - 1] == ",") {
								toString = toString.substr(0, toString.length - 2);
							}

							toString += "]";
						}
					} else {
						toString += "{" + getObjectHash(value) + "}";
					}
				} else {
					toString += JSON.stringify(value);
				}

				toString += ",";
			}

			if (toString[toString.length - 1] == ",") {
				toString = toString.substr(0, toString.length - 2);
			}

			if (Array.isArray(object)) {
				toString = "[" + toString + "]";
			} else if (typeof object == "object") {
				toString = "{" + toString + "}";
			}
		}

		return toString;
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

	function num2FileSize(size, units) {

		if(size == 0) return 0;

		const s = ['b', 'Kb', 'Mb', 'Gb', 'Tb', 'Pb'], e = Math.floor(Math.log(size) / Math.log(1024));

		return (size/ Math.pow(1024, Math.floor(e))).toFixed(2) + " " + (units && units[e] ? units[e] : s[e]);
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

	function sprintf(string) {

		let index = -1;
		let value;
		const args = [].slice.apply(arguments).slice(1);

		return string.replace(/%([s%])/g, function (all, modifier) {

			if (modifier == '%') {

				return modifier;
			}

			value = args[++index];

			switch (modifier) {

				case 's':

					return value == null ? '' : value;
			}
		});
	}

	function capitalize(string) {

		return string[0].toUpperCase() + string.slice(1);
	}

	function ellipsis (string, max = 30, end = 15, fill = '...') {

		if(string.length > max) return string.slice(0, max - end - fill.length + 1) + fill + string.slice(string.length - end + 1);

		return string
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
	//import {
	//	expo
	//} from "../utils/sw.backoff.js";

	// @ts-check
	//SW.expiration = (function() {
	const CRY = "😭";
	//const undef = null;
	let cache;

	caches.open(SW.app.cacheName).then(c => cache = c);

	/**
	 * @property {DBType} db
	 * @class CacheExpiration
	 */

	class CacheExpiration {

		constructor(options) {

			this.setOptions(options);
		}

		/**
		 *
		 * @param {String} url
		 * @returns {string|null}
		 */
		getRouteTag(url) {
			const route = SW.app.route;
			let host;

			for (host of SW.app.urls) {
				if (new RegExp("^https?://" + host + SW.app.scope + route + "/").test(url)) {
					return route;
				}
			}

			return undef$1;
		}

		/**
		 *
		 * @param options
		 * @returns {Promise<void>}
		 */
		async setOptions(options) {

			const date = new Date;
			const now = +date;

			this.maxAge = 0;
			this.limit = +options.limit || 0;
			this.maxFileSize = +options.maxFileSize || 0;

			const match = options.maxAge.match(/([+-]?\d+)(.*)$/);

			if (match != null) {

				switch (match[2]) {

					//	case 'seconds':
					case 'months':
					case 'minutes':
					case 'hours':

						let name = capitalize(match[2]);

						if (name == 'Months') {

							name = 'Month';
						}

						date['set' + name](+match[1] + date['get' + name]());
						this.maxAge = date - now;
				}
			}

			this.db = await DB(
				options.cacheName != undef$1 ?
				options.cacheName :
				"gzip_sw_worker_expiration_cache_private",
				"url",
				[{
						name: "url",
						key: "url"
					},
					{
						name: "version",
						key: "version"
					},
					{
						name: "route",
						key: "route"
					}
				]
			);
		}

		/**
		 *
		 * @param {FetchEvent} event
		 * @returns {Promise<Response|boolean>}
		 */
		async precheck(event) {
			try {
				if (this.db == undef$1 || this.maxAge == 0) {
					return true;
				}

				const version = hashCode(getObjectHash(event.request));
				const entry = await this.db.get(event.request.url, "url");

				if (
					entry != undef$1 &&
					(entry.version != version || entry.timestamp < Date.now())
				) {

					await caches.delete(event.request);
					return true;
				}

				return await cache.match(event.request);
			} catch (e) {
				console.error(CRY, e);
			}

			// todo ->delete expired
			// todo -> delete if count > limit

			return true;
		}

		/**
		 * 
		 * @param {FetchEvent} event 
		 * @param {Response} response 
		 */
		async postcheck(event, response) {

			if (this.db == undef$1) {
				return true;
			}

			if (this.maxFileSize > 0) {

				if (response.body != undef$1) {

					const blob = await response.clone().blob();

					if (blob.size > this.maxFileSize) {

						console.info(sprintf('cache limit exceeded. Deleting item [%s]', ellipsis(response.url)));

						// delete any cached response
						await this.db.delete(response.url);
						await cache.delete(response);
						throw new Error(sprintf('[%s][cache failed] cache size limit exceeded %s of %s', ellipsis(response.url, 42), num2FileSize(blob.size), num2FileSize(this.maxFileSize)));
					}
				}
			}

			try {
				const url = event.request.url;
				const entry = await this.db.get(url, "url");
				const version = hashCode(getObjectHash(event.request));

				if (
					entry == undef$1 ||
					entry.version != version ||
					entry.timestamp < Date.now()
				) {

					// need to update
					return await this.db.put({
						url,
						//	method: event.request.method,
						timestamp: Date.now() + this.maxAge,
						route: this.getRouteTag(url),
						version
					});
				}

				return url;
			} catch (e) {
				console.error(CRY, e);
			}

			return true;
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
	 * @subpackage  System.Gzip
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
	 */
	//SW.strategies = (function() {

	const map = new Map();

	const strategies = {
		/**
		 *
		 * @param {string} key
		 * @param {routerHandle} handle
		 */
		add: (key, handle, name) =>
			map.set(key, {
				key,
				name: name == undef$1 ? key : name,
				handle: async event => {
					const response = await handle(event);

					return response;
				}
			}),
		/**
		 *
		 * @returns {IterableIterator<string>}
		 */
		keys: () => map.keys(),
		/**
		 *
		 * @returns {IterableIterator<routerHandleObject>}
		 */
		values: () => map.values(),
		/**
		 *
		 * @returns {IterableIterator<[function]>}
		 */
		entries: () => map.entries(),
		/**
		 *
		 * @param {string} name
		 * @returns {routerHandleObject}
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
		isCacheableRequest
	};

	// if opaque response <- crossorigin? you should use cache.addAll instead of cache.put dude <- stop it!
	// if http response != 200 <- hmmm don't want to cache this <- stop it!
	// if auth != basic <- are you private? <- stop it!

	strategies[Symbol.iterator] = () => map[Symbol.iterator]();
	Object.defineProperty(strategies, "size", {
		get: () => map.size
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

	const cacheName = SW.app.cacheName;


	async function cacheFirst(event) {
		"use strict;";

		let response = await caches.match(event.request, {
			cacheName
		});

		if (response != null) {
			return response;
		}

		response = await fetch(event.request);

		if (strategies.isCacheableRequest(event.request, response)) {
			const cloned = response.clone();
			caches.open(cacheName).then(cache => cache.put(event.request, cloned));
		}

		return response;
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

	const cacheName$1 = SW.app.cacheName;

	async function cacheNetwork(event) {
		"use strict;";

		const response = await caches.match(event.request, {
			cacheName: cacheName$1
		});

		const fetchPromise = fetch(event.request).then(networkResponse => {
			// validate response before
			if (strategies.isCacheableRequest(event.request, networkResponse)) {
				const cloned = networkResponse.clone();
				caches
					.open(cacheName$1)
					.then(cache => cache.put(event.request, cloned));
			}

			return networkResponse;
		}).catch(( /*error*/ ) => {

			// cache update failed
			/* console.error("😭", error) */
		});

		return response || fetchPromise;
		//	});
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

	const cacheName$2 = SW.app.cacheName;

	async function cacheOnly(event) {
		return await caches.match(event.request, {
			cacheName: cacheName$2
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

	const cacheName$3 = SW.app.cacheName;

	async function networkFirst(event) {
		"use strict;";

		try {
			const response = await fetch(event.request);

			//	.then(response => {
			if (response == null) {
				throw new Error("Network error");
			}

			if (strategies.isCacheableRequest(event.request, response)) {
				const cloned = response.clone();
				caches
					.open(cacheName$3)
					.then(cache => cache.put(event.request, cloned));
			}

			return response;
			//	})
		} catch (error) {
		//	console.error("😭", error);
		}

		return caches.match(event.request, {cacheName: cacheName$3});
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

	// or simply don't call event.respondWith, which will result in default browser behaviour
	async function networkOnly(event) {
		return fetch(event.request);
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

	strategies.add("cf", cacheFirst, "Cache fallback to Network");
	strategies.add("cn", cacheNetwork, "Cache and Network Update");
	strategies.add("co", cacheOnly, "Cache Only");
	strategies.add("nf", networkFirst, "Network fallback to Cache");
	strategies.add("no", networkOnly, "Network Only");

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

	self.addEventListener("activate", event => {
		// delete old app owned caches
		event.waitUntil(
			(async () => {
				try {
					await SW.resolve("activate", event);
				} catch (e) {
					console.error("😭", e);
				}
				return self.clients.claim();
			})()
		);
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

	//const undef = null;
	/**
	 * @param {FetchEvent} event
	 */

	self.addEventListener("fetch", (event) => {

		event.respondWith(!event.url || (event.request.cache === 'only-if-cached' && event.request.mode !== 'same-origin') ? fetch(event.request) : (async function () {

			let response;

			const router = SW.routes.getRouter(event);

			if (router != undef$1) {

				try {

					response = await router.handler.handle(event);

					if (response instanceof Response) {

						return response;
					}

					for (response of await SW.routes.resolve('fail', event, response)) {

						if (response instanceof Response) {

							return response;
						}
					}

					// offline page should be returned from the previous loop
				} catch (error) {

					console.error("😭", error);
				}
			}

			return fetch(event.request);
		})());
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

	self.addEventListener("install", event => {
		event.waitUntil(
			(async () => {
				try {
					await SW.resolve("install", event);
				} catch (e) {
					console.error("😭", e);
				}
				return self.skipWaiting();
			})()
		);
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

	const cacheName$4 = "{CACHE_NAME}";
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

	        const cache = await caches.open(cacheName$4);

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
	// // import {
	//    TaskManager
	// } from "../task/sw.task.manager.js";

	if (SW.app.backgroundSync.enabled) {

	    //   const taskManager = new TaskManager();
	    const manager = new SyncManager();

	    SW.on({

	        /**
	         * clear data from previous worker instance
	         */
	        async install() {

	            for (const name of ['gzip_sw_worker_sync_requests', 'gzip_sw_worker_sync_tasks']) {

	                const db = await DB(name, 'id');

	                if (db != null) {

	                    await db.clear();
	                }
	            }
	        }
	    });

	    SW.routes.on({
	        /**
	         * 
	         * @param {Request} event 
	         * @param {Response} response 
	         */
	        fail(request, response) {

	            console.info('failed request detected! trying to schedule background sync');

	            const options = SW.app.backgroundSync;

	            if (options.length == 0 || options.method.indexOf(request.method) != -1) {

	                const location = request.url.replace(self.origin, '');

	                if (options.pattern.length == 0 || options.pattern.some(pattern => location.indexOf(pattern) == 0)) {

	                    return manager.push(request);
	                }
	            }
	        }
	    });

	    self.addEventListener("sync",
	        /**
	         * {SyncEvent} event
	         */
	        function (event) {

	            // tears of joy
	            console.info('sync event supported 😭');
	            console.info('Sync Tag ' + event.tag);

	            event.waitUntil(
	                manager.replay(event.tag).
	                // then(() => taskManager.run(event.tag)).
	                catch(error => console.error({
	                    error
	                }))
	            );
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

	/**
	 * enforce the limitation of the number of files in the cache
	 */
	const cleanup = (async function () {

		let cache = await caches.open('{CACHE_NAME}');

		const preloaded_urls = "{preloaded_urls}".map(url => new URL(url, self.origin).href);

		const limit = "{pwa_cache_max_file_count}";
		const db = await DB(
			"gzip_sw_worker_expiration_cache_private",
			"url",
			[{
					name: "url",
					key: "url"
				},
				{
					name: "version",
					key: "version"
				},
				{
					name: "route",
					key: "route"
				}
			]
		);

		return async function () {

			let count = await db.count();

			if (count > limit) {

				console.info(sprintf('cleaning up [%s] items present. [%s] items allowed', count, limit));

				for (let metadata of await db.getAll()) {

					if (preloaded_urls.includes(metadata.url)) {

						console.info(sprintf('skipped preloaded resource [%s]', metadata.url));
						continue;
					}

					console.info(sprintf('removing [%s]', metadata.url));

					await cache.delete(metadata.url);
					await db.delete(metadata.url);

					if (--count <= limit) {

						break;
					}
				}

				console.info(sprintf('cleaned up [%s] items present. [%s] items allowed', count, limit));
			}
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

	if (SW.app.network.limit > 0) {

		self.addEventListener('sync', async (event) => {

			const callback = await cleanup();

			event.waitUntil(callback());
		});
	}

	if (SW.app.offline.enabled) {

	    SW.routes.on('fail', async (event) => {

	        if (event.request.mode == 'navigate' && SW.app.offline.methods.includes(event.request.method)) {

	            if (SW.app.offline.type == 'response') {

	                return new Response(SW.app.offline.body, {
	                    headers: new Headers({
	                        'Content-Type': 'text/html; charset="{offline_charset}"'
	                    })
	                });
	            }

	            if (SW.app.offline.url != '') {

	                return caches.match(SW.app.offline.url);
	            }
	        }
	    });
	}

	/**
	 *
	 * main service worker file
	 *
	 * @package     GZip Plugin
	 * @copyright   Copyright (C) 2005 - 2018 Thierry Bela.
	 *
	 * dual licensed
	 *
	 * @license     LGPL v3
	 * @license     MIT License
	 */

	//const undef = null;
	const route = SW.routes;
	const scope = SW.app.scope;
	const networkSettings = SW.app.network;
	const caching = networkSettings.caching;
	//const cacheExpiryStrategy = "{cacheExpiryStrategy}";
	let entry;
	let router;

	let maxAge;
	let maxFileSize;
	let limit;
	let cacheName$5;
	let strategy;

	({
		limit,
		maxAge,
		cacheName: cacheName$5,
		strategy,
		maxFileSize
	} = networkSettings);

	const defaultCacheSettings = {
		limit,
		maxAge,
		strategy,
		maxFileSize
	};

	// excluded urls fallback on network only
	for (entry of "{exclude_urls}") {
		route.registerRoute(
			new RegExpRouter(new RegExp(entry), strategies.get("no"))
		);
	}

	// excluded urls fallback on network only
	//const network_strategies = "{network_strategies}";
	for (entry of networkSettings.settings) {

		router = new RegExpRouter(
			new RegExp('(' + entry.ext.join(')|(') + ')', "i"),
			strategies.get(entry.strategy),
			entry
		);

		if (caching) {

			({
				limit,
				maxAge,
				//	cacheName,
				maxFileSize
			} = entry);

			router.addPlugin(new CacheExpiration({
				limit,
				maxAge,
				maxFileSize
			}));
		}

		route.registerRoute(router);
	}

	// register strategies routers
	for (entry of strategies) {

		router = new ExpressRouter(
			scope + "{ROUTE}/media/z/" + entry[0] + "/",
			entry[1]
		);

		if (caching) {

			router.addPlugin(new CacheExpiration(defaultCacheSettings));
		}

		route.registerRoute(router);

		// implement encrypted file path support as well as expiry date?
		SW.app.customNetwork.forEach(setting => {

			let sc = scope + "{ROUTE}/" + entry[0] + '/' + setting.prefix + '/';

			delete setting.prefix;

			let router = new ExpressRouter(
				sc,
				setting
			);

	//	if (caching) {

			router.addPlugin(new CacheExpiration(setting));
	//	}

			route.registerRoute(router);
		});
	}

	router = new ExpressRouter(scope, strategies.get(networkSettings.strategy));

	if (caching) {

		router.addPlugin(new CacheExpiration(defaultCacheSettings));
	}

	route.setDefaultRouter(router);

	cacheName$5 = SW.app.cacheName;

	// service worker activation
	SW.on({
		error(error, event) {

			console.error({
				error,
				event
			});
		},
		async install() {
			console.info("🛠️ service worker install event");

			await caches.open(cacheName$5).then(async cache => await cache.addAll(SW.app.precache));
		},
		async activate() {
			console.info("🚁 service worker activate event");

			const db = await DB("gzip_sw_worker_config_cache_private", "name");

			const settings = await db.get("gzip");

			if (settings != undef$1) {
				if (settings.route != "{ROUTE}") {
					// the url cache prefix has changed! delete private cache expiration data
					let storeName, store;

					for (storeName of "{STORES}") {

						store = await DB(storeName, "url", [{
								name: "url",
								key: "url"
							},
							{
								name: "version",
								key: "version"
							},
							{
								name: "route",
								key: "route"
							}
						]);

						if (store != undef$1) {
							store.clear();
						}
					}
				}
			}

			await db.put(SW.app);

			// delete obsolete caches
			const keyList = await caches.keys();
			const tokens = cacheName$5.split(/_/, 2);
			/**
			 * @var {boolean|string}
			 */
			const search = tokens.length == 2 && tokens[0] + "_";

			// delete older app caches
			if (search != false) {
				await Promise.all(
					keyList.map(
						key =>
						key.indexOf(search) == 0 &&
						key != cacheName$5 &&
						caches.delete(key)
					)
				);
			}
		}
	});

}());
