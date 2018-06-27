(function(e, a) { for(var i in a) e[i] = a[i]; }(this, /******/ (function(modules) { // webpackBootstrap
/******/ 	// The module cache
/******/ 	var installedModules = {};
/******/
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/
/******/ 		// Check if module is in cache
/******/ 		if(installedModules[moduleId]) {
/******/ 			return installedModules[moduleId].exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = installedModules[moduleId] = {
/******/ 			i: moduleId,
/******/ 			l: false,
/******/ 			exports: {}
/******/ 		};
/******/
/******/ 		// Execute the module function
/******/ 		modules[moduleId].call(module.exports, module, module.exports, __webpack_require__);
/******/
/******/ 		// Flag the module as loaded
/******/ 		module.l = true;
/******/
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/
/******/
/******/ 	// expose the modules object (__webpack_modules__)
/******/ 	__webpack_require__.m = modules;
/******/
/******/ 	// expose the module cache
/******/ 	__webpack_require__.c = installedModules;
/******/
/******/ 	// define getter function for harmony exports
/******/ 	__webpack_require__.d = function(exports, name, getter) {
/******/ 		if(!__webpack_require__.o(exports, name)) {
/******/ 			Object.defineProperty(exports, name, { enumerable: true, get: getter });
/******/ 		}
/******/ 	};
/******/
/******/ 	// define __esModule on exports
/******/ 	__webpack_require__.r = function(exports) {
/******/ 		if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 			Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 		}
/******/ 		Object.defineProperty(exports, '__esModule', { value: true });
/******/ 	};
/******/
/******/ 	// create a fake namespace object
/******/ 	// mode & 1: value is a module id, require it
/******/ 	// mode & 2: merge all properties of value into the ns
/******/ 	// mode & 4: return value when already ns object
/******/ 	// mode & 8|1: behave like require
/******/ 	__webpack_require__.t = function(value, mode) {
/******/ 		if(mode & 1) value = __webpack_require__(value);
/******/ 		if(mode & 8) return value;
/******/ 		if((mode & 4) && typeof value === 'object' && value && value.__esModule) return value;
/******/ 		var ns = Object.create(null);
/******/ 		__webpack_require__.r(ns);
/******/ 		Object.defineProperty(ns, 'default', { enumerable: true, value: value });
/******/ 		if(mode & 2 && typeof value != 'string') for(var key in value) __webpack_require__.d(ns, key, function(key) { return value[key]; }.bind(null, key));
/******/ 		return ns;
/******/ 	};
/******/
/******/ 	// getDefaultExport function for compatibility with non-harmony modules
/******/ 	__webpack_require__.n = function(module) {
/******/ 		var getter = module && module.__esModule ?
/******/ 			function getDefault() { return module['default']; } :
/******/ 			function getModuleExports() { return module; };
/******/ 		__webpack_require__.d(getter, 'a', getter);
/******/ 		return getter;
/******/ 	};
/******/
/******/ 	// Object.prototype.hasOwnProperty.call
/******/ 	__webpack_require__.o = function(object, property) { return Object.prototype.hasOwnProperty.call(object, property); };
/******/
/******/ 	// __webpack_public_path__
/******/ 	__webpack_require__.p = "";
/******/
/******/
/******/ 	// Load entry module and return exports
/******/ 	return __webpack_require__(__webpack_require__.s = 14);
/******/ })
/************************************************************************/
/******/ ([
/* 0 */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _db_db_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(1);
/* harmony import */ var _event_sw_event_promise_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(5);
/* harmony import */ var _expiration_sw_expiration_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(2);
/* harmony import */ var _router_sw_router_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(4);
/* harmony import */ var _network_index_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(7);
/* harmony import */ var _serviceworker_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(3);
/* harmony import */ var _utils_sw_utils_js__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(6);
/* harmony import */ var _service_sw_service_activate_js__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(15);
/* harmony import */ var _service_sw_service_fetch_js__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(16);
/* harmony import */ var _service_sw_service_install_js__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(17);
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

// @ts-check
/* eslint wrap-iife: 0 */

// build 1be2ae0 2018-06-27 11:07:00-04:00
/* eslint wrap-iife: 0 */
// validator https://www.pwabuilder.com/
// pwa app image generator http://appimagegenerator-pre.azurewebsites.net/

/**
 *
 * type definitions file
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
 * @typedef SWType
 * @method {callback} SW.resolve
 * @method {callback} SW.on
 * @method {callback} SW.off
 * @property Expiration
 */

/**
 * @typedef {RouteHandler}
 * @property {Router} router
 * @property {RouterOptions} options
 *
 */

/**
 * @typedef {RouterOptions}
 * @property {cacheName} string cache name
 * @property {number} expiration
 *
 */

/**
 *
 * @async
 * @callback routerHandle
 * @param {FetchEvent} event
 */

/**
 * @typedef routerHandleObject
 * @property {object} handler
 * @property {routerHandle} handler.handle
 */

/**
 * @typedef {RegExp|string|URL} routerPath
 */









// SW.PromiseEvent = Event;
_utils_sw_utils_js__WEBPACK_IMPORTED_MODULE_6__["Utils"].merge(true, _serviceworker_js__WEBPACK_IMPORTED_MODULE_5__["SW"], _event_sw_event_promise_js__WEBPACK_IMPORTED_MODULE_1__["Event"]);

const undef = null;
const route = _serviceworker_js__WEBPACK_IMPORTED_MODULE_5__["SW"].routes;
const scope = _serviceworker_js__WEBPACK_IMPORTED_MODULE_5__["SW"].app.scope;
const cacheExpiryStrategy = "{cacheExpiryStrategy}";
let entry;
let option;

let defaultStrategy = "{defaultStrategy}";

// excluded urls fallback on network only
for (entry of "{exclude_urls}") {
	route.registerRoute(
		new _router_sw_router_js__WEBPACK_IMPORTED_MODULE_3__["Router"].RegExpRouter(new RegExp(entry), _network_index_js__WEBPACK_IMPORTED_MODULE_4__["strategies"].get("no"))
	);
}

// excluded urls fallback on network only
for (entry of "{network_strategies}") {
	option = entry[2] || cacheExpiryStrategy;

	route.registerRoute(
		new _router_sw_router_js__WEBPACK_IMPORTED_MODULE_3__["Router"].RegExpRouter(
			new RegExp(entry[1], "i"),
			_network_index_js__WEBPACK_IMPORTED_MODULE_4__["strategies"].get(entry[0]),
			option == undef
				? option
				: {plugins: [new _expiration_sw_expiration_js__WEBPACK_IMPORTED_MODULE_2__["expiration"].CacheExpiration(option)]}
		)
	);
}

// register strategies routers
for (entry of _network_index_js__WEBPACK_IMPORTED_MODULE_4__["strategies"]) {
	route.registerRoute(
		new _router_sw_router_js__WEBPACK_IMPORTED_MODULE_3__["Router"].ExpressRouter(
			scope + "/{ROUTE}/media/z/" + entry[0] + "/",
			entry[1]
		)
	);
}

if (!_network_index_js__WEBPACK_IMPORTED_MODULE_4__["strategies"].has(defaultStrategy)) {
	// default browser behavior
	defaultStrategy = "no";
}

route.setDefaultRouter(
	new _router_sw_router_js__WEBPACK_IMPORTED_MODULE_3__["Router"].ExpressRouter("/", _network_index_js__WEBPACK_IMPORTED_MODULE_4__["strategies"].get(defaultStrategy))
);

// service worker activation
_serviceworker_js__WEBPACK_IMPORTED_MODULE_5__["SW"].on({
	async install() {
		console.info("🛠️ service worker install event");

		await caches.open(_serviceworker_js__WEBPACK_IMPORTED_MODULE_5__["cacheName"]).then(async cache => {
			await cache.addAll("{preloaded_urls}");
		});
	},
	async activate() {
		console.info("🚁 service worker activate event");

		const db = await Object(_db_db_js__WEBPACK_IMPORTED_MODULE_0__["DB"])("gzip_sw_worker_config_cache_private", "name");

		//	console.log("{STORES}");

		const settings = await db.get("gzip");

		if (settings != undef) {
			if (settings.route != "{ROUTE}") {
				// the url cache prefix has changed! delete private cache expiration data
				let storeName, store;

				for (storeName of "{STORES}") {
					console.info({storeName});

					store = await Object(_db_db_js__WEBPACK_IMPORTED_MODULE_0__["DB"])(storeName, "url", [
						{name: "url", key: "url"},
						{name: "version", key: "version"},
						{name: "route", key: "route"}
					]);

					if (store != undef) {
						store.clear();
					}
				}
			}
		}

		await db.put(_serviceworker_js__WEBPACK_IMPORTED_MODULE_5__["SW"].app);

		// delete obsolet caches
		const keyList = await caches.keys();
		const tokens = _serviceworker_js__WEBPACK_IMPORTED_MODULE_5__["cacheName"].split(/_/, 2);
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
						key != _serviceworker_js__WEBPACK_IMPORTED_MODULE_5__["cacheName"] &&
						caches.delete(key)
				)
			);
		}
	}
});






/***/ }),
/* 1 */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "DB", function() { return DB; });
/* LICENSE: MIT LICENSE | https://github.com/msandrini/minimal-indexed-db */
/* global window */

/**
 * @typedef DBType
 * @callback count
 * @callback getEntry
 * @callback getAll
 * @callback put
 * @callback deleteEntry
 * @callback flush
 * @callback then
 * @callback catch
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
			const store = db.createObjectStore(storeName, {keyPath: key});

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
			reject(new Error(e));
		};
		openDBRequest.onupgradeneeded = _upgrade.bind(this);
		openDBRequest.onsuccess = _successOnBuild.bind(this);
		openDBRequest.onerror = _errorOnBuild.bind(this);
	});
}


/***/ }),
/* 2 */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "expiration", function() { return expiration; });
/* harmony import */ var _db_db_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(1);
/* harmony import */ var _serviceworker_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(3);
/* harmony import */ var _utils_sw_utils_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(6);
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
 * - url
 * - method
 * - timestamp ((getHeader(Date) || Date.now()) + maxAge)
 **/





// @ts-check
//SW.expiration = (function() {
const CRY = "😭";
const undef = null;
const expiration = Object.create(undef);

/**
 * @property {DBType} db
 * @class CacheExpiration
 */

class CacheExpiration {
	constructor(options) {
		this.setOptions(options);
	}

	getRouteTag(url) {
		const route = _serviceworker_js__WEBPACK_IMPORTED_MODULE_1__["SW"].app.route;
		let h, host;

		for (host of _serviceworker_js__WEBPACK_IMPORTED_MODULE_1__["SW"].app.urls) {
			if (new RegExp("^https?://" + host + "/" + route + "/").test(url)) {
				return route;
			}
		}

		return undef;
	}

	async setOptions(options) {
		//cacheName = "gzip_sw_worker_expiration_cache_private",
		//	limit = 0,
		//	maxAge = 0
		//
		this.limit = +options.limit || 0;
		this.maxAge = +options.maxAge * 1000 || 0;

		try {
			this.db = await Object(_db_db_js__WEBPACK_IMPORTED_MODULE_0__["DB"])(
				options.cacheName != undef
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
			console.error(CRY, e);
		}
	}

	async precheck(event) {
		try {
			if (this.db == undef) {
				return true;
			}

			const version = _utils_sw_utils_js__WEBPACK_IMPORTED_MODULE_2__["Utils"].getObjectHash(event.request);
			const entry = await this.db.get(event.request.url, "url");
			const cache = await caches.open(_serviceworker_js__WEBPACK_IMPORTED_MODULE_1__["cacheName"]);

			if (
				entry != undef &&
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
			console.error(CRY, e);
		}

		// todo ->delete expired
		// todo -> delete if count > limit

		return true;

		//	return (
		//		entries == undef || Date.now() - entry.timestamp < this.maxAge
		//	);
	}

	async postcheck(event) {
		if (this.db == undef) {
			return true;
		}

		try {
			const url = event.request.url;
			const entry = await this.db.get(url, "url");
			const version = _utils_sw_utils_js__WEBPACK_IMPORTED_MODULE_2__["Utils"].getObjectHash(event.request);

			if (
				entry == undef ||
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
			console.error(CRY, e);
		}

		return true;
	}
}

expiration.CacheExpiration = CacheExpiration;
//	return expiration;
//})();




/***/ }),
/* 3 */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "cacheName", function() { return cacheName; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "SW", function() { return SW; });
/* harmony import */ var _router_sw_router_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(4);
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

// @ts-check

/*  */

// build 1be2ae0 2018-06-27 11:07:00-04:00
/* eslint wrap-iife: 0 */
// validator https://www.pwabuilder.com/
// pwa app image generator http://appimagegenerator-pre.azurewebsites.net/

"use strict;";
"{IMPORT_SCRIPTS}";



const undef = null; //

/**
 *
 * @var {SWType}
 */
const SW = Object.create(undef);
const cacheName = "{CACHE_NAME}";
//const CRY = "😭";
//const scope = "{scope}";

Object.defineProperties(SW, {
	app: {value: Object.create(undef)},
	routes: {value: new _router_sw_router_js__WEBPACK_IMPORTED_MODULE_0__["Route"]()}
});
Object.defineProperties(SW.app, {
	name: {value: "gzip", enumerable: true},
	scope: {value: "{scope}", enumerable: true},
	route: {value: "{ROUTE}", enumerable: true},
	cacheName: {value: "{CACHE_NAME}", enumerable: true},
	codeName: {value: "Page Optimizer Plugin", enumerable: true},
	build: {value: "{VERSION}", enumerable: true},
	buildid: {value: "1be2ae0", enumerable: true},
	builddate: {value: "2018-06-27 11:07:00-04:00", enumerable: true},
	urls: {value: "{CDN_HOSTS}", enumerable: true},
	homepage: {value: "https://github.com/tbela99/gzip", enumerable: true}
});




/***/ }),
/* 4 */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "Route", function() { return Route; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "Router", function() { return Router; });
/* harmony import */ var _event_sw_event_promise_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(5);
/* harmony import */ var _utils_sw_utils_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(6);
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

//s(function(SW) {
//	const weakmap = new WeakMap();




const undef = null;
const CRY = "😭";

function normalize(method = "GET") {
	if (method == undef || method == "HEAD") {
		return "GET";
	}

	return method.toUpperCase();
}

/**
 * request route class
 *
 * @property {Object.<string, Router[]>} routes
 * @property {Object.<string, routerHandle>} defaultRouter
 * @method {function} on
 * @method {function} off
 * @method {function} trigger
 *
 * @class Route
 */
class Route {
	constructor() {
		this.routers = Object.create(undef);
		this.defaultRouter = Object.create(undef);
	}

	/**
	 * get the handler that matches the request event
	 *
	 * @param {FetchEvent} event
	 * @return {RouteHandler}
	 */
	getRouter(event) {
		const method = (event != undef && event.request.method) || "GET";
		const routes = this.routers[method] || [];
		const j = routes.length;
		let route,
			i = 0;

		for (; i < j; i++) {
			route = routes[i];

			if (route.match(event)) {
				console.info({
					match: true,
					strategy: route.strategy,
					name: route.constructor.name,
					url: event.request.url,
					path: route.path,
					route
				});
				return route;
			}
		}

		return this.defaultRouter[method];
	}

	/**
	 * register a handler for an http method
	 *
	 * @param {Router} router router instance
	 * @param {string} method http method
	 */
	registerRoute(router, method = "GET") {
		method = normalize(method);

		if (!(method in this.routers)) {
			this.routers[method] = [];
		}

		this.routers[method].push(router);

		return this;
	}

	/**
	 * unregister a handler for an http method
	 *
	 * @param {Router} router router instance
	 * @param {string} method http method
	 */
	unregisterRoute(router, method) {
		method = normalize(method);

		const routers = this.routers[method] || [];

		const index = routers.indexOf(router);

		if (index != -1) {
			routers.splice(index, 1);
		}

		return this;
	}

	/**
	 * set the default request handler
	 *
	 * @param {Router} router
	 * @param {string} method http method
	 */
	setDefaultRouter(router, method) {
		this.defaultRouter[normalize(method)] = router;
	}
}

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
	constructor(path, handler, options) {
		const self = this;

		self.options = Object.assign(
			Object.create({plugins: []}),
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

						if (response == undef && res instanceof Response) {
							response = res;
						}
					}

					console.log({
						precheck: "precheck",
						match: response instanceof Response,
						response,
						router: self,
						url: event.request.url
					});

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

_utils_sw_utils_js__WEBPACK_IMPORTED_MODULE_1__["Utils"].merge(true, Router.prototype, _event_sw_event_promise_js__WEBPACK_IMPORTED_MODULE_0__["Event"]);

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



//SW.Router = Router;
//SW.route = new Route();
//})(SW);


/***/ }),
/* 5 */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "Event", function() { return Event; });
/* harmony import */ var _utils_sw_utils_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(6);
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

const undef = null;
const extendArgs = _utils_sw_utils_js__WEBPACK_IMPORTED_MODULE_0__["Utils"].extendArgs;

const Event = {
	$events: {},
	$pseudo: {},
	// accept (event, handler)
	// Example: promisify('click:once', function () { console.log('clicked'); }) <- the event handler is fired once and removed
	// accept object with events as keys and handlers as values
	// Example promisify({'click:once': function () { console.log('clicked once'); }, 'click': function () { console.log('click'); }})
	on: extendArgs(function(name, fn, sticky) {
		const self = this;

		if (fn == undef) {
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

			if (parsed == undef) {
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
		Object.defineProperty(event, "sticky", {value: !!sticky});

		self.$events[name].push(event);
	}),
	off: extendArgs(function(name, fn, sticky) {
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
	// return a promise
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

Event.addPseudo("once", function(event) {
	event.cb = function() {
		const context = this;

		const value = event.fn.apply(context, arguments);
		context.off(event.name, event.fn);

		return value;
	};

	return this;
});




/***/ }),
/* 6 */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "Utils", function() { return Utils; });
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




/***/ }),
/* 7 */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _sw_strategies_cache_first_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(8);
/* harmony import */ var _sw_strategies_cache_network_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(10);
/* harmony import */ var _sw_strategies_cache_only_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(11);
/* harmony import */ var _sw_strategies_network_first_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(12);
/* harmony import */ var _sw_strategies_network_only_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(13);
/* harmony import */ var _sw_strategies_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(9);
/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "strategies", function() { return _sw_strategies_js__WEBPACK_IMPORTED_MODULE_5__["strategies"]; });

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








_sw_strategies_js__WEBPACK_IMPORTED_MODULE_5__["strategies"].add("cf", _sw_strategies_cache_first_js__WEBPACK_IMPORTED_MODULE_0__["cacheFirst"], "Cache fallback to Network");
_sw_strategies_js__WEBPACK_IMPORTED_MODULE_5__["strategies"].add("cn", _sw_strategies_cache_network_js__WEBPACK_IMPORTED_MODULE_1__["cacheNetwork"], "Cache and Network Update");
_sw_strategies_js__WEBPACK_IMPORTED_MODULE_5__["strategies"].add("co", _sw_strategies_cache_only_js__WEBPACK_IMPORTED_MODULE_2__["cacheOnly"], "Cache Only");
_sw_strategies_js__WEBPACK_IMPORTED_MODULE_5__["strategies"].add("nf", _sw_strategies_network_first_js__WEBPACK_IMPORTED_MODULE_3__["networkFirst"], "Network fallback to Cache");
_sw_strategies_js__WEBPACK_IMPORTED_MODULE_5__["strategies"].add("no", _sw_strategies_network_only_js__WEBPACK_IMPORTED_MODULE_4__["networkOnly"], "Network Only");




/***/ }),
/* 8 */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "cacheFirst", function() { return cacheFirst; });
/* harmony import */ var _sw_strategies_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(9);
/* harmony import */ var _serviceworker_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(3);
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



async function cacheFirst(event) {
	"use strict;";

	let response = await caches.match(event.request, {
		cacheName: _serviceworker_js__WEBPACK_IMPORTED_MODULE_1__["cacheName"]
	});

	if (response != null) {
		return response;
	}

	response = await fetch(event.request);

	if (_sw_strategies_js__WEBPACK_IMPORTED_MODULE_0__["strategies"].isCacheableRequest(event.request, response)) {
		const cloned = response.clone();
		caches.open(_serviceworker_js__WEBPACK_IMPORTED_MODULE_1__["cacheName"]).then(cache => cache.put(event.request, cloned));
	}

	return response;
}


/***/ }),
/* 9 */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "strategies", function() { return strategies; });
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
const undef = null;

const strategies = {
	/**
	 *
	 * @param {string} key
	 * @param {routerHandle} handle
	 */
	add: (key, handle, name) =>
		map.set(key, {
			key,
			name: name == undef ? key : name,
			handle: async event => {
				const response = await handle(event);

				console.info({
					strategy: name == undef ? key : name,
					responseMode: response.type,
					requestMode: event.request.mode,
					ok: response.ok,
					bodyUsed: response.bodyUsed,
					responseType: response && response.type,
					isCacheableRequest: strategies.isCacheableRequest(
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
	 * @returns {IterableIterator<[any]>}
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
	isCacheableRequest: (request, response) =>
		response instanceof Response &&
		("cors" == response.type ||
			new URL(request.url, self.origin).origin == self.origin) &&
		request.method == "GET" &&
		response.ok &&
		["default", "cors", "basic", "navigate"].includes(response.type) &&
		!response.bodyUsed
};

// if opaque response <- crossorigin? you should use cache.addAll instead of cache.put dude <- stop it!
// if http response != 200 <- hmmm don't want to cache this <- stop it!
// if auth != basic <- are you private? <- stop it!

strategies[Symbol.iterator] = () => map[Symbol.iterator]();
Object.defineProperty(strategies, "size", {get: () => map.size});


//})();


/***/ }),
/* 10 */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "cacheNetwork", function() { return cacheNetwork; });
/* harmony import */ var _sw_strategies_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(9);
/* harmony import */ var _serviceworker_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(3);
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
// stale while revalidate




async function cacheNetwork(event) {
	"use strict;";

	const response = await caches.match(event.request, {
		cacheName: _serviceworker_js__WEBPACK_IMPORTED_MODULE_1__["cacheName"]
	});

	const fetchPromise = fetch(event.request).then(networkResponse => {
		// validate response before
		if (_sw_strategies_js__WEBPACK_IMPORTED_MODULE_0__["strategies"].isCacheableRequest(event.request, networkResponse)) {
			const cloned = networkResponse.clone();
			caches
				.open(_serviceworker_js__WEBPACK_IMPORTED_MODULE_1__["cacheName"])
				.then(cache => cache.put(event.request, cloned));
		}

		return networkResponse;
	});

	return response || fetchPromise;
	//	});
}


/***/ }),
/* 11 */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "cacheOnly", function() { return cacheOnly; });
/* harmony import */ var _serviceworker_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(3);
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

// If a match isn't found in the cache, the response
// will look like a connection error);



async function cacheOnly(event) {
	return caches.match(event.request, {cacheName: _serviceworker_js__WEBPACK_IMPORTED_MODULE_0__["cacheName"]});
}


/***/ }),
/* 12 */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "networkFirst", function() { return networkFirst; });
/* harmony import */ var _sw_strategies_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(9);
/* harmony import */ var _serviceworker_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(3);
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




async function networkFirst(event) {
	"use strict;";

	try {
		const response = await fetch(event.request);

		//	.then(response => {
		if (response == null) {
			throw new Error("Network error");
		}

		if (_sw_strategies_js__WEBPACK_IMPORTED_MODULE_0__["strategies"].isCacheableRequest(event.request, response)) {
			const cloned = response.clone();
			caches
				.open(_serviceworker_js__WEBPACK_IMPORTED_MODULE_1__["cacheName"])
				.then(cache => cache.put(event.request, cloned));
		}

		return response;
		//	})
	} catch (e) {}

	return caches.match(event.request, {cacheName: _serviceworker_js__WEBPACK_IMPORTED_MODULE_1__["cacheName"]});
}


/***/ }),
/* 13 */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "networkOnly", function() { return networkOnly; });
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


/***/ }),
/* 14 */
/***/ (function(module, exports, __webpack_require__) {

module.exports = __webpack_require__(0);


/***/ }),
/* 15 */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _serviceworker_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(3);
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


self.addEventListener("activate", event => {
	// delete old app owned caches
	event.waitUntil(
		(async () => {
			try {
				await _serviceworker_js__WEBPACK_IMPORTED_MODULE_0__["SW"].resolve("activate", event);
			} catch (e) {
				console.error("😭", e);
			}
			return self.clients.claim();
		})()
	);
});


/***/ }),
/* 16 */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _serviceworker_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(3);
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
 * @param {FetchEvent} event
 */

self.addEventListener("fetch", (event) => {
	const router = _serviceworker_js__WEBPACK_IMPORTED_MODULE_0__["SW"].routes.getRouter(event);

	if (router != null) {
		event.respondWith(
			router.handler.handle(event).catch((error) => {
				console.error("😭", error);
				return fetch(event.request);
			})
		);
	}
	//	}
});


/***/ }),
/* 17 */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _serviceworker_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(3);
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


self.addEventListener("install", event => {
	event.waitUntil(
		(async () => {
			try {
				await _serviceworker_js__WEBPACK_IMPORTED_MODULE_0__["SW"].resolve("install", event);
			} catch (e) {
				console.error("😭", e);
			}
			return self.skipWaiting();
		})()
	);
});


/***/ })
/******/ ])));