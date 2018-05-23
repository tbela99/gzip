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
	function normalize(method) {
		if (method == undef || method == "HEAD") {
			return "GET";
		}

		return method.toLowerCase();
	}

	/**
	 * request router class
	 *
	 * @property {Object.<string, DefaultRouter[]>} routes
	 * @property {Object.<string, routerHandle>} defaultHandler
	 * @method {function} on
	 * @method {function} off
	 * @method {function} trigger
	 *
	 * @class Router
	 */
	class Router {
		constructor() {
			this.routes = Object.create(undef);
			this.defaultHandler = Object.create(undef);
		}

		/**
		 * get the handler that matches the request event
		 *
		 * @param {FetchEvent} event
		 */
		getHandler(event) {
			const method = (event != undef && event.request.method) || "GET";
			const routes = this.routes[method] || [];
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
					return route.handler;
				}
			}

			return this.defaultHandler[method];
		}

		/**
		 * regeister a handler for an http method
		 *
		 * @param {BaseRouter} router router instance
		 * @param {string} method http method
		 */
		registerRoute(router, method) {
			method = normalize(method);

			if (!(method in this.routes)) {
				this.routes[method] = [];
			}

			this.routes[method].push(router);

			return this;
		}

		/**
		 * unregister a handler for an http method
		 *
		 * @param {BaseRouter} router router instance
		 * @param {string} method http metho
		 */
		unregisterRoute(router, method) {
			method = normalize(method);

			const route = this.routes[method] || [];

			const index = route.indexOf(router);

			if (index != -1) {
				route.splice(index, 1);
			}

			return this;
		}

		/**
		 * set the default request handler
		 *
		 * @param {routerHandle} handler router instance
		 * @param {string} method http metho
		 */
		setDefaultHandler(handler, method) {
			this.defaultHandler[normalize(method)] = handler;
		}
	}

	/**
	 * @property {string} strategy router strategy name
	 * @property {routerPath} path path used to match requests
	 * @property {routerHandleObject} handler
	 * @property {object} options
	 * @method on
	 * @method off
	 * @method trigger
	 *
	 * @class BaseRouter
	 */
	class BaseRouter {
		/**
		 *
		 * @param {routerPath} path
		 * @param {routerHandle} handler
		 * @param {object} options
		 */
		constructor(path, handler, options) {
			const self = this;
			let prop, event, cb;

			self.options = Object.assign(
				Object.create(undef),
				{},
				options || {}
			);

			for (prop in self.options) {
				if (/^on.+/i.test(prop)) {
					event = prop.substr(2);

					if (Array.isArray(self.options[prop])) {
						for (cb of self.options[prop]) {
							self.on(event, cb);
						}
					} else {
						self.on(event, self.options[prop]);
					}
				}
			}

			SW.Utils.reset(this);

			self.path = path;
			self.strategy = handler.name;
			self.handler = {
				handle: async event => {
					// before route
					let result = await self.resolve("beforeroute", event);
					let response, res;

					for (response of result) {
						if (response != undef && response instanceof Response) {
							return response;
						}
					}

					response = await handler.handle(event);
					result = await self.resolve("afterroute", event, response);

					for (res of result) {
						if (res != undef && res instanceof Response) {
							return res;
						}
					}

					return response;
				}
			};

			/**/
		}
	}

	SW.Utils.merge(true, BaseRouter.prototype, SW.PromiseEvent);

	/**
	 *
	 *
	 * @class RegExpRouter
	 * @extends BaseRouter
	 * @inheritdoc
	 */
	class RegExpRouter extends BaseRouter {
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
	 * @extends BaseRouter
	 * @inheritdoc
	 */
	class ExpressRouter extends BaseRouter {
		/**
		 * @inheritdoc
		 */
		/**
		 * Creates an instance of ExpressRouter.
		 * @param  {RegExp} path
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
	 * @extends BaseRouter
	 * @inheritdoc
	 */
	class CallbackRouter extends BaseRouter {
		/**
		 *
		 * @param {FetchEvent} event
		 */
		match(event) {
			return this.path(event.request.url, event);
		}
	}

	const router = new Router();

	Router.RegExpRouter = RegExpRouter;
	Router.ExpressRouter = ExpressRouter;
	Router.CallbackRouter = CallbackRouter;

	SW.Router = Router;
	SW.router = router;
})(SW);
