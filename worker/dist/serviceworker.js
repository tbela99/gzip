/* do not edit! */
// @ts-check
/* main service worker file */
// build 065a58a 2018-03-29 20:48:06-04:00
/* eslint wrap-iife: 0 */
/* global */
// validator https://www.pwabuilder.com/
// pwa app image generator http://appimagegenerator-pre.azurewebsites.net/
"use strict;";

"{IMPORT_SCRIPTS}";

const undef = null;

 //
const SW = Object.create(undef);

const CACHE_NAME = "{CACHE_NAME}";

const scope = "{scope}";

// const defaultStrategy = "{defaultStrategy}";
//console.log(self);
// @ts-check
/* eslint wrap-iife: 0 */
/* global SW, undef */
!function() {
    "use strict;";
    const Utils = {
        implement(target) {
            const proto = target.prototype, args = [].slice.call(arguments, 1);
            let i, source, key;
            function makefunc(fn, previous, parent) {
                return function() {
                    const self = this, hasPrevious = "previous" in self, hasParent = "parent" in self, oldPrevious = self.previous, oldParent = self.parent;
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
                        proto[key] = merge(true, Array.isArray(source) ? [] : {}, source);
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
        // extend a function to accept either a key/value or an object hash as arguments
        // ex set(name, value, [...]) or set({name: value, name2: value2}, [...])
        extendArgs(fn) {
            return function(key) {
                if (typeof key == "object") {
                    const args = [].slice.call(arguments, 1);
                    let k;
                    for (k in key) {
                        fn.apply(this, [ k, key[k] ].concat(args));
                    }
                } else {
                    fn.apply(this, arguments);
                }
                return this;
            };
        },
        getAllPropertiesName(object) {
            const properties = [];
            let current = object, props, prop, i;
            do {
                props = Object.getOwnPropertyNames(current);
                for (i = 0; i < props.length; i++) {
                    prop = props[i];
                    if (properties.indexOf(prop) === -1) {
                        properties.push(prop);
                    }
                }
            } while (current = Object.getPrototypeOf(current));
            return properties;
        }
    };
    function merge(target) {
        const args = [].slice.call(arguments, 1);
        let deep = typeof target == "boolean", i, source, prop, value;
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
                        target[prop] = merge(deep, typeof target[prop] == "object" && target[prop] != undef ? target[prop] : Array.isArray(value) ? [] : {}, 
                        //
                        value);
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
        let name, descriptor, i = properties.length;
        while (i && i--) {
            name = properties[i];
            descriptor = Object.getOwnPropertyDescriptor(object, name);
            //
                        if (object[name] == undef || typeof object[name] != "object" || descriptor == undef || (!("value" in descriptor) || !(descriptor.writable && descriptor.configurable))) {
                continue;
            }
            object[name] = merge(true, Array.isArray(object[name]) ? [] : {}, reset(object[name]));
        }
        return object;
    }
    SW.Utils = Utils;
}();

// @ts-check
/* eslint wrap-iife: 0 */
/* global SW, undef */
// promisified event api on(event, handler) => resolve(event, [args...])
// promisified event api on({event: handler, event2: handler2}) => resolve(event, [args...])
!function() {
    "use strict;";
    const Utils = SW.Utils;
    const extendArgs = Utils.extendArgs;
    const Event = {
        $events: {},
        $pseudo: {},
        // accept (event, handler)
        // Example: promisify('click:once', function () { console.log('clicked'); }) <- the event handler is fired once and removed
        // accept object with events as keys and handlers as values
        // Example promisify({'click:once': function () { console.log('clicked once'); }, 'click': function () { console.log('click'); }})
        promisify: extendArgs(function(name, fn, sticky) {
            const self = this;
            if (fn == undef) {
                return;
            }
            name = name.toLowerCase();
            let i, ev;
            const original = name;
            const event = {
                fn,
                cb: fn,
                name,
                original: name,
                parsed: [ name ]
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
                        Object.defineProperty(event, "sticky", {
                value: !!sticky
            });
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
                                if (fn == undef && !sticky || event.fn == fn && (!event.sticky || event.sticky == sticky)) {
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
            return Promise.all((self.$events[name] || []).concat().map(event => new Promise(resolve => {
                resolve(event.cb.apply(self, args));
            })));
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
    SW.PromiseEvent = Event;
    Utils.merge(true, SW, Event);
}();

// @ts-check
/* eslint wrap-iife: 0 */
/* global SW, undef */
SW.strategies = function() {
    const map = new Map();
    const strategy = {
        /**
		 *
		 * @param {String} name
		 * @param {function} handle
		 */
        add: (name, handle, scheme) => map.set(name, {
            name,
            handle: async event => {
                //	await SW.resolve("prefetch", event.request);
                const response = await handle(event);
                //	await SW.resolve("postfetch", event.request, response);
                                console.log({
                    mode: event.request.mode,
                    response
                });
                return response;
            }
        }),
        keys: () => map.keys(),
        values: () => map.values(),
        entries: () => map.entries(),
        get: name => map.get(name),
        has: name => map.has(name),
        delete: name => map.delete(name),
        /**
		 *
		 * @param {Request} request
		 * @param {Response} response
		 */
        // https://www.w3.org/TR/SRI/#h-note6
        isCacheableRequest: (request, response) => ("cors" == request.mode || new URL(request.url, self.origin).origin == self.origin) && request.method == "GET" && response != undef && (response.type == "basic" || response.type == "default") && response.ok && !response.bodyUsed
    };
    // if opaque response <- crossorigin? you should use cache.addAll instead of cache.put dude <- stop it!
    // if http response != 200 <- hmmm don't want to cache this <- stop it!
    // if auth != basic <- are you private? <- stop it!
        strategy[Symbol.iterator] = (() => map[Symbol.iterator]());
    Object.defineProperty(strategy, "size", {
        get: () => map.size
    });
    return strategy;
}();

// @ts-check
/* global SW, CACHE_NAME */
/* eslint wrap-iife: 0 */
SW.strategies.add("nf", async (event, cache) => {
    "use strict;";
    try {
        const response = await fetch(event.request);
        //	.then(response => {
                if (response == undef) {
            throw new Error("Network error");
        }
        if (SW.strategies.isCacheableRequest(event.request, response)) {
            const cloned = response.clone();
            caches.open(CACHE_NAME).then(function(cache) {
                cache.put(event.request, cloned);
            });
        }
        return response;
        //	})
        } catch (e) {}
    return cache.match(event.request);
});

// @ts-check
/* eslint wrap-iife: 0 */
/* global SW, CACHE_NAME */
SW.strategies.add("cf", async event => {
    "use strict;";
    let response = await caches.match(event.request);
    if (response != undef) {
        return response;
    }
    response = await fetch(event.request);
    if (SW.strategies.isCacheableRequest(event.request, response)) {
        const cloned = response.clone();
        caches.open(CACHE_NAME).then(function(cache) {
            cache.put(event.request, cloned);
        });
    }
    return response;
});

// @ts-check
/* global SW, CACHE_NAME */
/* eslint wrap-iife: 0 */
// stale while revalidate
SW.strategies.add("cn", async event => {
    "use strict;";
    const response = await caches.match(event.request);
    const fetchPromise = fetch(event.request).then(function(networkResponse) {
        // validate response before
        if (SW.strategies.isCacheableRequest(event.request, networkResponse)) {
            const cloned = networkResponse.clone();
            caches.open(CACHE_NAME).then(function(cache) {
                cache.put(event.request, cloned);
            });
        }
        return networkResponse;
    });
    return response || fetchPromise;
    //	});
});

// @ts-check
/* eslint wrap-iife: 0 */
/* global SW */
// or simply don't call event.respondWith, which will result in default browser behaviour
SW.strategies.add("no", event => fetch(event.request));

// @ts-check
/* global SW */
/* eslint wrap-iife: 0 */
// If a match isn't found in the cache, the response
// will look like a connection error);
SW.strategies.add("co", event => caches.match(event.request));

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
		 */        getHandler(url, event) {
            const method = event != undef && event.request.method || "GET";
            const routes = this.routes[method] || [];
            let route, i = routes.length;
            while (i && i--) {
                route = routes[i];
                if (route.match(url)) {
                    console.log({
                        match: "match",
                        url,
                        route
                    });
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
                        console.log([ "beforeroute", self, [].slice.call(arguments) ]);
                    }
                },
                afterroute(event, response) {
                    if (event.request.mode == "navigate") {
                        console.log([ "afterroute", self, [].slice.call(arguments) ]);
                    }
                }
            });
            /**/        }
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
		 */        match(url /*, event*/) {
            const u = new URL(url);
            return /^https?:/.test(url) && u.origin == this.url.origin && u.pathname.indexOf(this.url.pathname) == 0;
        }
    }
    const router = new Router();
    Router.RegExpRouter = RegExpRouter;
    Router.ExpressRouter = ExpressRouter;
    //	Router.DataRouter = DataRouter;
        SW.Router = Router;
    SW.router = router;
})(SW);

// @ts-check
/* eslint wrap-iife: 0 */
/* main service worker file */
/* global SW, scope */
"use strict;";

// do not cache administrator content -> this can be done in the plugin settings / joomla addministrator
//SW.Filter.addRule(SW.Filter.Rules.Prefetch, function(request) {
//	return request.url.indexOf(scope + "/administrator/") != -1;
//});
//const excluded = "{exclude_urls}";
const strategies = SW.strategies;

const Router = SW.Router;

const router = SW.router;

const handler = strategies.get("no");

let entry;

let defaultStrategy = "{defaultStrategy}";

if (!strategies.has(defaultStrategy)) {
    // default browser behavior
    defaultStrategy = "no";
}

//console.log({ SW });
router.setDefaultHandler(strategies.get(defaultStrategy));

// register strategies routers
for (entry of strategies) {
    router.registerRoute(new Router.ExpressRouter(scope + "/media/z/" + entry[0] + "/", entry[1]));
}

// excluded urls fallback on network only
"{exclude_urls}".forEach(path => {
    router.registerRoute(new Router.RegExpRouter(new RegExp(path), handler));
});

//let x;
//for (x of SW.strategies) {
//	console.log(x);
//}
// @ts-check
/* global CACHE_NAME */
self.addEventListener("install", event => {
    event.waitUntil(caches.open(CACHE_NAME).then(async cache => {
        await cache.addAll("{preloaded_urls}");
        return self.skipWaiting();
    }));
});

// @ts-check
/* global CACHE_NAME */
self.addEventListener("activate", event => {
    // delete old app owned caches
    event.waitUntil(self.clients.claim().then(async () => {
        const keyList = await caches.keys();
        const tokens = CACHE_NAME.split(/_/, 2);
        const search = tokens.length == 2 && tokens[0] + "_";
        // delete older instances
                return Promise.all(keyList.map(key => search !== false && key.indexOf(search) == 0 && key != CACHE_NAME && caches.delete(key)));
    }));
});

// @ts-check
/* global CACHE_NAME */
/**
 * @param {FetchEvent} event
 */
self.addEventListener("fetch", event => {
    const handler = SW.router.getHandler(event.request.url, event);
    if (handler != undef) {
        event.respondWith(handler.handle(event).catch(error => {
            console.error("😭", error);
            return fetch(event.request);
        }));
    }
    //	}
});
