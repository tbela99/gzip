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
/* global SW, scope, undef */

(function(SW) {
	function normalize(method) {
		if (method == undef || method == "HEAD") {
			return "GET";
		}

		return method.toLowerCase();
	}

	class Router {
		constructor() {
			this.routes = Object.create(undef);
			this.handlers = [];
			this.defaultHandler = Object.create(undef);
		}

		/**
		 *
		 * @param {string} url
		 * @param {FetchEvent} event
		 */
		getHandler(url, event) {
			const method = (event != undef && event.request.method) || "GET";
			const routes = this.routes[method] || [];
			let route,
				i = routes.length;

			while (i && i--) {
				route = routes[i];

				if (route.match(url)) {
					console.log({ match: "match", url, route });
					return route.handler;
				}
			}

			return this.defaultHandler[method];
		}

		registerRoute(router, method) {
			method = normalize(method);

			if (!(method in this.routes)) {
				this.routes[method] = [];
			}

			this.routes[method].push(router);

			return this;
		}

		unregisterRoute(router, method) {
			method = normalize(method);

			const route = this.routes[method] || [];

			const index = route.indexOf(router);

			if (index != -1) {
				route.splice(index, 1);
			}

			return this;
		}

		setDefaultHandler(handler, method) {
			this.defaultHandler[normalize(method)] = handler;
		}
	}

	class DefaultRouter {
		constructor(path, handler) {
			const self = this;

			SW.Utils.reset(this);

			//	console.log(self);

			self.path = path;
			self.handler = {
				handle: async event => {
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

			self.promisify({
				beforeroute(event) {
					if (event.request.mode == "navigate") {
						console.log([
							"beforeroute",
							self,
							[].slice.call(arguments)
						]);
					}
				},
				afterroute(event, response) {
					if (event.request.mode == "navigate") {
						console.log([
							"afterroute",
							self,
							[].slice.call(arguments)
						]);
					}
				}
			});

			/**/
		}
	}

	SW.Utils.merge(true, DefaultRouter.prototype, SW.PromiseEvent);

	class RegExpRouter extends DefaultRouter {
		/**
		 *
		 * @param {string} url
		 * @param {Request} event
		 */
		match(url /*, event*/) {
			//	console.log({ url, regexpp: this.path });
			return /^https?:/.test(url) && this.path.test(url);
		}
	}

	class ExpressRouter extends DefaultRouter {
		constructor(path, handler) {
			super(path, handler);
			this.url = new URL(path, self.origin);
		}

		/**
		 *
		 * @param {string} url
		 */
		match(url /*, event*/) {
			const u = new URL(url);

			return (
				/^https?:/.test(url) &&
				u.origin == this.url.origin &&
				u.pathname.indexOf(this.url.pathname) == 0
			);
		}
	}

	const router = new Router();

	Router.RegExpRouter = RegExpRouter;
	Router.ExpressRouter = ExpressRouter;
	//	Router.DataRouter = DataRouter;

	SW.Router = Router;
	SW.router = router;
})(SW);
