(function (factory) {
	typeof define === 'function' && define.amd ? define(factory) :
	factory();
}(function () { 'use strict';

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
				count: () => _query("count", true, keyToUse),
				get: (keyToUse, index) => _query("get", true, keyToUse, index),
				getAll: (keyToUse, index) =>
					_query("getAll", true, keyToUse, index),
				put: entryData => _query("put", false, entryData),
				delete: keyToUse => _query("delete", false, keyToUse),
				clear: () => _query("clear", false)
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
				return function() {
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

		//	btoa(str) {
		//		return btoa(unescape(encodeURIComponent(str)));
		//	},

		//	atob(str) {
		//		return decodeURIComponent(escape(atob(str)));
		//	},

		/**
		 *  extend a function to accept either a key/value or an object as arguments
		 * 	ex set(name, value, [...]) or set({name: value, name2: value2}, [...])
		 * @param {Function} fn
		 */
		extendArgs(fn) {
			return function(key) {
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
		},
		getAllPropertiesName(object) {
			const properties = [];
			let current = object,
				props,
				prop,
				i;

			do {
				props = Object.getOwnPropertyNames(current);

				for (i = 0; i < props.length; i++) {
					prop = props[i];
					if (properties.indexOf(prop) === -1) {
						properties.push(prop);
					}
				}
			} while ((current = Object.getPrototypeOf(current)));

			return properties;
		},
		getOwnPropertyDescriptorNames(object) {
			let properties = Object.keys(Object.getOwnPropertyDescriptors(object));
			let current = Object.getPrototypeOf(object);
			while (current) {
				properties = properties.concat(
					Object.keys(Object.getOwnPropertyDescriptors(current))
				);

				current = Object.getPrototypeOf(current);
			}

			return properties;
		},
		getObjectHash(object) {
			return hashCode(getObjectHashString(object)).toString(16);
		}
	};

	function getObjectHashString(object) {
		let toString = "",
			property,
			value,
			key,
			i = 0,
			j;

		if ((!object && typeof object == "object") || typeof object == "string") {
			toString = "" + (object == "string" ? JSON.stringify(object) : object);
		} else {
			const properties = Utils.getOwnPropertyDescriptorNames(object);

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
						toString += getObjectHashString(value[j]) + ",";
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
										getObjectHashString(value) +
										",")
							);

							if (toString[toString.length - 1] == ",") {
								toString = toString.substr(0, toString.length - 2);
							}

							toString += "}";
						} else {
							toString += "[";

							for (key of value) {
								toString += getObjectHashString(key) + ",";
							}

							if (toString[toString.length - 1] == ",") {
								toString = toString.substr(0, toString.length - 2);
							}

							toString += "]";
						}
					} else {
						toString += "{" + getObjectHashString(value) + "}";
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

	function hashCode(string) {
		let hash = 0,
			char,
			i;

		if (string.length == 0) {
			return hash;
		}

		for (i = 0; i < string.length; i++) {
			char = string.charCodeAt(i);

			hash = (hash << 5) - hash + char;

			hash = hash & hash; // Convert to 32bit integer
		}

		return hash;
	}

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
								target[prop] != undef
								? target[prop]
								: Array.isArray(value)
									? []
									: {},
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

	const undef$1 = null;
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

		// invoke event handlers
		trigger(name) {

			name = name.toLowerCase();

			const self = this;
			const args = arguments.length > 1 ? [].slice.call(arguments, 1) : [];
			const events = self.$events[name] || [];

			let i = 0;

			for (; i < events.length; i++) {

				events[i].cb.apply(self, args);
			}

			return this;
		},
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

	const undef$2 = null;
	const CRY = "😭";

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

			if (method == undef$2 || method == "HEAD") {
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
			this.routers = Object.create(undef$2);
			this.defaultRouter = Object.create(undef$2);
		}


		/**
		 * get the handler that matches the request event
		 *
		 * @param {FetchEvent} event
		 * @return {RouteHandler}
		 */
		getRouter(event) {
			const method = (event != undef$2 && event.request.method) || "GET";
			const routes = this.routers[method] || [];
			const j = routes.length;
			let route,
				i = 0;

			for (; i < j; i++) {
				route = routes[i];

				if (route.match(event)) {
					/*	console.info({
							match: true,
							strategy: route.strategy,
							name: route.constructor.name,
							url: event.request.url,
							path: route.path,
							route
						});*/
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
	}

	Utils.merge(true, Route.prototype, Event);

	/**
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

			self.options = Object.assign(
				Object.create({
					plugins: []
				}),
				options || {}
			);

			self.path = path;
			self.strategy = handler.name;
			self.handler = {
				handle: async event => {
					// before route
					let result;
					let response, res, plugin;

					try {
						for (plugin of self.options.plugins) {
							res = await plugin.precheck(event, response);

							if (response == undef$2 && res instanceof Response) {
								response = res;
							}
						}

						/*
						console.log({
							precheck: "precheck",
							match: response instanceof Response,
							response,
							router: self,
							url: event.request.url
						});
						*/

						if (response instanceof Response) {
							return response;
						}

						result = await self.resolve("beforeroute", event, response);

						for (response of result) {
							if (response instanceof Response) {
								return response;
							}
						}
					} catch (error) {
						console.error(CRY, error);
					}

					response = await handler.handle(event);
					result = await self.resolve("afterroute", event, response);

					for (plugin of self.options.plugins) {
						await plugin.postcheck(event, response);
					}

					for (res of result) {
						if (res instanceof Response) {
							return res;
						}
					}

					return response;
				}
			};
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
		match(event) {
			const url = event.request.url;
			return /^https?:/.test(url) && this.path.test(url);
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
		constructor(path, handler, options) {
			super(path, handler, options);
			this.url = new URL(path, self.origin);
		}

		/**
		 *
		 * @param {FetchEvent} event
		 */
		match(event) {
			const url = event.request.url;
			const u = new URL(url);

			return (
				/^https?:/.test(url) &&
				u.origin == this.url.origin &&
				u.pathname.indexOf(this.url.pathname) == 0
			);
		}
	}

	/**
	 *
	 * @class CallbackRouter
	 * @extends Router
	 * @inheritdoc
	 */
	class CallbackRouter extends Router {
		/**
		 *
		 * @param {FetchEvent} event
		 */
		match(event) {
			return this.path(event.request.url, event);
		}
	}

	Router.RegExpRouter = RegExpRouter;
	Router.ExpressRouter = ExpressRouter;
	Router.CallbackRouter = CallbackRouter;

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

	const undef$3 = null; //

	/**
	 *
	 * {SWType} SW
	 */
	const SW = Object.create(undef$3);
	const cacheName = "{CACHE_NAME}";
	//const CRY = "😭";
	//const scope = "{scope}";

	Utils.merge(true, SW, Event);

	Object.defineProperties(SW, {
		app: {
			value: Object.create(undef$3)
		},
		routes: {
			value: new Route()
		}
	});
	Object.defineProperties(SW.app, {
		name: {
			value: "gzip",
			enumerable: true
		},
		scope: {
			value: "{scope}",
			enumerable: true
		},
		route: {
			value: "{ROUTE}",
			enumerable: true
		},
		cacheName: {
			value: "{CACHE_NAME}",
			enumerable: true
		},
		codeName: {
			value: "Page Optimizer Plugin",
			enumerable: true
		},
		build: {
			value: "{VERSION}",
			enumerable: true
		},
		buildid: {
			value: "26940ea",
			enumerable: true
		},
		builddate: {
			value: "2019-07-03 08:24:30-04:00",
			enumerable: true
		},
		urls: {
			value: "{CDN_HOSTS}",
			enumerable: true
		},
		backgroundSync: {
			value: "{BACKGROUND_SYNC}",
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

	// @ts-check
	//SW.expiration = (function() {
	const CRY$1 = "😭";
	const undef$4 = null;
	const expiration = Object.create(undef$4);

	/**
	 * @property {DBType} db
	 * @class CacheExpiration
	 */

	class CacheExpiration {
		constructor(options) {
			this.setOptions(options);
		}

		getRouteTag(url) {
			const route = SW.app.route;
			let host;

			for (host of SW.app.urls) {
				if (new RegExp("^https?://" + host + "/" + route + "/").test(url)) {
					return route;
				}
			}

			return undef$4;
		}

		async setOptions(options) {
			//cacheName = "gzip_sw_worker_expiration_cache_private",
			//	limit = 0,
			//	maxAge = 0
			//
			this.limit = +options.limit || 0;
			this.maxAge = +options.maxAge * 1000 || 0;

			try {
				this.db = await DB(
					options.cacheName != undef$4
						? options.cacheName
						: "gzip_sw_worker_expiration_cache_private",
					"url",
					[
						{name: "url", key: "url"},
						{name: "version", key: "version"},
						{name: "route", key: "route"}
					]
				);
			} catch (e) {
				console.error(CRY$1, e);
			}
		}

		async precheck(event) {
			try {
				if (this.db == undef$4) {
					return true;
				}

				const version = Utils.getObjectHash(event.request);
				const entry = await this.db.get(event.request.url, "url");
				const cache = await caches.open(cacheName);

				if (
					entry != undef$4 &&
					(entry.version != version || entry.timestamp < Date.now())
				) {
					console.info(
						"CacheExpiration [precheck][obsolete][" +
							version +
							"] " +
							event.request.url
					);

					caches.delete(event.request);
					return true;
				}

				return await cache.match(event.request);
			} catch (e) {
				console.error(CRY$1, e);
			}

			// todo ->delete expired
			// todo -> delete if count > limit

			return true;

			//	return (
			//		entries == undef || Date.now() - entry.timestamp < this.maxAge
			//	);
		}

		async postcheck(event) {
			if (this.db == undef$4) {
				return true;
			}

			try {
				const url = event.request.url;
				const entry = await this.db.get(url, "url");
				const version = Utils.getObjectHash(event.request);

				if (
					entry == undef$4 ||
					entry.version != version ||
					entry.timestamp < Date.now()
				) {
					console.info(
						"CacheExpiration [postcheck][update][version=" +
							version +
							"][expires=" +
							(Date.now() + this.maxAge) +
							"|" +
							new Date(Date.now() + this.maxAge).toUTCString() +
							"] " +
							url,
						this
					);

					// need to update
					return await this.db.put({
						url,
						method: event.request.method,
						timestamp: Date.now() + this.maxAge,
						route: this.getRouteTag(url),
						version
					});
				} else {
					console.info(
						"CacheExpiration [postcheck][no update][version=" +
							version +
							"][expires=" +
							entry.timestamp +
							"|" +
							new Date(entry.timestamp).toUTCString() +
							"] " +
							url,
						entry
					);
				}

				return url;
			} catch (e) {
				console.error(CRY$1, e);
			}

			return true;
		}
	}

	expiration.CacheExpiration = CacheExpiration;

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
	const undef$5 = null;

	const strategies = {
		/**
		 *
		 * @param {string} key
		 * @param {routerHandle} handle
		 */
		add: (key, handle, name) =>
			map.set(key, {
				key,
				name: name == undef$5 ? key : name,
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

	async function cacheNetwork(event) {
		"use strict;";

		const response = await caches.match(event.request, {
			cacheName
		});

		const fetchPromise = fetch(event.request).then(networkResponse => {
			// validate response before
			if (strategies.isCacheableRequest(event.request, networkResponse)) {
				const cloned = networkResponse.clone();
				caches
					.open(cacheName)
					.then(cache => cache.put(event.request, cloned));
			}

			return networkResponse;
		}).catch(error => {

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

	async function cacheOnly(event) {
		return await caches.match(event.request, {
			cacheName
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
					.open(cacheName)
					.then(cache => cache.put(event.request, cloned));
			}

			return response;
			//	})
		} catch (error) {
		//	console.error("😭", error);
		}

		return caches.match(event.request, {cacheName});
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

	/**
	 * @param {FetchEvent} event
	 */

	self.addEventListener("fetch", (event) => {
		const router = SW.routes.getRouter(event);

		if (router != null) {
			event.respondWith(
				router.handler.handle(event).then(response => {

					if (!(response instanceof Response)) {

						return SW.routes.resolve('fail', event.request, response).then(() => response);
					}

					return response

				}).catch((error) => {
					console.error("😭", error);
					return fetch(event.request);
				})
			);
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

	// @ts-check
	/* eslint wrap-iife: 0 */
	function hashCode$1(string) {
		var hash = 0,
			i, chr;
		if (string.length === 0) return hash;
		for (i = 0; i < string.length; i++) {
			chr = string.charCodeAt(i);
			hash = ((hash << 5) - hash) + chr;
			hash |= 0; // Convert to 32bit integer
		}
		return Number(hash).toString(16);
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

	const cacheName$1 = "{CACHE_NAME}";
	let undef$6 = null;

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
	            id: hashCode$1(request.url + serializableProperties.map(async name => {

	                let value = data[name];

	                if (value == undef$6) {

	                    return '';
	                }

	                if (name == 'headers') {

	                    if (value instanceof Headers) {

	                        return [...value.values()].filter(value => value != undef$6).join('');
	                    }

	                    return Object.values(value).map(value => data[name][value] != undef$6 ? data[name][value] : '').join('');
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

	        if (this.db == undef$6) {

	            this.db = await DB('gzip_sw_worker_sync_requests', 'id');
	        }

	        return this.db;
	    }

	    async replay(tag) {

	        console.log({
	            tag
	        });

	        if (tag != "{SYNC_API_TAG}") {

	            return
	        }

	        const db = await this.getDB();
	        const requests = await db.getAll();

	        if (requests.length > 0) {

	            console.info('attempting to sync background requests ...');
	        }

	        const cache = await caches.open(cacheName$1);

	        for (const data of requests) {

	            let remove = false;

	            try {

	                console.info('attempting to replay background requests: [' + data.request.method + '] ' + data.url);

	                const request = new Request(data.url, data.request);

	                let response = await cache.match(request);

	                remove = response != undef$6;

	                if (!remove) {

	                    response = await fetch(request.clone());

	                    remove = response != undef$6 && response.ok;

	                    if (remove && isCacheableRequest(request, response)) {

	                        await cache.put(request, response);
	                    }
	                }

	            } catch (e) {


	            }

	            if (remove || data.lastRetry <= Date.now()) {

	                console.log({
	                    remove,
	                    expired: data.lastRetry <= Date.now()
	                });
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

	/**
	 * serialize class or function
	 * @param {object|function} task
	 */
	function serialize(task) {

	    const source = task.toString().trim();

	    let type = '',
	        isAsync = Object.getPrototypeOf(task).constructor.name === 'AsyncFunction',
	        body;

	    const data = source.match(/^((class)|((async\s+)?function)?)\s*([^{(]*)[({]/);


	    type = data[1];
	    let name = data[5].trim().replace(/[\s(].*/, '');

	    body = type + ' ' + (name === '' ? task.name : name) + source.substring((type + (name === '' ? name : ' ' + name)).length);

	    if (name === '') {

	        name = task.name;
	    }

	    if (type === '') {

	        type = 'function';
	    }

	    return {
	        type,
	        name,
	        body,
	        isAsync
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

	/**
	 * revive a class or a function
	 * @param {object|function} task
	 * @returns {object|function}
	 */
	function script(serialized) {

	    return eval('(function () { return ' + serialized.body + '})()');
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

	const undef$7 = null;

	/*
	function nextRetry(n, max = 1000 * 60 * 60) {

	    // 1 hour
	    return Math.min(max, 1 / 2 * (2 ** n - 1));
	}
	*/

	class TaskManager {

	    /**
	     *
	     * @param queueName
	     * @returns {Promise<void>}
	     */
	    async run(queueName = "{SYNC_API_TAG}") {

	        const db = await this.getDB();
	        const tasks = await db.getAll();

	        for (const task of tasks) {

	            console.log({
	                task,
	                'task.queueName == queueName': task.queueName == queueName
	            });

	            if (task.queueName == queueName) {

	                const fn = script(task.data);

	                if (task.isAsync) {

	                    await fn();
	                } else fn();
	            }
	        }
	    }

	    /**
	     * @returns {Promise<DBType>}
	     */
	    async getDB() {

	        if (this.db == undef$7) {

	            this.db = await DB('gzip_sw_worker_sync_tasks', 'id');
	        }

	        return this.db;
	    }

	    /**
	     *
	     * @param {function} task
	     * @param {string} queueName
	     * @returns {object}
	     */
	    async register(task, queueName = "{SYNC_API_TAG}") {

	        const data = serialize(task);
	        const db = await this.getDB();
	        return await db.put(

	            {
	                id: hashCode$1(JSON.stringify(data)),
	                queueName,
	                data
	            }
	        );
	    }

	    async unregister(serialized) {

	        const db = await this.getDB();

	        await db.delete(serialized.id);
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

	if (SW.app.backgroundSync.enabled) {

	    const taskManager = new TaskManager();
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
	                manager.replay(event.tag).then(() => taskManager.run(event.tag)).catch(error => console.error({
	                    error
	                }))
	            );
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

	const undef$8 = null;
	const route = SW.routes;
	const scope = SW.app.scope;
	const cacheExpiryStrategy = "{cacheExpiryStrategy}";
	let entry;
	let option;

	let defaultStrategy = "{defaultStrategy}";

	// excluded urls fallback on network only
	for (entry of "{exclude_urls}") {
		route.registerRoute(
			new Router.RegExpRouter(new RegExp(entry), strategies.get("no"))
		);
	}

	// excluded urls fallback on network only
	for (entry of "{network_strategies}") {
		option = entry[2] || cacheExpiryStrategy;

		route.registerRoute(
			new Router.RegExpRouter(
				new RegExp(entry[1], "i"),
				strategies.get(entry[0]),
				option == undef$8 ?
				option : {
					plugins: [new expiration.CacheExpiration(option)]
				}
			)
		);
	}

	// register strategies routers
	for (entry of strategies) {
		route.registerRoute(
			new Router.ExpressRouter(
				scope + "/{ROUTE}/media/z/" + entry[0] + "/",
				entry[1]
			)
		);
	}

	if (!strategies.has(defaultStrategy)) {
		// default browser behavior
		defaultStrategy = "no";
	}

	route.setDefaultRouter(
		new Router.ExpressRouter("/", strategies.get(defaultStrategy))
	);

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

			await caches.open(cacheName).then(async cache => {
				await cache.addAll("{preloaded_urls}");
			});
		},
		async activate() {
			console.info("🚁 service worker activate event");

			const db = await DB("gzip_sw_worker_config_cache_private", "name");

			//	console.log("{STORES}");

			const settings = await db.get("gzip");

			if (settings != undef$8) {
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

						if (store != undef$8) {
							store.clear();
						}
					}
				}
			}

			await db.put(SW.app);

			// delete obsolete caches
			const keyList = await caches.keys();
			const tokens = cacheName.split(/_/, 2);
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
						key != cacheName &&
						caches.delete(key)
					)
				);
			}
		}
	});

}));
