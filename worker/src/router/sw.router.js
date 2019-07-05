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

import {
	Event
} from "../event/sw.event.promise.js";
import {
	Utils
} from "../utils/sw.utils.js";

const undef = null;
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

		if (method == undef || method == "HEAD") {
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
export class Route {
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
export class Router {
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

						if (response == undef && res instanceof Response) {
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