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
/* global SW, scope, undef */

(function(SW) {
	const weakmap = new WeakMap();

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
			console.log({router, normalized: normalize(method)});
			method = normalize(method);

			console.log({router});

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

			SW.Utils.reset(self);

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
							plugin.precheck(event);
						}

						result = await self.resolve("beforeroute", event);

						for (response of result) {
							if (response instanceof Response) {
								return response;
							}
						}
					} catch (e) {
						console.error(CRY, error);
					}

					response = await handler.handle(event);
					result = await self.resolve("afterroute", event, response);

					for (res of result) {
						if (res instanceof Response) {
							for (plugin of self.options.plugins) {
								plugin.postcheck(event, res);
							}

							return res;
						}
					}
					return response;
				}
			};
		}
	}

	SW.Utils.merge(true, Router.prototype, SW.PromiseEvent);

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

	SW.Router = Router;
	SW.route = new Route();
})(SW);
