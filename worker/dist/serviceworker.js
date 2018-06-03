/* do not edit! */
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
// build 071aa55 2018-06-03 01:29:34-04:00
/* eslint wrap-iife: 0 */
/* global */
// validator https://www.pwabuilder.com/
// pwa app image generator http://appimagegenerator-pre.azurewebsites.net/
"use strict;";

"{IMPORT_SCRIPTS}";

const undef = null;

 //
/**
 *
 * @var {SWType}
 */ const SW = Object.create(undef);

const CACHE_NAME = "{CACHE_NAME}";

const CRY = "😭";

const scope = "{scope}";

Object.defineProperty(SW, "app", {
    value: Object.create(undef)
});

Object.defineProperties(SW.app, {
    name: {
        value: "gzip",
        enumerable: true
    },
    route: {
        value: "{ROUTE}",
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
        value: "071aa55",
        enumerable: true
    },
    builddate: {
        value: "2018-06-03 01:29:34-04:00",
        enumerable: true
    },
    urls: {
        value: "{CDN_HOSTS}",
        enumerable: true
    },
    homepage: {
        value: "https://github.com/tbela99/gzip",
        enumerable: true
    }
});

// const defaultStrategy = "{defaultStrategy}";
//console.log(self);
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
// force uglifyjs to include this file content
if (false) {}

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
        },
        getOwnPropertyDescriptorNames(object) {
            let properties = Object.keys(Object.getOwnPropertyDescriptors(object));
            let current = Object.getPrototypeOf(object);
            while (current) {
                properties = properties.concat(Object.keys(Object.getOwnPropertyDescriptors(current)));
                current = Object.getPrototypeOf(current);
            }
            return properties;
        },
        getObjectHash(object) {
            return hashCode(getObjectHashString(object)).toString(16);
        }
    };
    function getObjectHashString(object) {
        let toString = "", property, value, key, i = 0, j;
        if (!object && typeof object == "object" || typeof object == "string") {
            toString = "" + (object == "string" ? JSON.stringify(object) : object);
        } else {
            const properties = Utils.getOwnPropertyDescriptorNames(object);
            for (;i < properties.length; i++) {
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
                            /* eslint no-loop-func: 0 */                            value.forEach((value, key) => toString += key + ":" + getObjectHashString(value) + ",");
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
        let hash = 0, char, i;
        if (string.length == 0) {
            return hash;
        }
        for (i = 0; i < string.length; i++) {
            char = string.charCodeAt(i);
            hash = (hash << 5) - hash + char;
            hash = hash & hash;
 // Convert to 32bit integer
                }
        return hash;
    }
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
        on: extendArgs(function(name, fn, sticky) {
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
/*global SW, undef */
/**
 *
 */
SW.strategies = function() {
    const map = new Map();
    const strategy = {
        /**
		 *
		 * @param {string} key
		 * @param {routerHandle} handle
		 */
        add: (key, handle, name) => map.set(key, {
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
                    isCacheableRequest: strategy.isCacheableRequest(event.request, response),
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
        isCacheableRequest: (request, response) => response instanceof Response && ("cors" == response.type || new URL(request.url, self.origin).origin == self.origin) && request.method == "GET" && response.ok && [ "default", "cors", "basic", "navigate" ].includes(response.type) && !response.bodyUsed
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
/* global SW, CACHE_NAME */
/* eslint wrap-iife: 0 */
SW.strategies.add("nf", async event => {
    "use strict;";
    try {
        const response = await fetch(event.request);
        //	.then(response => {
                if (response == undef) {
            throw new Error("Network error");
        }
        if (SW.strategies.isCacheableRequest(event.request, response)) {
            const cloned = response.clone();
            caches.open(CACHE_NAME).then(cache => cache.put(event.request, cloned));
        }
        return response;
        //	})
        } catch (e) {}
    return caches.match(event.request, {
        cacheName: CACHE_NAME
    });
}, "Network fallback to Cache");

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
/* global SW, CACHE_NAME */
SW.strategies.add("cf", async event => {
    "use strict;";
    let response = await caches.match(event.request, {
        cacheName: CACHE_NAME
    });
    if (response != undef) {
        return response;
    }
    response = await fetch(event.request);
    if (SW.strategies.isCacheableRequest(event.request, response)) {
        const cloned = response.clone();
        caches.open(CACHE_NAME).then(cache => cache.put(event.request, cloned));
    }
    return response;
}, "Cache fallback to Network");

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
/* global SW, CACHE_NAME */
/* eslint wrap-iife: 0 */
// stale while revalidate
SW.strategies.add("cn", async event => {
    "use strict;";
    const response = await caches.match(event.request, {
        cacheName: CACHE_NAME
    });
    const fetchPromise = fetch(event.request).then(networkResponse => {
        // validate response before
        if (SW.strategies.isCacheableRequest(event.request, networkResponse)) {
            const cloned = networkResponse.clone();
            caches.open(CACHE_NAME).then(cache => cache.put(event.request, cloned));
        }
        return networkResponse;
    });
    return response || fetchPromise;
    //	});
}, "Cache and Network Update");

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
/* global SW */
// or simply don't call event.respondWith, which will result in default browser behaviour
SW.strategies.add("no", event => fetch(event.request), "Network Only");

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
/* global SW */
/* eslint wrap-iife: 0 */
// If a match isn't found in the cache, the response
// will look like a connection error);
SW.strategies.add("co", event => caches.match(event.request, {
    cacheName: CACHE_NAME
}, "Cache Only"));

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
/* global SW, CRY, scope, undef */
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
	 */    class Route {
        constructor() {
            this.routers = Object.create(undef);
            this.defaultRouter = Object.create(undef);
        }
        /**
		 * get the handler that matches the request event
		 *
		 * @param {FetchEvent} event
		 * @return {RouteHandler}
		 */        getRouter(event) {
            const method = event != undef && event.request.method || "GET";
            const routes = this.routers[method] || [];
            const j = routes.length;
            let route, i = 0;
            for (;i < j; i++) {
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
		 */        registerRoute(router, method = "GET") {
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
		 */        unregisterRoute(router, method) {
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
		 */        setDefaultRouter(router, method) {
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
	 */    class Router {
        /**
		 *
		 * @param {routerPath} path
		 * @param {routerHandle} handler
		 * @param {RouterOptions} options
		 */
        constructor(path, handler, options) {
            const self = this;
            SW.Utils.reset(self);
            self.options = Object.assign(Object.create({
                plugins: []
            }), options || {});
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
                    } catch (e) {
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
    SW.Utils.merge(true, Router.prototype, SW.PromiseEvent);
    /**
	 *
	 *
	 * @class RegExpRouter
	 * @extends Router
	 * @inheritdoc
	 */    class RegExpRouter extends Router {
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
	 */    class ExpressRouter extends Router {
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
		 */        match(event) {
            const url = event.request.url;
            const u = new URL(url);
            return /^https?:/.test(url) && u.origin == this.url.origin && u.pathname.indexOf(this.url.pathname) == 0;
        }
    }
    /**
	 *
	 * @class CallbackRouter
	 * @extends Router
	 * @inheritdoc
	 */    class CallbackRouter extends Router {
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
const DB = async (dbName, key = "id", indexes = []) => {
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
        const _query = (method, readOnly, param = null, index = null) => new Promise((resolveQuery, rejectQuery) => {
            const permission = readOnly ? "readonly" : "readwrite";
            if (db.objectStoreNames.contains(storeName)) {
                const transaction = db.transaction(storeName, permission);
                const store = transaction.objectStore(storeName);
                const isMultiplePut = method === "put" && param && typeof param.length !== "undefined";
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
                listener.oncomplete = (event => {
                    resolveQuery(event.target.result);
                });
                listener.onsuccess = (event => {
                    resolveQuery(event.target.result);
                });
                listener.onerror = (event => {
                    rejectQuery(event);
                });
            } else {
                rejectQuery(new Error("Store not found"));
            }
        });
        const methods = {
            count: () => _query("count", true, keyToUse),
            get: (keyToUse, index) => _query("get", true, keyToUse, index),
            getAll: (keyToUse, index) => _query("getAll", true, keyToUse, index),
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
};

/* global SW, CACHE_NAME */
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
/** global undef, CRY, CACHE_NAME */
// @ts-check
SW.expiration = function() {
    const expiration = Object.create(undef);
    /**
	 * @property {DBType} db
	 * @class CacheExpiration
	 */    class CacheExpiration {
        constructor(options) {
            this.setOptions(options);
        }
        getRouteTag(url) {
            const route = SW.app.route;
            let h, host;
            for (host of SW.app.urls) {
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
            this.maxAge = +options.maxAge * 1e3 || 0;
            this.db = await DB(options.cacheName != undef ? options.cacheName : "gzip_sw_worker_expiration_cache_private", "url", [ {
                name: "url",
                key: "url"
            }, {
                name: "version",
                key: "version"
            }, {
                name: "route",
                key: "route"
            } ]);
        }
        async precheck(event) {
            try {
                if (this.db == undef) {
                    return true;
                }
                const version = SW.Utils.getObjectHash(event.request);
                const entry = await this.db.get(event.request.url, "url");
                const cache = await caches.open(CACHE_NAME);
                if (entry != undef && (entry.version != version || entry.timestamp < Date.now())) {
                    console.info("CacheExpiration [precheck][obsolete][" + version + "] " + event.request.url);
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
                const version = SW.Utils.getObjectHash(event.request);
                if (entry == undef || entry.version != version || entry.timestamp < Date.now()) {
                    console.info("CacheExpiration [postcheck][update][version=" + version + "][expires=" + (Date.now() + this.maxAge) + "|" + new Date(Date.now() + this.maxAge).toUTCString() + "] " + url, this);
                    // need to update
                                        return await this.db.put({
                        url,
                        method: event.request.method,
                        timestamp: Date.now() + this.maxAge,
                        route: this.getRouteTag(url),
                        version
                    });
                } else {
                    console.info("CacheExpiration [postcheck][no update][version=" + version + "][expires=" + entry.timestamp + "|" + new Date(entry.timestamp).toUTCString() + "] " + url, entry);
                }
                return url;
            } catch (e) {
                console.error(CRY, e);
            }
            return true;
        }
    }
    expiration.CacheExpiration = CacheExpiration;
    return expiration;
}();

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
/* global SW, scope, undef */
/** @var {string} scope */
/** @var {SWType} SW */
"use strict;";

// do not cache administrator content -> this can be done in the plugin settings / joomla addministrator
//SW.Filter.addRule(SW.Filter.Rules.Prefetch, function(request) {
//	return request.url.indexOf(scope + "/administrator/") != -1;
//});
const strategies = SW.strategies;

const Router = SW.Router;

const route = SW.route;

const cacheExpiryStrategy = "{cacheExpiryStrategy}";

let entry;

let option;

let defaultStrategy = "{defaultStrategy}";

// excluded urls fallback on network only
for (entry of "{exclude_urls}") {
    route.registerRoute(new Router.RegExpRouter(new RegExp(entry), strategies.get("no")));
}

// excluded urls fallback on network only
for (entry of "{network_strategies}") {
    option = entry[2] || cacheExpiryStrategy;
    //	console.log({option});
        route.registerRoute(new Router.RegExpRouter(new RegExp(entry[1], "i"), strategies.get(entry[0]), option == undef ? option : {
        plugins: [ new SW.expiration.CacheExpiration(option) ]
    }));
}

// register strategies routers
for (entry of strategies) {
    route.registerRoute(new Router.ExpressRouter(scope + "/{ROUTE}/media/z/" + entry[0] + "/", entry[1]));
}

if (!strategies.has(defaultStrategy)) {
    // default browser behavior
    defaultStrategy = "no";
}

route.setDefaultRouter(new Router.ExpressRouter("/", strategies.get(defaultStrategy)));

// service worker activation
SW.on({
    async install() {
        console.info("🛠️ service worker install event");
        await caches.open(CACHE_NAME).then(async cache => {
            await cache.addAll("{preloaded_urls}");
        });
    },
    async activate() {
        console.info("🚁 service worker activate event");
        const db = await DB("gzip_sw_worker_config_cache_private", "name");
        //	console.log("{STORES}");
                const settings = await db.get("gzip");
        if (settings != undef) {
            if (settings.route != "{ROUTE}") {
                // the url cache prefix has changed! delete private cache expiration data
                let storeName, store;
                for (storeName of "{STORES}") {
                    console.info({
                        storeName
                    });
                    store = await DB(storeName, "url", [ {
                        name: "url",
                        key: "url"
                    }, {
                        name: "version",
                        key: "version"
                    }, {
                        name: "route",
                        key: "route"
                    } ]);
                    if (store != undef) {
                        store.clear();
                    }
                }
            }
        }
        await db.put(SW.app);
        // delete obsolet caches
                const keyList = await caches.keys();
        const tokens = CACHE_NAME.split(/_/, 2);
        /**
		 * @var {boolean|string}
		 */        const search = tokens.length == 2 && tokens[0] + "_";
        // delete older app caches
                if (search != false) {
            await Promise.all(keyList.map(key => key.indexOf(search) == 0 && key != CACHE_NAME && caches.delete(key)));
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
// @ts-check
/* global CACHE_NAME */
self.addEventListener("install", event => {
    event.waitUntil((async () => {
        try {
            await SW.resolve("install", event);
        } catch (e) {
            console.error(CRY, e);
        }
        return self.skipWaiting();
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
// @ts-check
/* global CACHE_NAME, SW */
self.addEventListener("activate", event => {
    // delete old app owned caches
    event.waitUntil((async () => {
        try {
            await SW.resolve("activate", event);
        } catch (e) {
            console.error(CRY, e);
        }
        return self.clients.claim();
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
// @ts-check
/* global CACHE_NAME, CRY */
/**
 * @param {FetchEvent} event
 */
self.addEventListener("fetch", event => {
    const router = SW.route.getRouter(event);
    if (router != undef) {
        event.respondWith(router.handler.handle(event).catch(error => {
            console.error(CRY, error);
            return fetch(event.request);
        }));
    }
    //	}
});
