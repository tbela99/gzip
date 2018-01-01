/* do not edit! */
// @ts-check
/* eslint wrap-iife: 0 */
/* main service worker file */
/* global */
"use strict;";

//importScripts("{scope}/localforage.min.js");
const SW = Object.create(null);

const CACHE_NAME = "{CACHE_NAME}";

const scope = "{scope}";

const defaultStrategy = "{defaultStrategy}";

let undef;

//
// -> importScript indexDb
self.addEventListener("install", function(event) {
    event.waitUntil(self.skipWaiting());
});

self.addEventListener("activate", function(event) {
    // delete old app owned caches
    caches.keys().then(function(keyList) {
        const tokens = CACHE_NAME.split(/_/, 2);
        const search = tokens.length == 2 && tokens[0] + "_";
        return search !== false && Promise.all(keyList.map(key => key.indexOf(search) == 0 && key != CACHE_NAME && caches.delete(key)));
    });
    event.waitUntil(self.clients.claim());
});

/**
 * @param {Request} event
 */
self.addEventListener("fetch", event => {
    if (event.request.method !== "GET") {
        return;
    }
    const strategies = SW.strategies;
    // guess stategy from url
    let strategyToUse = (new URL(event.request.url).pathname.match(new RegExp(scope + "/media/z/([a-z]{2})/")) || [])[1];
    // fallback to default configured in the plugin settings
    if (strategyToUse == undef) {
        strategyToUse = defaultStrategy;
    }
    if (!strategies.has(strategyToUse)) {
        // default browser behavior
        strategyToUse = "no";
    }
    console.info({
        strategyToUse,
        url: event.request.url
    });
    if (event.request.url.indexOf("data:") != 0) {
        event.respondWith(strategies.get(strategyToUse).handle(event).catch(error => {
            console.error("😭", error);
            return fetch(event.request);
        }));
    }
});

// @ts-check
/* eslint wrap-iife: 0 */
/* global SW, undef */
!function() {
    "use strict;";
    const Utils = {
        implement: function(target) {
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
        // extend a function to accept either a key/value or an object hash as arguments
        // ex set(name, value, [...]) or set({name: value, name2: value2}, [...])
        extendArgs: function(fn) {
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
        getAllPropertiesName: function(object) {
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
        const deep = target === true, args = [].slice.call(arguments, 1);
        let i, source, prop, value;
        if (deep === true) {
            target = args.shift();
        }
        for (i = 0; i < args.length; i++) {
            source = args[i];
            for (prop in source) {
                value = source[prop];
                switch (typeof value) {
                  case "object":
                    target[prop] = deep == true ? merge(deep, value == undef ? value : Array.isArray(value) ? [] : {}, value) : value;
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
        resolve: function(name) {
            name = name.toLowerCase();
            const self = this;
            const args = arguments.length > 1 ? [].slice.call(arguments, 1) : [];
            return Promise.all((self.$events[name] || []).concat().map(function(event) {
                return new Promise(function(resolve) {
                    resolve(event.cb.apply(self, args));
                });
            }));
        },
        addPseudo: function(name, fn) {
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
        add: (name, handle) => map.set(name, {
            handle: async event => {
                await SW.resolve("prefetch", event.request);
                const response = await handle(event);
                await SW.resolve("postfetch", event.request, response);
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
		 */
        isCacheableRequest: request => [ "same-origin", "cors" ].includes(request.mode),
        /**
		 *
		 * @param {Response} response
		 */
        //	isCacheableResponse: (response) => response != null && response.type == 'basic' && response.ok && !response.bodyUsed
        isCacheableResponse: response => //	console.log({response, type: response && response.type, ok: response && response.ok, bodyUsed: response && response.bodyUsed});
        //	console.log(new Error().stack);
        response != undef && response.type == "basic" && response.ok && !response.bodyUsed
    };
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
        if (SW.strategies.isCacheableResponse(response)) {
            const cloned = response.clone();
            caches.open(CACHE_NAME).then(function(cache) {
                cache.put(event.request, cloned);
            });
        }
        return response;
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
    if (SW.strategies.isCacheableResponse(response)) {
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
        if (SW.strategies.isCacheableResponse(networkResponse)) {
            const cloned = networkResponse.clone();
            caches.open(CACHE_NAME).then(function(cache) {
                cache.put(event.request, cloned);
            });
        }
        return networkResponse;
    });
    return response || fetchPromise;
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
/* eslint wrap-iife: 0 */
/* global SW, scope, undef */
SW.Filter = function(SW) {
    const prefetchRule = "prefetch.rule";
    const postfetchRule = "postfetch.rule";
    const map = new Map();
    const Filter = {
        Rules: {
            Prefetch: prefetchRule,
            Postfetch: postfetchRule
        },
        validators: map,
        addRule: (type, regExp) => {
            if (type != prefetchRule && type != postfetchRule) {
                throw new Error("Invalid rule type");
            }
            let validators = map.get(type);
            if (validators == undef) {
                validators = [];
                map.set(type, validators);
            }
            validators.push(regExp);
        }
    };
    SW.promisify({
        prefetch: function(request) {
            console.info("prefetch");
            const url = request.url;
            const excludeSet = map.get(prefetchRule);
            if (excludeSet != undef) {
                let i = 0;
                for (;i < excludeSet.length; i++) {
                    if (excludeSet[i](request)) {
                        throw new Error("Url not allowed " + url);
                    }
                }
            }
            console.info({
                request
            });
            return request;
        },
        postfetch: function(request, response) {
            console.info("postfetch");
            //	const url = request.url;
            //	const excludeSet = map.get(postfetchRule);
            //	if (excludeSet != undef) {
            //		let i = 0;
            //		for (; i < excludeSet.length; i++) {
            //			if (excludeSet[i](request)) {
            //				throw new Error("Url not allowed " + url);
            //			}
            //		}
            //	}
            console.info({
                request,
                response
            });
            return response;
        }
    });
    return Filter;
}(SW);

// @ts-check
/* eslint wrap-iife: 0 */
/* main service worker file */
/* global SW, scope */
"use strict;";

// do not cache administrator content
SW.Filter.addRule(SW.Filter.Rules.Prefetch, function(request) {
    return request.url.indexOf(scope + "/administrator/") != -1;
});