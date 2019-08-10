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
import {
	undef
} from '../serviceworker.js';

//const undef = null;
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

		return undef;
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
export class Router {
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
			Object.create(undef), {
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

					/*
					console.log({
						response: response.url,
						plugins: self.plugins
					});
					*/

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
		}
	}
	addPlugin(plugin) {

		if (!this.plugins.includes(plugin)) {

			this.plugins.push(plugin);
		}

		return this;
	}
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
export class RegExpRouter extends Router {
	/**
	 *
	 * @param {FetchEvent} event
	 */
	match(event, response) {
		const url = event.request.url;
		return (/^https?:/.test(url) && this.path.test(url)) ||
			this.options.mime.includes(event.request.headers.get('Content-Type')) ||
			(response != undef && this.options.mime.includes(response.headers.get('Content-Type')));
	}
}

/**
 * @property {URL} url
 * @class ExpressRouter
 * @extends Router
 * @inheritdoc
 */
export class ExpressRouter extends Router {
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
			(response != undef && this.options.mime.includes(response.headers.get('Content-Type')));
	}
}

/**
 *
 * @class CallbackRouter
 * @extends Router
 * @inheritdoc
 */
export class CallbackRouter extends Router {
	/**
	 *
	 * @param {FetchEvent} event
	 */
	match(event, response) {
		return this.path(event.request.url, event) ||
			this.options.mime.includes(event.request.headers.get('Content-Type')) ||
			(response != undef && this.options.mime.includes(response.headers.get('Content-Type')));
	}
}

//Router.RegExpRouter = RegExpRouter;
//Router.ExpressRouter = ExpressRouter;
//Router.CallbackRouter = CallbackRouter;