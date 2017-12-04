var LIB = Object.create(null);

// @ts-check
/* global LIB, document */
/* eslint wrap-iife: 0 */
LIB.ready = function() {
    "use strict;";
    const queue = [];
    let fired = document.readyState != "loading";
    function readystatechange() {
        switch (document.readyState) {
          case "loading":
            break;

          case "interactive":
          default:
            fired = true;
            while (queue.length > 0) {
                requestAnimationFrame(queue.shift());
            }
            document.removeEventListener("readystatechange", readystatechange);
            break;
        }
    }
    document.addEventListener("readystatechange", readystatechange);
    return function(cb) {
        if (fired) {
            while (queue.length > 0) {
                requestAnimationFrame(queue.shift());
            }
            cb();
        } else {
            queue.push(cb);
        }
    };
}();

// @ts-check
/* eslint wrap-iife: 0 */
/* global LIB */
!function(LIB, undef) {
    "use strict;";
    const Utils = {
        pause: function(fn, delay) {
            let timeout, undef;
            return function() {
                const context = this, args = arguments;
                if (timeout) {
                    clearTimeout(timeout);
                    timeout = undef;
                }
                timeout = setTimeout(function() {
                    timeout = undef;
                    fn.apply(context, args);
                }, delay || 250);
            };
        },
        throttle: function(fn, delay) {
            let time, undef;
            if (delay == undef) {
                delay = 250;
            }
            return function() {
                const now = Date.now();
                if (time == undef || time + delay >= now) {
                    time = now;
                    fn.apply(this, arguments);
                }
            };
        },
        implement: function(target) {
            const proto = target.prototype, args = Array.prototype.slice.call(arguments, 1);
            let i, source, key;
            function makefunc(fn, previous, parent) {
                return function() {
                    const self = this, hasPrevious = "previous" in self, hasParent = "parent" in self, oldPrevious = self.previous, oldParent = self.parent;
                    self.previous = previous;
                    self.parent = parent;
                    const result = fn.apply(self, arguments);
                    if (hasPrevious) {
                        self.previous = oldPrevious;
                    } else {
                        delete self.previous;
                    }
                    if (hasParent) {
                        self.parent = oldParent;
                    } else {
                        delete self.parent;
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
                        proto[key] = merge(true, source instanceof Array ? [] : {}, source);
                        break;

                      default:
                        proto[key] = source;
                        break;
                    }
                }
            }
            return target;
        },
        merge: merge,
        reset: reset,
        // extend a function to accept either a key/value or an object hash as arguments
        // ex set(name, value, [...]) or set({name: value, name2: value2}, [...])
        extendArgs: function(fn) {
            return function(key) {
                if (typeof key == "object") {
                    const args = Array.prototype.slice.apply(arguments, 1);
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
        const deep = target === true, args = Array.prototype.slice.call(arguments, 1);
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
                    target[prop] = deep == true ? merge(deep, value instanceof Array ? [] : {}, value) : value;
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
            object[name] = merge(true, object[name] instanceof Array ? [] : {}, reset(object[name]));
        }
        return object;
    }
    LIB.Utils = Utils;
}(LIB);

// @ts-check
/* eslint wrap-iife: 0 */
/* global LIB */
!function(LIB, undef) {
    "use strict;";
    const Utils = LIB.Utils;
    const Event = {
        $events: {},
        $pseudo: {},
        on: Utils.extendArgs(function(name, fn, sticky) {
            // 'click:delay(500)'.match(/([^:]+):([^(]+)(\(([^)]+)\))?/)
            // Array [ "click:delay(400)", "click", "delay", "(400)", "400" ]
            const self = this;
            if (fn == undef) {
                return;
            }
            name = name.toLowerCase();
            let i, ev;
            const event = {
                fn: fn,
                cb: fn,
                name: name,
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
                if (ev.fn == fn && ev.original == name) {
                    return;
                }
            }
            //    sticky = !!sticky;
            Object.defineProperty(event, "sticky", {
                value: !!sticky
            });
            self.$events[name].push(event);
        }),
        off: Utils.extendArgs(function(name, fn, sticky) {
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
        trigger: function(name) {
            name = name.toLowerCase();
            const self = this;
            if (!(name in self.$events)) {
                return self;
            }
            let i;
            const args = arguments.length > 1 ? Array.prototype.slice.call(arguments, 1) : [];
            const events = self.$events[name].concat();
            for (i = 0; i < events.length; i++) {
                events[i].cb.apply(self, args);
            }
            return self;
        },
        addPseudo: function(name, fn) {
            this.$pseudo[name] = fn;
            return this;
        }
    };
    Event.addPseudo("once", function(event) {
        const context = this;
        event.cb = function() {
            event.fn.apply(context, arguments);
            context.off(event.name, event.fn);
        };
    }).addPseudo("pause", function(event) {
        event.cb = Utils.pause(event.fn, event.parsed[4] == undef && 250 || +event.parsed[4]);
    }).addPseudo("throttle", function(event) {
        event.cb = Utils.throttle(event.fn, event.parsed[4] == undef && 250 || +event.parsed[4]);
    });
    LIB.Event = Event;
}(LIB);

// @ts-check
/* eslint wrap-iife: 0 */
/* global LIB */
!function(LIB, undef) {
    "use strict;";
    const Utils = LIB.Utils;
    LIB.Options = {
        options: {},
        setOptions: function(options) {
            let key, option, match;
            const self = this, hasEvent = typeof self.on == "function";
            if (hasEvent) {
                for (key in options) {
                    option = options[key];
                    if (typeof option == "function") {
                        match = key.match(/^on(.*)$/);
                        if (match != undef) {
                            self.on(match[1], option);
                            delete options[key];
                            continue;
                        }
                    }
                    self.options[key] = typeof option == "object" && option != undef ? Utils.merge(true, option instanceof Array ? [] : {}, option) : option;
                }
            } else {
                Utils.merge(true, self.options, options);
            }
        }
    };
}(LIB);

// @ts-check
/* eslint wrap-iife: 0 */
/* global LIB */
!function(LIB, undef) {
    "use strict;";
    const SW = {};
    const Utils = LIB.Utils;
    //const merge = Utils.merge;
    const supported = "serviceWorker" in navigator;
    const serviceworker = navigator.serviceWorker;
    LIB.SW = SW;
    Object.defineProperties(SW, {
        supported: {
            get: function() {
                return supported;
            },
            enumerable: true
        },
        serviceworker: {
            get: function() {
                return serviceworker;
            },
            enumerable: true
        },
        ServiceWorker: {
            get: function() {
                return ServiceWorker;
            },
            enumerable: false
        }
    });
    if (!supported) {
        console.log("service worker not supported.");
        return;
    }
    /**
     * storeInfo: {name: 'db', storeName: 'store1' }
     */
    SW.getInstance = function(options) {
        return new ServiceWorker(options);
    };
    function ServiceWorker(options) {
        "use strict;";
        const self = this;
        let worker, state;
        Utils.reset(self);
        self.setOptions(options);
        //
        Object.defineProperties(self, {
            state: {
                get: function() {
                    return state;
                },
                enumerable: true
            },
            worker: {
                get: function() {
                    return worker;
                },
                enumerable: true
            }
        });
        serviceworker.register(options.worker).then(function(registration) {
            if (registration.installing) {
                worker = registration.installing;
                state = "installing";
            } else if (registration.waiting) {
                worker = registration.waiting;
                state = "waiting";
            } else if (registration.active) {
                worker = registration.active;
                state = "active";
            }
            if (worker != undef) {
                worker.addEventListener("statechange", function(e) {
                    self.trigger("statechange", e);
                });
                self.trigger("ready");
            }
        }).catch(function(error) {
            // L'enregistrement s'est mal déroulé. Le fichier service-worker.js
            // est peut-être indisponible ou contient une erreur.
            state = "error";
            self.trigger("error", error);
        });
    }
    ServiceWorker.prototype.constructor = ServiceWorker;
    Utils.implement(ServiceWorker, {
        add: function(files) {
            const values = Object.values(files);
            if (values.length > 0) {
                this.trigger("add", files).postMessage({
                    action: "addFiles",
                    files: values
                });
            }
            return this;
        },
        remove: function(files) {
            const values = Object.values(files);
            if (values.length > 0) {
                this.trigger("remove", files).postMessage({
                    action: "removeFiles",
                    files: values
                });
            }
            return this;
        },
        postMessage: function(data) {
            const self = this, messageChannel = new MessageChannel();
            messageChannel.port1.onmessage = function(event) {
                self.trigger("message", event);
            };
            self.worker.postMessage(data, [ messageChannel.port2 ]);
            return self;
        },
        clearCache: function() {
            const self = this;
            self.postMessage({
                action: "empty"
            });
            return self;
        },
        removeWorker: function() {
            const self = this;
            if (self.worker != undef) {
                self.worker.unregister().then(function() {
                    self.trigger("deleted");
                });
            }
            return self;
        },
        removeAllWorkers: function() {
            navigator.serviceWorker.getRegistrations().then(function(registrations) {
                let registration;
                for (registration of registrations) {
                    registration.unregister();
                }
            });
        }
    }, LIB.Event, LIB.Options);
}(LIB);

/*!
    localForage -- Offline Storage, Improved
    Version 1.5.0
    https://localforage.github.io/localForage
    (c) 2013-2017 Mozilla, Apache License 2.0
*/
(function(f) {
    if (typeof exports === "object" && typeof module !== "undefined") {
        module.exports = f();
    } else if (typeof define === "function" && define.amd) {
        define([], f);
    } else {
        var g;
        if (typeof window !== "undefined") {
            g = window;
        } else if (typeof global !== "undefined") {
            g = global;
        } else if (typeof self !== "undefined") {
            g = self;
        } else {
            g = this;
        }
        g.localforage = f();
    }
})(function() {
    var define, module, exports;
    return function e(t, n, r) {
        function s(o, u) {
            if (!n[o]) {
                if (!t[o]) {
                    var a = typeof require == "function" && require;
                    if (!u && a) return a(o, !0);
                    if (i) return i(o, !0);
                    var f = new Error("Cannot find module '" + o + "'");
                    throw f.code = "MODULE_NOT_FOUND", f;
                }
                var l = n[o] = {
                    exports: {}
                };
                t[o][0].call(l.exports, function(e) {
                    var n = t[o][1][e];
                    return s(n ? n : e);
                }, l, l.exports, e, t, n, r);
            }
            return n[o].exports;
        }
        var i = typeof require == "function" && require;
        for (var o = 0; o < r.length; o++) s(r[o]);
        return s;
    }({
        1: [ function(_dereq_, module, exports) {
            (function(global) {
                "use strict";
                var Mutation = global.MutationObserver || global.WebKitMutationObserver;
                var scheduleDrain;
                {
                    if (Mutation) {
                        var called = 0;
                        var observer = new Mutation(nextTick);
                        var element = global.document.createTextNode("");
                        observer.observe(element, {
                            characterData: true
                        });
                        scheduleDrain = function() {
                            element.data = called = ++called % 2;
                        };
                    } else if (!global.setImmediate && typeof global.MessageChannel !== "undefined") {
                        var channel = new global.MessageChannel();
                        channel.port1.onmessage = nextTick;
                        scheduleDrain = function() {
                            channel.port2.postMessage(0);
                        };
                    } else if ("document" in global && "onreadystatechange" in global.document.createElement("script")) {
                        scheduleDrain = function() {
                            // Create a <script> element; its readystatechange event will be fired asynchronously once it is inserted
                            // into the document. Do so, thus queuing up the task. Remember to clean up once it's been called.
                            var scriptEl = global.document.createElement("script");
                            scriptEl.onreadystatechange = function() {
                                nextTick();
                                scriptEl.onreadystatechange = null;
                                scriptEl.parentNode.removeChild(scriptEl);
                                scriptEl = null;
                            };
                            global.document.documentElement.appendChild(scriptEl);
                        };
                    } else {
                        scheduleDrain = function() {
                            setTimeout(nextTick, 0);
                        };
                    }
                }
                var draining;
                var queue = [];
                //named nextTick for less confusing stack traces
                function nextTick() {
                    draining = true;
                    var i, oldQueue;
                    var len = queue.length;
                    while (len) {
                        oldQueue = queue;
                        queue = [];
                        i = -1;
                        while (++i < len) {
                            oldQueue[i]();
                        }
                        len = queue.length;
                    }
                    draining = false;
                }
                module.exports = immediate;
                function immediate(task) {
                    if (queue.push(task) === 1 && !draining) {
                        scheduleDrain();
                    }
                }
            }).call(this, typeof global !== "undefined" ? global : typeof self !== "undefined" ? self : typeof window !== "undefined" ? window : {});
        }, {} ],
        2: [ function(_dereq_, module, exports) {
            "use strict";
            var immediate = _dereq_(1);
            /* istanbul ignore next */
            function INTERNAL() {}
            var handlers = {};
            var REJECTED = [ "REJECTED" ];
            var FULFILLED = [ "FULFILLED" ];
            var PENDING = [ "PENDING" ];
            module.exports = exports = Promise;
            function Promise(resolver) {
                if (typeof resolver !== "function") {
                    throw new TypeError("resolver must be a function");
                }
                this.state = PENDING;
                this.queue = [];
                this.outcome = void 0;
                if (resolver !== INTERNAL) {
                    safelyResolveThenable(this, resolver);
                }
            }
            Promise.prototype["catch"] = function(onRejected) {
                return this.then(null, onRejected);
            };
            Promise.prototype.then = function(onFulfilled, onRejected) {
                if (typeof onFulfilled !== "function" && this.state === FULFILLED || typeof onRejected !== "function" && this.state === REJECTED) {
                    return this;
                }
                var promise = new this.constructor(INTERNAL);
                if (this.state !== PENDING) {
                    var resolver = this.state === FULFILLED ? onFulfilled : onRejected;
                    unwrap(promise, resolver, this.outcome);
                } else {
                    this.queue.push(new QueueItem(promise, onFulfilled, onRejected));
                }
                return promise;
            };
            function QueueItem(promise, onFulfilled, onRejected) {
                this.promise = promise;
                if (typeof onFulfilled === "function") {
                    this.onFulfilled = onFulfilled;
                    this.callFulfilled = this.otherCallFulfilled;
                }
                if (typeof onRejected === "function") {
                    this.onRejected = onRejected;
                    this.callRejected = this.otherCallRejected;
                }
            }
            QueueItem.prototype.callFulfilled = function(value) {
                handlers.resolve(this.promise, value);
            };
            QueueItem.prototype.otherCallFulfilled = function(value) {
                unwrap(this.promise, this.onFulfilled, value);
            };
            QueueItem.prototype.callRejected = function(value) {
                handlers.reject(this.promise, value);
            };
            QueueItem.prototype.otherCallRejected = function(value) {
                unwrap(this.promise, this.onRejected, value);
            };
            function unwrap(promise, func, value) {
                immediate(function() {
                    var returnValue;
                    try {
                        returnValue = func(value);
                    } catch (e) {
                        return handlers.reject(promise, e);
                    }
                    if (returnValue === promise) {
                        handlers.reject(promise, new TypeError("Cannot resolve promise with itself"));
                    } else {
                        handlers.resolve(promise, returnValue);
                    }
                });
            }
            handlers.resolve = function(self, value) {
                var result = tryCatch(getThen, value);
                if (result.status === "error") {
                    return handlers.reject(self, result.value);
                }
                var thenable = result.value;
                if (thenable) {
                    safelyResolveThenable(self, thenable);
                } else {
                    self.state = FULFILLED;
                    self.outcome = value;
                    var i = -1;
                    var len = self.queue.length;
                    while (++i < len) {
                        self.queue[i].callFulfilled(value);
                    }
                }
                return self;
            };
            handlers.reject = function(self, error) {
                self.state = REJECTED;
                self.outcome = error;
                var i = -1;
                var len = self.queue.length;
                while (++i < len) {
                    self.queue[i].callRejected(error);
                }
                return self;
            };
            function getThen(obj) {
                // Make sure we only access the accessor once as required by the spec
                var then = obj && obj.then;
                if (obj && typeof obj === "object" && typeof then === "function") {
                    return function appyThen() {
                        then.apply(obj, arguments);
                    };
                }
            }
            function safelyResolveThenable(self, thenable) {
                // Either fulfill, reject or reject with error
                var called = false;
                function onError(value) {
                    if (called) {
                        return;
                    }
                    called = true;
                    handlers.reject(self, value);
                }
                function onSuccess(value) {
                    if (called) {
                        return;
                    }
                    called = true;
                    handlers.resolve(self, value);
                }
                function tryToUnwrap() {
                    thenable(onSuccess, onError);
                }
                var result = tryCatch(tryToUnwrap);
                if (result.status === "error") {
                    onError(result.value);
                }
            }
            function tryCatch(func, value) {
                var out = {};
                try {
                    out.value = func(value);
                    out.status = "success";
                } catch (e) {
                    out.status = "error";
                    out.value = e;
                }
                return out;
            }
            exports.resolve = resolve;
            function resolve(value) {
                if (value instanceof this) {
                    return value;
                }
                return handlers.resolve(new this(INTERNAL), value);
            }
            exports.reject = reject;
            function reject(reason) {
                var promise = new this(INTERNAL);
                return handlers.reject(promise, reason);
            }
            exports.all = all;
            function all(iterable) {
                var self = this;
                if (Object.prototype.toString.call(iterable) !== "[object Array]") {
                    return this.reject(new TypeError("must be an array"));
                }
                var len = iterable.length;
                var called = false;
                if (!len) {
                    return this.resolve([]);
                }
                var values = new Array(len);
                var resolved = 0;
                var i = -1;
                var promise = new this(INTERNAL);
                while (++i < len) {
                    allResolver(iterable[i], i);
                }
                return promise;
                function allResolver(value, i) {
                    self.resolve(value).then(resolveFromAll, function(error) {
                        if (!called) {
                            called = true;
                            handlers.reject(promise, error);
                        }
                    });
                    function resolveFromAll(outValue) {
                        values[i] = outValue;
                        if (++resolved === len && !called) {
                            called = true;
                            handlers.resolve(promise, values);
                        }
                    }
                }
            }
            exports.race = race;
            function race(iterable) {
                var self = this;
                if (Object.prototype.toString.call(iterable) !== "[object Array]") {
                    return this.reject(new TypeError("must be an array"));
                }
                var len = iterable.length;
                var called = false;
                if (!len) {
                    return this.resolve([]);
                }
                var i = -1;
                var promise = new this(INTERNAL);
                while (++i < len) {
                    resolver(iterable[i]);
                }
                return promise;
                function resolver(value) {
                    self.resolve(value).then(function(response) {
                        if (!called) {
                            called = true;
                            handlers.resolve(promise, response);
                        }
                    }, function(error) {
                        if (!called) {
                            called = true;
                            handlers.reject(promise, error);
                        }
                    });
                }
            }
        }, {
            "1": 1
        } ],
        3: [ function(_dereq_, module, exports) {
            (function(global) {
                "use strict";
                if (typeof global.Promise !== "function") {
                    global.Promise = _dereq_(2);
                }
            }).call(this, typeof global !== "undefined" ? global : typeof self !== "undefined" ? self : typeof window !== "undefined" ? window : {});
        }, {
            "2": 2
        } ],
        4: [ function(_dereq_, module, exports) {
            "use strict";
            var _typeof = typeof Symbol === "function" && typeof Symbol.iterator === "symbol" ? function(obj) {
                return typeof obj;
            } : function(obj) {
                return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj;
            };
            function _classCallCheck(instance, Constructor) {
                if (!(instance instanceof Constructor)) {
                    throw new TypeError("Cannot call a class as a function");
                }
            }
            function getIDB() {
                /* global indexedDB,webkitIndexedDB,mozIndexedDB,OIndexedDB,msIndexedDB */
                try {
                    if (typeof indexedDB !== "undefined") {
                        return indexedDB;
                    }
                    if (typeof webkitIndexedDB !== "undefined") {
                        return webkitIndexedDB;
                    }
                    if (typeof mozIndexedDB !== "undefined") {
                        return mozIndexedDB;
                    }
                    if (typeof OIndexedDB !== "undefined") {
                        return OIndexedDB;
                    }
                    if (typeof msIndexedDB !== "undefined") {
                        return msIndexedDB;
                    }
                } catch (e) {}
            }
            var idb = getIDB();
            function isIndexedDBValid() {
                try {
                    // Initialize IndexedDB; fall back to vendor-prefixed versions
                    // if needed.
                    if (!idb) {
                        return false;
                    }
                    // We mimic PouchDB here;
                    //
                    // We test for openDatabase because IE Mobile identifies itself
                    // as Safari. Oh the lulz...
                    var isSafari = typeof openDatabase !== "undefined" && /(Safari|iPhone|iPad|iPod)/.test(navigator.userAgent) && !/Chrome/.test(navigator.userAgent) && !/BlackBerry/.test(navigator.platform);
                    var hasFetch = typeof fetch === "function" && fetch.toString().indexOf("[native code") !== -1;
                    // Safari <10.1 does not meet our requirements for IDB support (#5572)
                    // since Safari 10.1 shipped with fetch, we can use that to detect it
                    // some outdated implementations of IDB that appear on Samsung
                    // and HTC Android devices <4.4 are missing IDBKeyRange
                    return (!isSafari || hasFetch) && typeof indexedDB !== "undefined" && typeof IDBKeyRange !== "undefined";
                } catch (e) {
                    return false;
                }
            }
            function isWebSQLValid() {
                return typeof openDatabase === "function";
            }
            function isLocalStorageValid() {
                try {
                    return typeof localStorage !== "undefined" && "setItem" in localStorage && localStorage.setItem;
                } catch (e) {
                    return false;
                }
            }
            // Abstracts constructing a Blob object, so it also works in older
            // browsers that don't support the native Blob constructor. (i.e.
            // old QtWebKit versions, at least).
            // Abstracts constructing a Blob object, so it also works in older
            // browsers that don't support the native Blob constructor. (i.e.
            // old QtWebKit versions, at least).
            function createBlob(parts, properties) {
                /* global BlobBuilder,MSBlobBuilder,MozBlobBuilder,WebKitBlobBuilder */
                parts = parts || [];
                properties = properties || {};
                try {
                    return new Blob(parts, properties);
                } catch (e) {
                    if (e.name !== "TypeError") {
                        throw e;
                    }
                    var Builder = typeof BlobBuilder !== "undefined" ? BlobBuilder : typeof MSBlobBuilder !== "undefined" ? MSBlobBuilder : typeof MozBlobBuilder !== "undefined" ? MozBlobBuilder : WebKitBlobBuilder;
                    var builder = new Builder();
                    for (var i = 0; i < parts.length; i += 1) {
                        builder.append(parts[i]);
                    }
                    return builder.getBlob(properties.type);
                }
            }
            // This is CommonJS because lie is an external dependency, so Rollup
            // can just ignore it.
            if (typeof Promise === "undefined") {
                // In the "nopromises" build this will just throw if you don't have
                // a global promise object, but it would throw anyway later.
                _dereq_(3);
            }
            var Promise$1 = Promise;
            function executeCallback(promise, callback) {
                if (callback) {
                    promise.then(function(result) {
                        callback(null, result);
                    }, function(error) {
                        callback(error);
                    });
                }
            }
            function executeTwoCallbacks(promise, callback, errorCallback) {
                if (typeof callback === "function") {
                    promise.then(callback);
                }
                if (typeof errorCallback === "function") {
                    promise["catch"](errorCallback);
                }
            }
            // Some code originally from async_storage.js in
            // [Gaia](https://github.com/mozilla-b2g/gaia).
            var DETECT_BLOB_SUPPORT_STORE = "local-forage-detect-blob-support";
            var supportsBlobs;
            var dbContexts;
            var toString = Object.prototype.toString;
            // Transaction Modes
            var READ_ONLY = "readonly";
            var READ_WRITE = "readwrite";
            // Transform a binary string to an array buffer, because otherwise
            // weird stuff happens when you try to work with the binary string directly.
            // It is known.
            // From http://stackoverflow.com/questions/14967647/ (continues on next line)
            // encode-decode-image-with-base64-breaks-image (2013-04-21)
            function _binStringToArrayBuffer(bin) {
                var length = bin.length;
                var buf = new ArrayBuffer(length);
                var arr = new Uint8Array(buf);
                for (var i = 0; i < length; i++) {
                    arr[i] = bin.charCodeAt(i);
                }
                return buf;
            }
            //
            // Blobs are not supported in all versions of IndexedDB, notably
            // Chrome <37 and Android <5. In those versions, storing a blob will throw.
            //
            // Various other blob bugs exist in Chrome v37-42 (inclusive).
            // Detecting them is expensive and confusing to users, and Chrome 37-42
            // is at very low usage worldwide, so we do a hacky userAgent check instead.
            //
            // content-type bug: https://code.google.com/p/chromium/issues/detail?id=408120
            // 404 bug: https://code.google.com/p/chromium/issues/detail?id=447916
            // FileReader bug: https://code.google.com/p/chromium/issues/detail?id=447836
            //
            // Code borrowed from PouchDB. See:
            // https://github.com/pouchdb/pouchdb/blob/master/packages/node_modules/pouchdb-adapter-idb/src/blobSupport.js
            //
            function _checkBlobSupportWithoutCaching(idb) {
                return new Promise$1(function(resolve) {
                    var txn = idb.transaction(DETECT_BLOB_SUPPORT_STORE, READ_WRITE);
                    var blob = createBlob([ "" ]);
                    txn.objectStore(DETECT_BLOB_SUPPORT_STORE).put(blob, "key");
                    txn.onabort = function(e) {
                        // If the transaction aborts now its due to not being able to
                        // write to the database, likely due to the disk being full
                        e.preventDefault();
                        e.stopPropagation();
                        resolve(false);
                    };
                    txn.oncomplete = function() {
                        var matchedChrome = navigator.userAgent.match(/Chrome\/(\d+)/);
                        var matchedEdge = navigator.userAgent.match(/Edge\//);
                        // MS Edge pretends to be Chrome 42:
                        // https://msdn.microsoft.com/en-us/library/hh869301%28v=vs.85%29.aspx
                        resolve(matchedEdge || !matchedChrome || parseInt(matchedChrome[1], 10) >= 43);
                    };
                })["catch"](function() {
                    return false;
                });
            }
            function _checkBlobSupport(idb) {
                if (typeof supportsBlobs === "boolean") {
                    return Promise$1.resolve(supportsBlobs);
                }
                return _checkBlobSupportWithoutCaching(idb).then(function(value) {
                    supportsBlobs = value;
                    return supportsBlobs;
                });
            }
            function _deferReadiness(dbInfo) {
                var dbContext = dbContexts[dbInfo.name];
                // Create a deferred object representing the current database operation.
                var deferredOperation = {};
                deferredOperation.promise = new Promise$1(function(resolve) {
                    deferredOperation.resolve = resolve;
                });
                // Enqueue the deferred operation.
                dbContext.deferredOperations.push(deferredOperation);
                // Chain its promise to the database readiness.
                if (!dbContext.dbReady) {
                    dbContext.dbReady = deferredOperation.promise;
                } else {
                    dbContext.dbReady = dbContext.dbReady.then(function() {
                        return deferredOperation.promise;
                    });
                }
            }
            function _advanceReadiness(dbInfo) {
                var dbContext = dbContexts[dbInfo.name];
                // Dequeue a deferred operation.
                var deferredOperation = dbContext.deferredOperations.pop();
                // Resolve its promise (which is part of the database readiness
                // chain of promises).
                if (deferredOperation) {
                    deferredOperation.resolve();
                }
            }
            function _rejectReadiness(dbInfo, err) {
                var dbContext = dbContexts[dbInfo.name];
                // Dequeue a deferred operation.
                var deferredOperation = dbContext.deferredOperations.pop();
                // Reject its promise (which is part of the database readiness
                // chain of promises).
                if (deferredOperation) {
                    deferredOperation.reject(err);
                }
            }
            function _getConnection(dbInfo, upgradeNeeded) {
                return new Promise$1(function(resolve, reject) {
                    if (dbInfo.db) {
                        if (upgradeNeeded) {
                            _deferReadiness(dbInfo);
                            dbInfo.db.close();
                        } else {
                            return resolve(dbInfo.db);
                        }
                    }
                    var dbArgs = [ dbInfo.name ];
                    if (upgradeNeeded) {
                        dbArgs.push(dbInfo.version);
                    }
                    var openreq = idb.open.apply(idb, dbArgs);
                    if (upgradeNeeded) {
                        openreq.onupgradeneeded = function(e) {
                            var db = openreq.result;
                            try {
                                db.createObjectStore(dbInfo.storeName);
                                if (e.oldVersion <= 1) {
                                    // Added when support for blob shims was added
                                    db.createObjectStore(DETECT_BLOB_SUPPORT_STORE);
                                }
                            } catch (ex) {
                                if (ex.name === "ConstraintError") {
                                    console.warn('The database "' + dbInfo.name + '"' + " has been upgraded from version " + e.oldVersion + " to version " + e.newVersion + ', but the storage "' + dbInfo.storeName + '" already exists.');
                                } else {
                                    throw ex;
                                }
                            }
                        };
                    }
                    openreq.onerror = function(e) {
                        e.preventDefault();
                        reject(openreq.error);
                    };
                    openreq.onsuccess = function() {
                        resolve(openreq.result);
                        _advanceReadiness(dbInfo);
                    };
                });
            }
            function _getOriginalConnection(dbInfo) {
                return _getConnection(dbInfo, false);
            }
            function _getUpgradedConnection(dbInfo) {
                return _getConnection(dbInfo, true);
            }
            function _isUpgradeNeeded(dbInfo, defaultVersion) {
                if (!dbInfo.db) {
                    return true;
                }
                var isNewStore = !dbInfo.db.objectStoreNames.contains(dbInfo.storeName);
                var isDowngrade = dbInfo.version < dbInfo.db.version;
                var isUpgrade = dbInfo.version > dbInfo.db.version;
                if (isDowngrade) {
                    // If the version is not the default one
                    // then warn for impossible downgrade.
                    if (dbInfo.version !== defaultVersion) {
                        console.warn('The database "' + dbInfo.name + '"' + " can't be downgraded from version " + dbInfo.db.version + " to version " + dbInfo.version + ".");
                    }
                    // Align the versions to prevent errors.
                    dbInfo.version = dbInfo.db.version;
                }
                if (isUpgrade || isNewStore) {
                    // If the store is new then increment the version (if needed).
                    // This will trigger an "upgradeneeded" event which is required
                    // for creating a store.
                    if (isNewStore) {
                        var incVersion = dbInfo.db.version + 1;
                        if (incVersion > dbInfo.version) {
                            dbInfo.version = incVersion;
                        }
                    }
                    return true;
                }
                return false;
            }
            // encode a blob for indexeddb engines that don't support blobs
            function _encodeBlob(blob) {
                return new Promise$1(function(resolve, reject) {
                    var reader = new FileReader();
                    reader.onerror = reject;
                    reader.onloadend = function(e) {
                        var base64 = btoa(e.target.result || "");
                        resolve({
                            __local_forage_encoded_blob: true,
                            data: base64,
                            type: blob.type
                        });
                    };
                    reader.readAsBinaryString(blob);
                });
            }
            // decode an encoded blob
            function _decodeBlob(encodedBlob) {
                var arrayBuff = _binStringToArrayBuffer(atob(encodedBlob.data));
                return createBlob([ arrayBuff ], {
                    type: encodedBlob.type
                });
            }
            // is this one of our fancy encoded blobs?
            function _isEncodedBlob(value) {
                return value && value.__local_forage_encoded_blob;
            }
            // Specialize the default `ready()` function by making it dependent
            // on the current database operations. Thus, the driver will be actually
            // ready when it's been initialized (default) *and* there are no pending
            // operations on the database (initiated by some other instances).
            function _fullyReady(callback) {
                var self = this;
                var promise = self._initReady().then(function() {
                    var dbContext = dbContexts[self._dbInfo.name];
                    if (dbContext && dbContext.dbReady) {
                        return dbContext.dbReady;
                    }
                });
                executeTwoCallbacks(promise, callback, callback);
                return promise;
            }
            // Try to establish a new db connection to replace the
            // current one which is broken (i.e. experiencing
            // InvalidStateError while creating a transaction).
            function _tryReconnect(dbInfo) {
                _deferReadiness(dbInfo);
                var dbContext = dbContexts[dbInfo.name];
                var forages = dbContext.forages;
                for (var i = 0; i < forages.length; i++) {
                    if (forages[i]._dbInfo.db) {
                        forages[i]._dbInfo.db.close();
                        forages[i]._dbInfo.db = null;
                    }
                }
                return _getConnection(dbInfo, false).then(function(db) {
                    for (var j = 0; j < forages.length; j++) {
                        forages[j]._dbInfo.db = db;
                    }
                })["catch"](function(err) {
                    _rejectReadiness(dbInfo, err);
                    throw err;
                });
            }
            // FF doesn't like Promises (micro-tasks) and IDDB store operations,
            // so we have to do it with callbacks
            function createTransaction(dbInfo, mode, callback) {
                try {
                    var tx = dbInfo.db.transaction(dbInfo.storeName, mode);
                    callback(null, tx);
                } catch (err) {
                    if (!dbInfo.db || err.name === "InvalidStateError") {
                        return _tryReconnect(dbInfo).then(function() {
                            var tx = dbInfo.db.transaction(dbInfo.storeName, mode);
                            callback(null, tx);
                        });
                    }
                    callback(err);
                }
            }
            // Open the IndexedDB database (automatically creates one if one didn't
            // previously exist), using any options set in the config.
            function _initStorage(options) {
                var self = this;
                var dbInfo = {
                    db: null
                };
                if (options) {
                    for (var i in options) {
                        dbInfo[i] = options[i];
                    }
                }
                // Initialize a singleton container for all running localForages.
                if (!dbContexts) {
                    dbContexts = {};
                }
                // Get the current context of the database;
                var dbContext = dbContexts[dbInfo.name];
                // ...or create a new context.
                if (!dbContext) {
                    dbContext = {
                        // Running localForages sharing a database.
                        forages: [],
                        // Shared database.
                        db: null,
                        // Database readiness (promise).
                        dbReady: null,
                        // Deferred operations on the database.
                        deferredOperations: []
                    };
                    // Register the new context in the global container.
                    dbContexts[dbInfo.name] = dbContext;
                }
                // Register itself as a running localForage in the current context.
                dbContext.forages.push(self);
                // Replace the default `ready()` function with the specialized one.
                if (!self._initReady) {
                    self._initReady = self.ready;
                    self.ready = _fullyReady;
                }
                // Create an array of initialization states of the related localForages.
                var initPromises = [];
                function ignoreErrors() {
                    // Don't handle errors here,
                    // just makes sure related localForages aren't pending.
                    return Promise$1.resolve();
                }
                for (var j = 0; j < dbContext.forages.length; j++) {
                    var forage = dbContext.forages[j];
                    if (forage !== self) {
                        // Don't wait for itself...
                        initPromises.push(forage._initReady()["catch"](ignoreErrors));
                    }
                }
                // Take a snapshot of the related localForages.
                var forages = dbContext.forages.slice(0);
                // Initialize the connection process only when
                // all the related localForages aren't pending.
                return Promise$1.all(initPromises).then(function() {
                    dbInfo.db = dbContext.db;
                    // Get the connection or open a new one without upgrade.
                    return _getOriginalConnection(dbInfo);
                }).then(function(db) {
                    dbInfo.db = db;
                    if (_isUpgradeNeeded(dbInfo, self._defaultConfig.version)) {
                        // Reopen the database for upgrading.
                        return _getUpgradedConnection(dbInfo);
                    }
                    return db;
                }).then(function(db) {
                    dbInfo.db = dbContext.db = db;
                    self._dbInfo = dbInfo;
                    // Share the final connection amongst related localForages.
                    for (var k = 0; k < forages.length; k++) {
                        var forage = forages[k];
                        if (forage !== self) {
                            // Self is already up-to-date.
                            forage._dbInfo.db = dbInfo.db;
                            forage._dbInfo.version = dbInfo.version;
                        }
                    }
                });
            }
            function getItem(key, callback) {
                var self = this;
                // Cast the key to a string, as that's all we can set as a key.
                if (typeof key !== "string") {
                    console.warn(key + " used as a key, but it is not a string.");
                    key = String(key);
                }
                var promise = new Promise$1(function(resolve, reject) {
                    self.ready().then(function() {
                        createTransaction(self._dbInfo, READ_ONLY, function(err, transaction) {
                            if (err) {
                                return reject(err);
                            }
                            try {
                                var store = transaction.objectStore(self._dbInfo.storeName);
                                var req = store.get(key);
                                req.onsuccess = function() {
                                    var value = req.result;
                                    if (value === undefined) {
                                        value = null;
                                    }
                                    if (_isEncodedBlob(value)) {
                                        value = _decodeBlob(value);
                                    }
                                    resolve(value);
                                };
                                req.onerror = function() {
                                    reject(req.error);
                                };
                            } catch (e) {
                                reject(e);
                            }
                        });
                    })["catch"](reject);
                });
                executeCallback(promise, callback);
                return promise;
            }
            // Iterate over all items stored in database.
            function iterate(iterator, callback) {
                var self = this;
                var promise = new Promise$1(function(resolve, reject) {
                    self.ready().then(function() {
                        createTransaction(self._dbInfo, READ_ONLY, function(err, transaction) {
                            if (err) {
                                return reject(err);
                            }
                            try {
                                var store = transaction.objectStore(self._dbInfo.storeName);
                                var req = store.openCursor();
                                var iterationNumber = 1;
                                req.onsuccess = function() {
                                    var cursor = req.result;
                                    if (cursor) {
                                        var value = cursor.value;
                                        if (_isEncodedBlob(value)) {
                                            value = _decodeBlob(value);
                                        }
                                        var result = iterator(value, cursor.key, iterationNumber++);
                                        // when the iterator callback retuns any
                                        // (non-`undefined`) value, then we stop
                                        // the iteration immediately
                                        if (result !== void 0) {
                                            resolve(result);
                                        } else {
                                            cursor["continue"]();
                                        }
                                    } else {
                                        resolve();
                                    }
                                };
                                req.onerror = function() {
                                    reject(req.error);
                                };
                            } catch (e) {
                                reject(e);
                            }
                        });
                    })["catch"](reject);
                });
                executeCallback(promise, callback);
                return promise;
            }
            function setItem(key, value, callback) {
                var self = this;
                // Cast the key to a string, as that's all we can set as a key.
                if (typeof key !== "string") {
                    console.warn(key + " used as a key, but it is not a string.");
                    key = String(key);
                }
                var promise = new Promise$1(function(resolve, reject) {
                    var dbInfo;
                    self.ready().then(function() {
                        dbInfo = self._dbInfo;
                        if (toString.call(value) === "[object Blob]") {
                            return _checkBlobSupport(dbInfo.db).then(function(blobSupport) {
                                if (blobSupport) {
                                    return value;
                                }
                                return _encodeBlob(value);
                            });
                        }
                        return value;
                    }).then(function(value) {
                        createTransaction(self._dbInfo, READ_WRITE, function(err, transaction) {
                            if (err) {
                                return reject(err);
                            }
                            try {
                                var store = transaction.objectStore(self._dbInfo.storeName);
                                var req = store.put(value, key);
                                // The reason we don't _save_ null is because IE 10 does
                                // not support saving the `null` type in IndexedDB. How
                                // ironic, given the bug below!
                                // See: https://github.com/mozilla/localForage/issues/161
                                if (value === null) {
                                    value = undefined;
                                }
                                transaction.oncomplete = function() {
                                    // Cast to undefined so the value passed to
                                    // callback/promise is the same as what one would get out
                                    // of `getItem()` later. This leads to some weirdness
                                    // (setItem('foo', undefined) will return `null`), but
                                    // it's not my fault localStorage is our baseline and that
                                    // it's weird.
                                    if (value === undefined) {
                                        value = null;
                                    }
                                    resolve(value);
                                };
                                transaction.onabort = transaction.onerror = function() {
                                    var err = req.error ? req.error : req.transaction.error;
                                    reject(err);
                                };
                            } catch (e) {
                                reject(e);
                            }
                        });
                    })["catch"](reject);
                });
                executeCallback(promise, callback);
                return promise;
            }
            function removeItem(key, callback) {
                var self = this;
                // Cast the key to a string, as that's all we can set as a key.
                if (typeof key !== "string") {
                    console.warn(key + " used as a key, but it is not a string.");
                    key = String(key);
                }
                var promise = new Promise$1(function(resolve, reject) {
                    self.ready().then(function() {
                        createTransaction(self._dbInfo, READ_WRITE, function(err, transaction) {
                            if (err) {
                                return reject(err);
                            }
                            try {
                                var store = transaction.objectStore(self._dbInfo.storeName);
                                // We use a Grunt task to make this safe for IE and some
                                // versions of Android (including those used by Cordova).
                                // Normally IE won't like `.delete()` and will insist on
                                // using `['delete']()`, but we have a build step that
                                // fixes this for us now.
                                var req = store["delete"](key);
                                transaction.oncomplete = function() {
                                    resolve();
                                };
                                transaction.onerror = function() {
                                    reject(req.error);
                                };
                                // The request will be also be aborted if we've exceeded our storage
                                // space.
                                transaction.onabort = function() {
                                    var err = req.error ? req.error : req.transaction.error;
                                    reject(err);
                                };
                            } catch (e) {
                                reject(e);
                            }
                        });
                    })["catch"](reject);
                });
                executeCallback(promise, callback);
                return promise;
            }
            function clear(callback) {
                var self = this;
                var promise = new Promise$1(function(resolve, reject) {
                    self.ready().then(function() {
                        createTransaction(self._dbInfo, READ_WRITE, function(err, transaction) {
                            if (err) {
                                return reject(err);
                            }
                            try {
                                var store = transaction.objectStore(self._dbInfo.storeName);
                                var req = store.clear();
                                transaction.oncomplete = function() {
                                    resolve();
                                };
                                transaction.onabort = transaction.onerror = function() {
                                    var err = req.error ? req.error : req.transaction.error;
                                    reject(err);
                                };
                            } catch (e) {
                                reject(e);
                            }
                        });
                    })["catch"](reject);
                });
                executeCallback(promise, callback);
                return promise;
            }
            function length(callback) {
                var self = this;
                var promise = new Promise$1(function(resolve, reject) {
                    self.ready().then(function() {
                        createTransaction(self._dbInfo, READ_ONLY, function(err, transaction) {
                            if (err) {
                                return reject(err);
                            }
                            try {
                                var store = transaction.objectStore(self._dbInfo.storeName);
                                var req = store.count();
                                req.onsuccess = function() {
                                    resolve(req.result);
                                };
                                req.onerror = function() {
                                    reject(req.error);
                                };
                            } catch (e) {
                                reject(e);
                            }
                        });
                    })["catch"](reject);
                });
                executeCallback(promise, callback);
                return promise;
            }
            function key(n, callback) {
                var self = this;
                var promise = new Promise$1(function(resolve, reject) {
                    if (n < 0) {
                        resolve(null);
                        return;
                    }
                    self.ready().then(function() {
                        createTransaction(self._dbInfo, READ_ONLY, function(err, transaction) {
                            if (err) {
                                return reject(err);
                            }
                            try {
                                var store = transaction.objectStore(self._dbInfo.storeName);
                                var advanced = false;
                                var req = store.openCursor();
                                req.onsuccess = function() {
                                    var cursor = req.result;
                                    if (!cursor) {
                                        // this means there weren't enough keys
                                        resolve(null);
                                        return;
                                    }
                                    if (n === 0) {
                                        // We have the first key, return it if that's what they
                                        // wanted.
                                        resolve(cursor.key);
                                    } else {
                                        if (!advanced) {
                                            // Otherwise, ask the cursor to skip ahead n
                                            // records.
                                            advanced = true;
                                            cursor.advance(n);
                                        } else {
                                            // When we get here, we've got the nth key.
                                            resolve(cursor.key);
                                        }
                                    }
                                };
                                req.onerror = function() {
                                    reject(req.error);
                                };
                            } catch (e) {
                                reject(e);
                            }
                        });
                    })["catch"](reject);
                });
                executeCallback(promise, callback);
                return promise;
            }
            function keys(callback) {
                var self = this;
                var promise = new Promise$1(function(resolve, reject) {
                    self.ready().then(function() {
                        createTransaction(self._dbInfo, READ_ONLY, function(err, transaction) {
                            if (err) {
                                return reject(err);
                            }
                            try {
                                var store = transaction.objectStore(self._dbInfo.storeName);
                                var req = store.openCursor();
                                var keys = [];
                                req.onsuccess = function() {
                                    var cursor = req.result;
                                    if (!cursor) {
                                        resolve(keys);
                                        return;
                                    }
                                    keys.push(cursor.key);
                                    cursor["continue"]();
                                };
                                req.onerror = function() {
                                    reject(req.error);
                                };
                            } catch (e) {
                                reject(e);
                            }
                        });
                    })["catch"](reject);
                });
                executeCallback(promise, callback);
                return promise;
            }
            var asyncStorage = {
                _driver: "asyncStorage",
                _initStorage: _initStorage,
                iterate: iterate,
                getItem: getItem,
                setItem: setItem,
                removeItem: removeItem,
                clear: clear,
                length: length,
                key: key,
                keys: keys
            };
            // Sadly, the best way to save binary data in WebSQL/localStorage is serializing
            // it to Base64, so this is how we store it to prevent very strange errors with less
            // verbose ways of binary <-> string data storage.
            var BASE_CHARS = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/";
            var BLOB_TYPE_PREFIX = "~~local_forage_type~";
            var BLOB_TYPE_PREFIX_REGEX = /^~~local_forage_type~([^~]+)~/;
            var SERIALIZED_MARKER = "__lfsc__:";
            var SERIALIZED_MARKER_LENGTH = SERIALIZED_MARKER.length;
            // OMG the serializations!
            var TYPE_ARRAYBUFFER = "arbf";
            var TYPE_BLOB = "blob";
            var TYPE_INT8ARRAY = "si08";
            var TYPE_UINT8ARRAY = "ui08";
            var TYPE_UINT8CLAMPEDARRAY = "uic8";
            var TYPE_INT16ARRAY = "si16";
            var TYPE_INT32ARRAY = "si32";
            var TYPE_UINT16ARRAY = "ur16";
            var TYPE_UINT32ARRAY = "ui32";
            var TYPE_FLOAT32ARRAY = "fl32";
            var TYPE_FLOAT64ARRAY = "fl64";
            var TYPE_SERIALIZED_MARKER_LENGTH = SERIALIZED_MARKER_LENGTH + TYPE_ARRAYBUFFER.length;
            var toString$1 = Object.prototype.toString;
            function stringToBuffer(serializedString) {
                // Fill the string into a ArrayBuffer.
                var bufferLength = serializedString.length * .75;
                var len = serializedString.length;
                var i;
                var p = 0;
                var encoded1, encoded2, encoded3, encoded4;
                if (serializedString[serializedString.length - 1] === "=") {
                    bufferLength--;
                    if (serializedString[serializedString.length - 2] === "=") {
                        bufferLength--;
                    }
                }
                var buffer = new ArrayBuffer(bufferLength);
                var bytes = new Uint8Array(buffer);
                for (i = 0; i < len; i += 4) {
                    encoded1 = BASE_CHARS.indexOf(serializedString[i]);
                    encoded2 = BASE_CHARS.indexOf(serializedString[i + 1]);
                    encoded3 = BASE_CHARS.indexOf(serializedString[i + 2]);
                    encoded4 = BASE_CHARS.indexOf(serializedString[i + 3]);
                    /*jslint bitwise: true */
                    bytes[p++] = encoded1 << 2 | encoded2 >> 4;
                    bytes[p++] = (encoded2 & 15) << 4 | encoded3 >> 2;
                    bytes[p++] = (encoded3 & 3) << 6 | encoded4 & 63;
                }
                return buffer;
            }
            // Converts a buffer to a string to store, serialized, in the backend
            // storage library.
            function bufferToString(buffer) {
                // base64-arraybuffer
                var bytes = new Uint8Array(buffer);
                var base64String = "";
                var i;
                for (i = 0; i < bytes.length; i += 3) {
                    /*jslint bitwise: true */
                    base64String += BASE_CHARS[bytes[i] >> 2];
                    base64String += BASE_CHARS[(bytes[i] & 3) << 4 | bytes[i + 1] >> 4];
                    base64String += BASE_CHARS[(bytes[i + 1] & 15) << 2 | bytes[i + 2] >> 6];
                    base64String += BASE_CHARS[bytes[i + 2] & 63];
                }
                if (bytes.length % 3 === 2) {
                    base64String = base64String.substring(0, base64String.length - 1) + "=";
                } else if (bytes.length % 3 === 1) {
                    base64String = base64String.substring(0, base64String.length - 2) + "==";
                }
                return base64String;
            }
            // Serialize a value, afterwards executing a callback (which usually
            // instructs the `setItem()` callback/promise to be executed). This is how
            // we store binary data with localStorage.
            function serialize(value, callback) {
                var valueType = "";
                if (value) {
                    valueType = toString$1.call(value);
                }
                // Cannot use `value instanceof ArrayBuffer` or such here, as these
                // checks fail when running the tests using casper.js...
                //
                // TODO: See why those tests fail and use a better solution.
                if (value && (valueType === "[object ArrayBuffer]" || value.buffer && toString$1.call(value.buffer) === "[object ArrayBuffer]")) {
                    // Convert binary arrays to a string and prefix the string with
                    // a special marker.
                    var buffer;
                    var marker = SERIALIZED_MARKER;
                    if (value instanceof ArrayBuffer) {
                        buffer = value;
                        marker += TYPE_ARRAYBUFFER;
                    } else {
                        buffer = value.buffer;
                        if (valueType === "[object Int8Array]") {
                            marker += TYPE_INT8ARRAY;
                        } else if (valueType === "[object Uint8Array]") {
                            marker += TYPE_UINT8ARRAY;
                        } else if (valueType === "[object Uint8ClampedArray]") {
                            marker += TYPE_UINT8CLAMPEDARRAY;
                        } else if (valueType === "[object Int16Array]") {
                            marker += TYPE_INT16ARRAY;
                        } else if (valueType === "[object Uint16Array]") {
                            marker += TYPE_UINT16ARRAY;
                        } else if (valueType === "[object Int32Array]") {
                            marker += TYPE_INT32ARRAY;
                        } else if (valueType === "[object Uint32Array]") {
                            marker += TYPE_UINT32ARRAY;
                        } else if (valueType === "[object Float32Array]") {
                            marker += TYPE_FLOAT32ARRAY;
                        } else if (valueType === "[object Float64Array]") {
                            marker += TYPE_FLOAT64ARRAY;
                        } else {
                            callback(new Error("Failed to get type for BinaryArray"));
                        }
                    }
                    callback(marker + bufferToString(buffer));
                } else if (valueType === "[object Blob]") {
                    // Conver the blob to a binaryArray and then to a string.
                    var fileReader = new FileReader();
                    fileReader.onload = function() {
                        // Backwards-compatible prefix for the blob type.
                        var str = BLOB_TYPE_PREFIX + value.type + "~" + bufferToString(this.result);
                        callback(SERIALIZED_MARKER + TYPE_BLOB + str);
                    };
                    fileReader.readAsArrayBuffer(value);
                } else {
                    try {
                        callback(JSON.stringify(value));
                    } catch (e) {
                        console.error("Couldn't convert value into a JSON string: ", value);
                        callback(null, e);
                    }
                }
            }
            // Deserialize data we've inserted into a value column/field. We place
            // special markers into our strings to mark them as encoded; this isn't
            // as nice as a meta field, but it's the only sane thing we can do whilst
            // keeping localStorage support intact.
            //
            // Oftentimes this will just deserialize JSON content, but if we have a
            // special marker (SERIALIZED_MARKER, defined above), we will extract
            // some kind of arraybuffer/binary data/typed array out of the string.
            function deserialize(value) {
                // If we haven't marked this string as being specially serialized (i.e.
                // something other than serialized JSON), we can just return it and be
                // done with it.
                if (value.substring(0, SERIALIZED_MARKER_LENGTH) !== SERIALIZED_MARKER) {
                    return JSON.parse(value);
                }
                // The following code deals with deserializing some kind of Blob or
                // TypedArray. First we separate out the type of data we're dealing
                // with from the data itself.
                var serializedString = value.substring(TYPE_SERIALIZED_MARKER_LENGTH);
                var type = value.substring(SERIALIZED_MARKER_LENGTH, TYPE_SERIALIZED_MARKER_LENGTH);
                var blobType;
                // Backwards-compatible blob type serialization strategy.
                // DBs created with older versions of localForage will simply not have the blob type.
                if (type === TYPE_BLOB && BLOB_TYPE_PREFIX_REGEX.test(serializedString)) {
                    var matcher = serializedString.match(BLOB_TYPE_PREFIX_REGEX);
                    blobType = matcher[1];
                    serializedString = serializedString.substring(matcher[0].length);
                }
                var buffer = stringToBuffer(serializedString);
                // Return the right type based on the code/type set during
                // serialization.
                switch (type) {
                  case TYPE_ARRAYBUFFER:
                    return buffer;

                  case TYPE_BLOB:
                    return createBlob([ buffer ], {
                        type: blobType
                    });

                  case TYPE_INT8ARRAY:
                    return new Int8Array(buffer);

                  case TYPE_UINT8ARRAY:
                    return new Uint8Array(buffer);

                  case TYPE_UINT8CLAMPEDARRAY:
                    return new Uint8ClampedArray(buffer);

                  case TYPE_INT16ARRAY:
                    return new Int16Array(buffer);

                  case TYPE_UINT16ARRAY:
                    return new Uint16Array(buffer);

                  case TYPE_INT32ARRAY:
                    return new Int32Array(buffer);

                  case TYPE_UINT32ARRAY:
                    return new Uint32Array(buffer);

                  case TYPE_FLOAT32ARRAY:
                    return new Float32Array(buffer);

                  case TYPE_FLOAT64ARRAY:
                    return new Float64Array(buffer);

                  default:
                    throw new Error("Unkown type: " + type);
                }
            }
            var localforageSerializer = {
                serialize: serialize,
                deserialize: deserialize,
                stringToBuffer: stringToBuffer,
                bufferToString: bufferToString
            };
            /*
 * Includes code from:
 *
 * base64-arraybuffer
 * https://github.com/niklasvh/base64-arraybuffer
 *
 * Copyright (c) 2012 Niklas von Hertzen
 * Licensed under the MIT license.
 */
            // Open the WebSQL database (automatically creates one if one didn't
            // previously exist), using any options set in the config.
            function _initStorage$1(options) {
                var self = this;
                var dbInfo = {
                    db: null
                };
                if (options) {
                    for (var i in options) {
                        dbInfo[i] = typeof options[i] !== "string" ? options[i].toString() : options[i];
                    }
                }
                var dbInfoPromise = new Promise$1(function(resolve, reject) {
                    // Open the database; the openDatabase API will automatically
                    // create it for us if it doesn't exist.
                    try {
                        dbInfo.db = openDatabase(dbInfo.name, String(dbInfo.version), dbInfo.description, dbInfo.size);
                    } catch (e) {
                        return reject(e);
                    }
                    // Create our key/value table if it doesn't exist.
                    dbInfo.db.transaction(function(t) {
                        t.executeSql("CREATE TABLE IF NOT EXISTS " + dbInfo.storeName + " (id INTEGER PRIMARY KEY, key unique, value)", [], function() {
                            self._dbInfo = dbInfo;
                            resolve();
                        }, function(t, error) {
                            reject(error);
                        });
                    });
                });
                dbInfo.serializer = localforageSerializer;
                return dbInfoPromise;
            }
            function getItem$1(key, callback) {
                var self = this;
                // Cast the key to a string, as that's all we can set as a key.
                if (typeof key !== "string") {
                    console.warn(key + " used as a key, but it is not a string.");
                    key = String(key);
                }
                var promise = new Promise$1(function(resolve, reject) {
                    self.ready().then(function() {
                        var dbInfo = self._dbInfo;
                        dbInfo.db.transaction(function(t) {
                            t.executeSql("SELECT * FROM " + dbInfo.storeName + " WHERE key = ? LIMIT 1", [ key ], function(t, results) {
                                var result = results.rows.length ? results.rows.item(0).value : null;
                                // Check to see if this is serialized content we need to
                                // unpack.
                                if (result) {
                                    result = dbInfo.serializer.deserialize(result);
                                }
                                resolve(result);
                            }, function(t, error) {
                                reject(error);
                            });
                        });
                    })["catch"](reject);
                });
                executeCallback(promise, callback);
                return promise;
            }
            function iterate$1(iterator, callback) {
                var self = this;
                var promise = new Promise$1(function(resolve, reject) {
                    self.ready().then(function() {
                        var dbInfo = self._dbInfo;
                        dbInfo.db.transaction(function(t) {
                            t.executeSql("SELECT * FROM " + dbInfo.storeName, [], function(t, results) {
                                var rows = results.rows;
                                var length = rows.length;
                                for (var i = 0; i < length; i++) {
                                    var item = rows.item(i);
                                    var result = item.value;
                                    // Check to see if this is serialized content
                                    // we need to unpack.
                                    if (result) {
                                        result = dbInfo.serializer.deserialize(result);
                                    }
                                    result = iterator(result, item.key, i + 1);
                                    // void(0) prevents problems with redefinition
                                    // of `undefined`.
                                    if (result !== void 0) {
                                        resolve(result);
                                        return;
                                    }
                                }
                                resolve();
                            }, function(t, error) {
                                reject(error);
                            });
                        });
                    })["catch"](reject);
                });
                executeCallback(promise, callback);
                return promise;
            }
            function _setItem(key, value, callback, retriesLeft) {
                var self = this;
                // Cast the key to a string, as that's all we can set as a key.
                if (typeof key !== "string") {
                    console.warn(key + " used as a key, but it is not a string.");
                    key = String(key);
                }
                var promise = new Promise$1(function(resolve, reject) {
                    self.ready().then(function() {
                        // The localStorage API doesn't return undefined values in an
                        // "expected" way, so undefined is always cast to null in all
                        // drivers. See: https://github.com/mozilla/localForage/pull/42
                        if (value === undefined) {
                            value = null;
                        }
                        // Save the original value to pass to the callback.
                        var originalValue = value;
                        var dbInfo = self._dbInfo;
                        dbInfo.serializer.serialize(value, function(value, error) {
                            if (error) {
                                reject(error);
                            } else {
                                dbInfo.db.transaction(function(t) {
                                    t.executeSql("INSERT OR REPLACE INTO " + dbInfo.storeName + " (key, value) VALUES (?, ?)", [ key, value ], function() {
                                        resolve(originalValue);
                                    }, function(t, error) {
                                        reject(error);
                                    });
                                }, function(sqlError) {
                                    // The transaction failed; check
                                    // to see if it's a quota error.
                                    if (sqlError.code === sqlError.QUOTA_ERR) {
                                        // We reject the callback outright for now, but
                                        // it's worth trying to re-run the transaction.
                                        // Even if the user accepts the prompt to use
                                        // more storage on Safari, this error will
                                        // be called.
                                        //
                                        // Try to re-run the transaction.
                                        if (retriesLeft > 0) {
                                            resolve(_setItem.apply(self, [ key, originalValue, callback, retriesLeft - 1 ]));
                                            return;
                                        }
                                        reject(sqlError);
                                    }
                                });
                            }
                        });
                    })["catch"](reject);
                });
                executeCallback(promise, callback);
                return promise;
            }
            function setItem$1(key, value, callback) {
                return _setItem.apply(this, [ key, value, callback, 1 ]);
            }
            function removeItem$1(key, callback) {
                var self = this;
                // Cast the key to a string, as that's all we can set as a key.
                if (typeof key !== "string") {
                    console.warn(key + " used as a key, but it is not a string.");
                    key = String(key);
                }
                var promise = new Promise$1(function(resolve, reject) {
                    self.ready().then(function() {
                        var dbInfo = self._dbInfo;
                        dbInfo.db.transaction(function(t) {
                            t.executeSql("DELETE FROM " + dbInfo.storeName + " WHERE key = ?", [ key ], function() {
                                resolve();
                            }, function(t, error) {
                                reject(error);
                            });
                        });
                    })["catch"](reject);
                });
                executeCallback(promise, callback);
                return promise;
            }
            // Deletes every item in the table.
            // TODO: Find out if this resets the AUTO_INCREMENT number.
            function clear$1(callback) {
                var self = this;
                var promise = new Promise$1(function(resolve, reject) {
                    self.ready().then(function() {
                        var dbInfo = self._dbInfo;
                        dbInfo.db.transaction(function(t) {
                            t.executeSql("DELETE FROM " + dbInfo.storeName, [], function() {
                                resolve();
                            }, function(t, error) {
                                reject(error);
                            });
                        });
                    })["catch"](reject);
                });
                executeCallback(promise, callback);
                return promise;
            }
            // Does a simple `COUNT(key)` to get the number of items stored in
            // localForage.
            function length$1(callback) {
                var self = this;
                var promise = new Promise$1(function(resolve, reject) {
                    self.ready().then(function() {
                        var dbInfo = self._dbInfo;
                        dbInfo.db.transaction(function(t) {
                            // Ahhh, SQL makes this one soooooo easy.
                            t.executeSql("SELECT COUNT(key) as c FROM " + dbInfo.storeName, [], function(t, results) {
                                var result = results.rows.item(0).c;
                                resolve(result);
                            }, function(t, error) {
                                reject(error);
                            });
                        });
                    })["catch"](reject);
                });
                executeCallback(promise, callback);
                return promise;
            }
            // Return the key located at key index X; essentially gets the key from a
            // `WHERE id = ?`. This is the most efficient way I can think to implement
            // this rarely-used (in my experience) part of the API, but it can seem
            // inconsistent, because we do `INSERT OR REPLACE INTO` on `setItem()`, so
            // the ID of each key will change every time it's updated. Perhaps a stored
            // procedure for the `setItem()` SQL would solve this problem?
            // TODO: Don't change ID on `setItem()`.
            function key$1(n, callback) {
                var self = this;
                var promise = new Promise$1(function(resolve, reject) {
                    self.ready().then(function() {
                        var dbInfo = self._dbInfo;
                        dbInfo.db.transaction(function(t) {
                            t.executeSql("SELECT key FROM " + dbInfo.storeName + " WHERE id = ? LIMIT 1", [ n + 1 ], function(t, results) {
                                var result = results.rows.length ? results.rows.item(0).key : null;
                                resolve(result);
                            }, function(t, error) {
                                reject(error);
                            });
                        });
                    })["catch"](reject);
                });
                executeCallback(promise, callback);
                return promise;
            }
            function keys$1(callback) {
                var self = this;
                var promise = new Promise$1(function(resolve, reject) {
                    self.ready().then(function() {
                        var dbInfo = self._dbInfo;
                        dbInfo.db.transaction(function(t) {
                            t.executeSql("SELECT key FROM " + dbInfo.storeName, [], function(t, results) {
                                var keys = [];
                                for (var i = 0; i < results.rows.length; i++) {
                                    keys.push(results.rows.item(i).key);
                                }
                                resolve(keys);
                            }, function(t, error) {
                                reject(error);
                            });
                        });
                    })["catch"](reject);
                });
                executeCallback(promise, callback);
                return promise;
            }
            var webSQLStorage = {
                _driver: "webSQLStorage",
                _initStorage: _initStorage$1,
                iterate: iterate$1,
                getItem: getItem$1,
                setItem: setItem$1,
                removeItem: removeItem$1,
                clear: clear$1,
                length: length$1,
                key: key$1,
                keys: keys$1
            };
            // Config the localStorage backend, using options set in the config.
            function _initStorage$2(options) {
                var self = this;
                var dbInfo = {};
                if (options) {
                    for (var i in options) {
                        dbInfo[i] = options[i];
                    }
                }
                dbInfo.keyPrefix = dbInfo.name + "/";
                if (dbInfo.storeName !== self._defaultConfig.storeName) {
                    dbInfo.keyPrefix += dbInfo.storeName + "/";
                }
                self._dbInfo = dbInfo;
                dbInfo.serializer = localforageSerializer;
                return Promise$1.resolve();
            }
            // Remove all keys from the datastore, effectively destroying all data in
            // the app's key/value store!
            function clear$2(callback) {
                var self = this;
                var promise = self.ready().then(function() {
                    var keyPrefix = self._dbInfo.keyPrefix;
                    for (var i = localStorage.length - 1; i >= 0; i--) {
                        var key = localStorage.key(i);
                        if (key.indexOf(keyPrefix) === 0) {
                            localStorage.removeItem(key);
                        }
                    }
                });
                executeCallback(promise, callback);
                return promise;
            }
            // Retrieve an item from the store. Unlike the original async_storage
            // library in Gaia, we don't modify return values at all. If a key's value
            // is `undefined`, we pass that value to the callback function.
            function getItem$2(key, callback) {
                var self = this;
                // Cast the key to a string, as that's all we can set as a key.
                if (typeof key !== "string") {
                    console.warn(key + " used as a key, but it is not a string.");
                    key = String(key);
                }
                var promise = self.ready().then(function() {
                    var dbInfo = self._dbInfo;
                    var result = localStorage.getItem(dbInfo.keyPrefix + key);
                    // If a result was found, parse it from the serialized
                    // string into a JS object. If result isn't truthy, the key
                    // is likely undefined and we'll pass it straight to the
                    // callback.
                    if (result) {
                        result = dbInfo.serializer.deserialize(result);
                    }
                    return result;
                });
                executeCallback(promise, callback);
                return promise;
            }
            // Iterate over all items in the store.
            function iterate$2(iterator, callback) {
                var self = this;
                var promise = self.ready().then(function() {
                    var dbInfo = self._dbInfo;
                    var keyPrefix = dbInfo.keyPrefix;
                    var keyPrefixLength = keyPrefix.length;
                    var length = localStorage.length;
                    // We use a dedicated iterator instead of the `i` variable below
                    // so other keys we fetch in localStorage aren't counted in
                    // the `iterationNumber` argument passed to the `iterate()`
                    // callback.
                    //
                    // See: github.com/mozilla/localForage/pull/435#discussion_r38061530
                    var iterationNumber = 1;
                    for (var i = 0; i < length; i++) {
                        var key = localStorage.key(i);
                        if (key.indexOf(keyPrefix) !== 0) {
                            continue;
                        }
                        var value = localStorage.getItem(key);
                        // If a result was found, parse it from the serialized
                        // string into a JS object. If result isn't truthy, the
                        // key is likely undefined and we'll pass it straight
                        // to the iterator.
                        if (value) {
                            value = dbInfo.serializer.deserialize(value);
                        }
                        value = iterator(value, key.substring(keyPrefixLength), iterationNumber++);
                        if (value !== void 0) {
                            return value;
                        }
                    }
                });
                executeCallback(promise, callback);
                return promise;
            }
            // Same as localStorage's key() method, except takes a callback.
            function key$2(n, callback) {
                var self = this;
                var promise = self.ready().then(function() {
                    var dbInfo = self._dbInfo;
                    var result;
                    try {
                        result = localStorage.key(n);
                    } catch (error) {
                        result = null;
                    }
                    // Remove the prefix from the key, if a key is found.
                    if (result) {
                        result = result.substring(dbInfo.keyPrefix.length);
                    }
                    return result;
                });
                executeCallback(promise, callback);
                return promise;
            }
            function keys$2(callback) {
                var self = this;
                var promise = self.ready().then(function() {
                    var dbInfo = self._dbInfo;
                    var length = localStorage.length;
                    var keys = [];
                    for (var i = 0; i < length; i++) {
                        if (localStorage.key(i).indexOf(dbInfo.keyPrefix) === 0) {
                            keys.push(localStorage.key(i).substring(dbInfo.keyPrefix.length));
                        }
                    }
                    return keys;
                });
                executeCallback(promise, callback);
                return promise;
            }
            // Supply the number of keys in the datastore to the callback function.
            function length$2(callback) {
                var self = this;
                var promise = self.keys().then(function(keys) {
                    return keys.length;
                });
                executeCallback(promise, callback);
                return promise;
            }
            // Remove an item from the store, nice and simple.
            function removeItem$2(key, callback) {
                var self = this;
                // Cast the key to a string, as that's all we can set as a key.
                if (typeof key !== "string") {
                    console.warn(key + " used as a key, but it is not a string.");
                    key = String(key);
                }
                var promise = self.ready().then(function() {
                    var dbInfo = self._dbInfo;
                    localStorage.removeItem(dbInfo.keyPrefix + key);
                });
                executeCallback(promise, callback);
                return promise;
            }
            // Set a key's value and run an optional callback once the value is set.
            // Unlike Gaia's implementation, the callback function is passed the value,
            // in case you want to operate on that value only after you're sure it
            // saved, or something like that.
            function setItem$2(key, value, callback) {
                var self = this;
                // Cast the key to a string, as that's all we can set as a key.
                if (typeof key !== "string") {
                    console.warn(key + " used as a key, but it is not a string.");
                    key = String(key);
                }
                var promise = self.ready().then(function() {
                    // Convert undefined values to null.
                    // https://github.com/mozilla/localForage/pull/42
                    if (value === undefined) {
                        value = null;
                    }
                    // Save the original value to pass to the callback.
                    var originalValue = value;
                    return new Promise$1(function(resolve, reject) {
                        var dbInfo = self._dbInfo;
                        dbInfo.serializer.serialize(value, function(value, error) {
                            if (error) {
                                reject(error);
                            } else {
                                try {
                                    localStorage.setItem(dbInfo.keyPrefix + key, value);
                                    resolve(originalValue);
                                } catch (e) {
                                    // localStorage capacity exceeded.
                                    // TODO: Make this a specific error/event.
                                    if (e.name === "QuotaExceededError" || e.name === "NS_ERROR_DOM_QUOTA_REACHED") {
                                        reject(e);
                                    }
                                    reject(e);
                                }
                            }
                        });
                    });
                });
                executeCallback(promise, callback);
                return promise;
            }
            var localStorageWrapper = {
                _driver: "localStorageWrapper",
                _initStorage: _initStorage$2,
                // Default API, from Gaia/localStorage.
                iterate: iterate$2,
                getItem: getItem$2,
                setItem: setItem$2,
                removeItem: removeItem$2,
                clear: clear$2,
                length: length$2,
                key: key$2,
                keys: keys$2
            };
            // Custom drivers are stored here when `defineDriver()` is called.
            // They are shared across all instances of localForage.
            var CustomDrivers = {};
            var DriverType = {
                INDEXEDDB: "asyncStorage",
                LOCALSTORAGE: "localStorageWrapper",
                WEBSQL: "webSQLStorage"
            };
            var DefaultDriverOrder = [ DriverType.INDEXEDDB, DriverType.WEBSQL, DriverType.LOCALSTORAGE ];
            var LibraryMethods = [ "clear", "getItem", "iterate", "key", "keys", "length", "removeItem", "setItem" ];
            var DefaultConfig = {
                description: "",
                driver: DefaultDriverOrder.slice(),
                name: "localforage",
                // Default DB size is _JUST UNDER_ 5MB, as it's the highest size
                // we can use without a prompt.
                size: 4980736,
                storeName: "keyvaluepairs",
                version: 1
            };
            var driverSupport = {};
            // Check to see if IndexedDB is available and if it is the latest
            // implementation; it's our preferred backend library. We use "_spec_test"
            // as the name of the database because it's not the one we'll operate on,
            // but it's useful to make sure its using the right spec.
            // See: https://github.com/mozilla/localForage/issues/128
            driverSupport[DriverType.INDEXEDDB] = isIndexedDBValid();
            driverSupport[DriverType.WEBSQL] = isWebSQLValid();
            driverSupport[DriverType.LOCALSTORAGE] = isLocalStorageValid();
            var isArray = Array.isArray || function(arg) {
                return Object.prototype.toString.call(arg) === "[object Array]";
            };
            function callWhenReady(localForageInstance, libraryMethod) {
                localForageInstance[libraryMethod] = function() {
                    var _args = arguments;
                    return localForageInstance.ready().then(function() {
                        return localForageInstance[libraryMethod].apply(localForageInstance, _args);
                    });
                };
            }
            function extend() {
                for (var i = 1; i < arguments.length; i++) {
                    var arg = arguments[i];
                    if (arg) {
                        for (var key in arg) {
                            if (arg.hasOwnProperty(key)) {
                                if (isArray(arg[key])) {
                                    arguments[0][key] = arg[key].slice();
                                } else {
                                    arguments[0][key] = arg[key];
                                }
                            }
                        }
                    }
                }
                return arguments[0];
            }
            function isLibraryDriver(driverName) {
                for (var driver in DriverType) {
                    if (DriverType.hasOwnProperty(driver) && DriverType[driver] === driverName) {
                        return true;
                    }
                }
                return false;
            }
            var LocalForage = function() {
                function LocalForage(options) {
                    _classCallCheck(this, LocalForage);
                    this.INDEXEDDB = DriverType.INDEXEDDB;
                    this.LOCALSTORAGE = DriverType.LOCALSTORAGE;
                    this.WEBSQL = DriverType.WEBSQL;
                    this._defaultConfig = extend({}, DefaultConfig);
                    this._config = extend({}, this._defaultConfig, options);
                    this._driverSet = null;
                    this._initDriver = null;
                    this._ready = false;
                    this._dbInfo = null;
                    this._wrapLibraryMethodsWithReady();
                    this.setDriver(this._config.driver)["catch"](function() {});
                }
                // Set any config values for localForage; can be called anytime before
                // the first API call (e.g. `getItem`, `setItem`).
                // We loop through options so we don't overwrite existing config
                // values.
                LocalForage.prototype.config = function config(options) {
                    // If the options argument is an object, we use it to set values.
                    // Otherwise, we return either a specified config value or all
                    // config values.
                    if ((typeof options === "undefined" ? "undefined" : _typeof(options)) === "object") {
                        // If localforage is ready and fully initialized, we can't set
                        // any new configuration values. Instead, we return an error.
                        if (this._ready) {
                            return new Error("Can't call config() after localforage " + "has been used.");
                        }
                        for (var i in options) {
                            if (i === "storeName") {
                                options[i] = options[i].replace(/\W/g, "_");
                            }
                            if (i === "version" && typeof options[i] !== "number") {
                                return new Error("Database version must be a number.");
                            }
                            this._config[i] = options[i];
                        }
                        // after all config options are set and
                        // the driver option is used, try setting it
                        if ("driver" in options && options.driver) {
                            return this.setDriver(this._config.driver);
                        }
                        return true;
                    } else if (typeof options === "string") {
                        return this._config[options];
                    } else {
                        return this._config;
                    }
                };
                // Used to define a custom driver, shared across all instances of
                // localForage.
                LocalForage.prototype.defineDriver = function defineDriver(driverObject, callback, errorCallback) {
                    var promise = new Promise$1(function(resolve, reject) {
                        try {
                            var driverName = driverObject._driver;
                            var complianceError = new Error("Custom driver not compliant; see " + "https://mozilla.github.io/localForage/#definedriver");
                            var namingError = new Error("Custom driver name already in use: " + driverObject._driver);
                            // A driver name should be defined and not overlap with the
                            // library-defined, default drivers.
                            if (!driverObject._driver) {
                                reject(complianceError);
                                return;
                            }
                            if (isLibraryDriver(driverObject._driver)) {
                                reject(namingError);
                                return;
                            }
                            var customDriverMethods = LibraryMethods.concat("_initStorage");
                            for (var i = 0; i < customDriverMethods.length; i++) {
                                var customDriverMethod = customDriverMethods[i];
                                if (!customDriverMethod || !driverObject[customDriverMethod] || typeof driverObject[customDriverMethod] !== "function") {
                                    reject(complianceError);
                                    return;
                                }
                            }
                            var setDriverSupport = function setDriverSupport(support) {
                                driverSupport[driverName] = support;
                                CustomDrivers[driverName] = driverObject;
                                resolve();
                            };
                            if ("_support" in driverObject) {
                                if (driverObject._support && typeof driverObject._support === "function") {
                                    driverObject._support().then(setDriverSupport, reject);
                                } else {
                                    setDriverSupport(!!driverObject._support);
                                }
                            } else {
                                setDriverSupport(true);
                            }
                        } catch (e) {
                            reject(e);
                        }
                    });
                    executeTwoCallbacks(promise, callback, errorCallback);
                    return promise;
                };
                LocalForage.prototype.driver = function driver() {
                    return this._driver || null;
                };
                LocalForage.prototype.getDriver = function getDriver(driverName, callback, errorCallback) {
                    var self = this;
                    var getDriverPromise = Promise$1.resolve().then(function() {
                        if (isLibraryDriver(driverName)) {
                            switch (driverName) {
                              case self.INDEXEDDB:
                                return asyncStorage;

                              case self.LOCALSTORAGE:
                                return localStorageWrapper;

                              case self.WEBSQL:
                                return webSQLStorage;
                            }
                        } else if (CustomDrivers[driverName]) {
                            return CustomDrivers[driverName];
                        } else {
                            throw new Error("Driver not found.");
                        }
                    });
                    executeTwoCallbacks(getDriverPromise, callback, errorCallback);
                    return getDriverPromise;
                };
                LocalForage.prototype.getSerializer = function getSerializer(callback) {
                    var serializerPromise = Promise$1.resolve(localforageSerializer);
                    executeTwoCallbacks(serializerPromise, callback);
                    return serializerPromise;
                };
                LocalForage.prototype.ready = function ready(callback) {
                    var self = this;
                    var promise = self._driverSet.then(function() {
                        if (self._ready === null) {
                            self._ready = self._initDriver();
                        }
                        return self._ready;
                    });
                    executeTwoCallbacks(promise, callback, callback);
                    return promise;
                };
                LocalForage.prototype.setDriver = function setDriver(drivers, callback, errorCallback) {
                    var self = this;
                    if (!isArray(drivers)) {
                        drivers = [ drivers ];
                    }
                    var supportedDrivers = this._getSupportedDrivers(drivers);
                    function setDriverToConfig() {
                        self._config.driver = self.driver();
                    }
                    function extendSelfWithDriver(driver) {
                        self._extend(driver);
                        setDriverToConfig();
                        self._ready = self._initStorage(self._config);
                        return self._ready;
                    }
                    function initDriver(supportedDrivers) {
                        return function() {
                            var currentDriverIndex = 0;
                            function driverPromiseLoop() {
                                while (currentDriverIndex < supportedDrivers.length) {
                                    var driverName = supportedDrivers[currentDriverIndex];
                                    currentDriverIndex++;
                                    self._dbInfo = null;
                                    self._ready = null;
                                    return self.getDriver(driverName).then(extendSelfWithDriver)["catch"](driverPromiseLoop);
                                }
                                setDriverToConfig();
                                var error = new Error("No available storage method found.");
                                self._driverSet = Promise$1.reject(error);
                                return self._driverSet;
                            }
                            return driverPromiseLoop();
                        };
                    }
                    // There might be a driver initialization in progress
                    // so wait for it to finish in order to avoid a possible
                    // race condition to set _dbInfo
                    var oldDriverSetDone = this._driverSet !== null ? this._driverSet["catch"](function() {
                        return Promise$1.resolve();
                    }) : Promise$1.resolve();
                    this._driverSet = oldDriverSetDone.then(function() {
                        var driverName = supportedDrivers[0];
                        self._dbInfo = null;
                        self._ready = null;
                        return self.getDriver(driverName).then(function(driver) {
                            self._driver = driver._driver;
                            setDriverToConfig();
                            self._wrapLibraryMethodsWithReady();
                            self._initDriver = initDriver(supportedDrivers);
                        });
                    })["catch"](function() {
                        setDriverToConfig();
                        var error = new Error("No available storage method found.");
                        self._driverSet = Promise$1.reject(error);
                        return self._driverSet;
                    });
                    executeTwoCallbacks(this._driverSet, callback, errorCallback);
                    return this._driverSet;
                };
                LocalForage.prototype.supports = function supports(driverName) {
                    return !!driverSupport[driverName];
                };
                LocalForage.prototype._extend = function _extend(libraryMethodsAndProperties) {
                    extend(this, libraryMethodsAndProperties);
                };
                LocalForage.prototype._getSupportedDrivers = function _getSupportedDrivers(drivers) {
                    var supportedDrivers = [];
                    for (var i = 0, len = drivers.length; i < len; i++) {
                        var driverName = drivers[i];
                        if (this.supports(driverName)) {
                            supportedDrivers.push(driverName);
                        }
                    }
                    return supportedDrivers;
                };
                LocalForage.prototype._wrapLibraryMethodsWithReady = function _wrapLibraryMethodsWithReady() {
                    // Add a stub for each driver API method that delays the call to the
                    // corresponding driver method until localForage is ready. These stubs
                    // will be replaced by the driver methods as soon as the driver is
                    // loaded, so there is no performance impact.
                    for (var i = 0; i < LibraryMethods.length; i++) {
                        callWhenReady(this, LibraryMethods[i]);
                    }
                };
                LocalForage.prototype.createInstance = function createInstance(options) {
                    return new LocalForage(options);
                };
                return LocalForage;
            }();
            // The actual localForage object that we expose as a module or via a
            // global. It's extended by pulling in one of our other libraries.
            var localforage_js = new LocalForage();
            module.exports = localforage_js;
        }, {
            "3": 3
        } ]
    }, {}, [ 4 ])(4);
});

(function(global, factory) {
    typeof exports === "object" && typeof module !== "undefined" ? factory(exports, require("localforage")) : typeof define === "function" && define.amd ? define([ "exports", "localforage" ], factory) : factory(global.localforageGetItems = global.localforageGetItems || {}, global.localforage);
})(this, function(exports, localforage) {
    "use strict";
    localforage = "default" in localforage ? localforage["default"] : localforage;
    function getSerializerPromise(localForageInstance) {
        if (getSerializerPromise.result) {
            return getSerializerPromise.result;
        }
        if (!localForageInstance || typeof localForageInstance.getSerializer !== "function") {
            return Promise.reject(new Error("localforage.getSerializer() was not available! " + "localforage v1.4+ is required!"));
        }
        getSerializerPromise.result = localForageInstance.getSerializer();
        return getSerializerPromise.result;
    }
    function executeCallback(promise, callback) {
        if (callback) {
            promise.then(function(result) {
                callback(null, result);
            }, function(error) {
                callback(error);
            });
        }
        return promise;
    }
    function getItemKeyValue(key, callback) {
        var localforageInstance = this;
        var promise = localforageInstance.getItem(key).then(function(value) {
            return {
                key: key,
                value: value
            };
        });
        executeCallback(promise, callback);
        return promise;
    }
    function getItemsGeneric(keys) {
        var localforageInstance = this;
        var promise = new Promise(function(resolve, reject) {
            var itemPromises = [];
            for (var i = 0, len = keys.length; i < len; i++) {
                itemPromises.push(getItemKeyValue.call(localforageInstance, keys[i]));
            }
            Promise.all(itemPromises).then(function(keyValuePairs) {
                var result = {};
                for (var i = 0, len = keyValuePairs.length; i < len; i++) {
                    var keyValuePair = keyValuePairs[i];
                    result[keyValuePair.key] = keyValuePair.value;
                }
                resolve(result);
            }).catch(reject);
        });
        return promise;
    }
    function getAllItemsUsingIterate() {
        var localforageInstance = this;
        var accumulator = {};
        return localforageInstance.iterate(function(value, key) {
            accumulator[key] = value;
        }).then(function() {
            return accumulator;
        });
    }
    function getIDBKeyRange() {
        /* global IDBKeyRange, webkitIDBKeyRange, mozIDBKeyRange */
        if (typeof IDBKeyRange !== "undefined") {
            return IDBKeyRange;
        }
        if (typeof webkitIDBKeyRange !== "undefined") {
            return webkitIDBKeyRange;
        }
        if (typeof mozIDBKeyRange !== "undefined") {
            return mozIDBKeyRange;
        }
    }
    var idbKeyRange = getIDBKeyRange();
    function getItemsIndexedDB(keys) {
        var localforageInstance = this;
        function comparer(a, b) {
            return a < b ? -1 : a > b ? 1 : 0;
        }
        var promise = new Promise(function(resolve, reject) {
            localforageInstance.ready().then(function() {
                // Thanks https://hacks.mozilla.org/2014/06/breaking-the-borders-of-indexeddb/
                var dbInfo = localforageInstance._dbInfo;
                var store = dbInfo.db.transaction(dbInfo.storeName, "readonly").objectStore(dbInfo.storeName);
                var set = keys.sort(comparer);
                var keyRangeValue = idbKeyRange.bound(keys[0], keys[keys.length - 1], false, false);
                var req = store.openCursor(keyRangeValue);
                var result = {};
                var i = 0;
                req.onsuccess = function() {
                    var cursor = req.result;
                    // event.target.result;
                    if (!cursor) {
                        resolve(result);
                        return;
                    }
                    var key = cursor.key;
                    while (key > set[i]) {
                        // The cursor has passed beyond this key. Check next.
                        i++;
                        if (i === set.length) {
                            // There is no next. Stop searching.
                            resolve(result);
                            return;
                        }
                    }
                    if (key === set[i]) {
                        // The current cursor value should be included and we should continue
                        // a single step in case next item has the same key or possibly our
                        // next key in set.
                        var value = cursor.value;
                        if (value === undefined) {
                            value = null;
                        }
                        result[key] = value;
                        // onfound(cursor.value);
                        cursor.continue();
                    } else {
                        // cursor.key not yet at set[i]. Forward cursor to the next key to hunt for.
                        cursor.continue(set[i]);
                    }
                };
                req.onerror = function() {
                    reject(req.error);
                };
            }).catch(reject);
        });
        return promise;
    }
    function getItemsWebsql(keys) {
        var localforageInstance = this;
        var promise = new Promise(function(resolve, reject) {
            localforageInstance.ready().then(function() {
                return getSerializerPromise(localforageInstance);
            }).then(function(serializer) {
                var dbInfo = localforageInstance._dbInfo;
                dbInfo.db.transaction(function(t) {
                    var queryParts = new Array(keys.length);
                    for (var i = 0, len = keys.length; i < len; i++) {
                        queryParts[i] = "?";
                    }
                    t.executeSql("SELECT * FROM " + dbInfo.storeName + " WHERE (key IN (" + queryParts.join(",") + "))", keys, function(t, results) {
                        var result = {};
                        var rows = results.rows;
                        for (var i = 0, len = rows.length; i < len; i++) {
                            var item = rows.item(i);
                            var value = item.value;
                            // Check to see if this is serialized content we need to
                            // unpack.
                            if (value) {
                                value = serializer.deserialize(value);
                            }
                            result[item.key] = value;
                        }
                        resolve(result);
                    }, function(t, error) {
                        reject(error);
                    });
                });
            }).catch(reject);
        });
        return promise;
    }
    function localforageGetItems(keys, callback) {
        var localforageInstance = this;
        var promise;
        if (!arguments.length || keys === null) {
            promise = getAllItemsUsingIterate.apply(localforageInstance);
        } else {
            var currentDriver = localforageInstance.driver();
            if (currentDriver === localforageInstance.INDEXEDDB) {
                promise = getItemsIndexedDB.apply(localforageInstance, arguments);
            } else if (currentDriver === localforageInstance.WEBSQL) {
                promise = getItemsWebsql.apply(localforageInstance, arguments);
            } else {
                promise = getItemsGeneric.apply(localforageInstance, arguments);
            }
        }
        executeCallback(promise, callback);
        return promise;
    }
    function extendPrototype(localforage) {
        var localforagePrototype = Object.getPrototypeOf(localforage);
        if (localforagePrototype) {
            localforagePrototype.getItems = localforageGetItems;
            localforagePrototype.getItems.indexedDB = function() {
                return getItemsIndexedDB.apply(this, arguments);
            };
            localforagePrototype.getItems.websql = function() {
                return getItemsWebsql.apply(this, arguments);
            };
            localforagePrototype.getItems.generic = function() {
                return getItemsGeneric.apply(this, arguments);
            };
        }
    }
    var extendPrototypeResult = extendPrototype(localforage);
    exports.localforageGetItems = localforageGetItems;
    exports.extendPrototype = extendPrototype;
    exports.extendPrototypeResult = extendPrototypeResult;
    exports.getItemsGeneric = getItemsGeneric;
});

(function(global, factory) {
    typeof exports === "object" && typeof module !== "undefined" ? factory(exports, require("localforage")) : typeof define === "function" && define.amd ? define([ "exports", "localforage" ], factory) : factory(global.localforageSetItems = global.localforageSetItems || {}, global.localforage);
})(this, function(exports, localforage) {
    "use strict";
    localforage = "default" in localforage ? localforage["default"] : localforage;
    function getSerializerPromise(localForageInstance) {
        if (getSerializerPromise.result) {
            return getSerializerPromise.result;
        }
        if (!localForageInstance || typeof localForageInstance.getSerializer !== "function") {
            return Promise.reject(new Error("localforage.getSerializer() was not available! " + "localforage v1.4+ is required!"));
        }
        getSerializerPromise.result = localForageInstance.getSerializer();
        return getSerializerPromise.result;
    }
    function executeCallback(promise, callback) {
        if (callback) {
            promise.then(function(result) {
                callback(null, result);
            }, function(error) {
                callback(error);
            });
        }
    }
    function forEachItem(items, keyFn, valueFn, loopFn) {
        function ensurePropGetterMethod(propFn, defaultPropName) {
            var propName = propFn || defaultPropName;
            if ((!propFn || typeof propFn !== "function") && typeof propName === "string") {
                propFn = function propFn(item) {
                    return item[propName];
                };
            }
            return propFn;
        }
        var result = [];
        // http://stackoverflow.com/questions/4775722/check-if-object-is-array
        if (Object.prototype.toString.call(items) === "[object Array]") {
            keyFn = ensurePropGetterMethod(keyFn, "key");
            valueFn = ensurePropGetterMethod(valueFn, "value");
            for (var i = 0, len = items.length; i < len; i++) {
                var item = items[i];
                result.push(loopFn(keyFn(item), valueFn(item)));
            }
        } else {
            for (var prop in items) {
                if (items.hasOwnProperty(prop)) {
                    result.push(loopFn(prop, items[prop]));
                }
            }
        }
        return result;
    }
    function setItemsIndexedDB(items, keyFn, valueFn, callback) {
        var localforageInstance = this;
        var promise = localforageInstance.ready().then(function() {
            return new Promise(function(resolve, reject) {
                // Inspired from @lu4 PR mozilla/localForage#318
                var dbInfo = localforageInstance._dbInfo;
                var transaction = dbInfo.db.transaction(dbInfo.storeName, "readwrite");
                var store = transaction.objectStore(dbInfo.storeName);
                var lastError;
                transaction.oncomplete = function() {
                    resolve(items);
                };
                transaction.onabort = transaction.onerror = function(event) {
                    reject(lastError || event.target);
                };
                function requestOnError(evt) {
                    var request = evt.target || this;
                    lastError = request.error || request.transaction.error;
                    reject(lastError);
                }
                forEachItem(items, keyFn, valueFn, function(key, value) {
                    // The reason we don't _save_ null is because IE 10 does
                    // not support saving the `null` type in IndexedDB. How
                    // ironic, given the bug below!
                    // See: https://github.com/mozilla/localForage/issues/161
                    if (value === null) {
                        value = undefined;
                    }
                    var request = store.put(value, key);
                    request.onerror = requestOnError;
                });
            });
        });
        executeCallback(promise, callback);
        return promise;
    }
    function setItemsWebsql(items, keyFn, valueFn, callback) {
        var localforageInstance = this;
        var promise = new Promise(function(resolve, reject) {
            localforageInstance.ready().then(function() {
                return getSerializerPromise(localforageInstance);
            }).then(function(serializer) {
                // Inspired from @lu4 PR mozilla/localForage#318
                var dbInfo = localforageInstance._dbInfo;
                dbInfo.db.transaction(function(t) {
                    var query = "INSERT OR REPLACE INTO " + dbInfo.storeName + " (key, value) VALUES (?, ?)";
                    var itemPromises = forEachItem(items, keyFn, valueFn, function(key, value) {
                        return new Promise(function(resolve, reject) {
                            serializer.serialize(value, function(value, error) {
                                if (error) {
                                    reject(error);
                                } else {
                                    t.executeSql(query, [ key, value ], function() {
                                        resolve();
                                    }, function(t, error) {
                                        reject(error);
                                    });
                                }
                            });
                        });
                    });
                    Promise.all(itemPromises).then(function() {
                        resolve(items);
                    }, reject);
                }, function(sqlError) {
                    reject(sqlError);
                });
            }).catch(reject);
        });
        executeCallback(promise, callback);
        return promise;
    }
    function setItemsGeneric(items, keyFn, valueFn, callback) {
        var localforageInstance = this;
        var itemPromises = forEachItem(items, keyFn, valueFn, function(key, value) {
            return localforageInstance.setItem(key, value);
        });
        var promise = Promise.all(itemPromises);
        executeCallback(promise, callback);
        return promise;
    }
    function localforageSetItems(items, keyFn, valueFn, callback) {
        var localforageInstance = this;
        var currentDriver = localforageInstance.driver();
        if (currentDriver === localforageInstance.INDEXEDDB) {
            return setItemsIndexedDB.call(localforageInstance, items, keyFn, valueFn, callback);
        } else if (currentDriver === localforageInstance.WEBSQL) {
            return setItemsWebsql.call(localforageInstance, items, keyFn, valueFn, callback);
        } else {
            return setItemsGeneric.call(localforageInstance, items, keyFn, valueFn, callback);
        }
    }
    function extendPrototype(localforage) {
        var localforagePrototype = Object.getPrototypeOf(localforage);
        if (localforagePrototype) {
            localforagePrototype.setItems = localforageSetItems;
            localforagePrototype.setItems.indexedDB = function() {
                return setItemsIndexedDB.apply(this, arguments);
            };
            localforagePrototype.setItems.websql = function() {
                return setItemsWebsql.apply(this, arguments);
            };
            localforagePrototype.setItems.generic = function() {
                return setItemsGeneric.apply(this, arguments);
            };
        }
    }
    var extendPrototypeResult = extendPrototype(localforage);
    exports.setItemsGeneric = setItemsGeneric;
    exports.localforageSetItems = localforageSetItems;
    exports.extendPrototype = extendPrototype;
    exports.extendPrototypeResult = extendPrototypeResult;
});

(function(global, factory) {
    typeof exports === "object" && typeof module !== "undefined" ? factory(exports, require("localforage")) : typeof define === "function" && define.amd ? define([ "exports", "localforage" ], factory) : factory(global.localforageRemoveItems = global.localforageRemoveItems || {}, global.localforage);
})(this, function(exports, localforage) {
    "use strict";
    localforage = "default" in localforage ? localforage["default"] : localforage;
    function executeCallback(promise, callback) {
        if (callback) {
            promise.then(function(result) {
                callback(null, result);
            }, function(error) {
                callback(error);
            });
        }
        return promise;
    }
    function removeItemsGeneric(keys, callback) {
        var localforageInstance = this;
        var itemPromises = [];
        for (var i = 0, len = keys.length; i < len; i++) {
            var key = keys[i];
            itemPromises.push(localforageInstance.removeItem(key));
        }
        var promise = Promise.all(itemPromises);
        executeCallback(promise, callback);
        return promise;
    }
    function removeItemsIndexedDB(keys, callback) {
        var localforageInstance = this;
        var promise = localforageInstance.ready().then(function() {
            return new Promise(function(resolve, reject) {
                var dbInfo = localforageInstance._dbInfo;
                var transaction = dbInfo.db.transaction(dbInfo.storeName, "readwrite");
                var store = transaction.objectStore(dbInfo.storeName);
                var firstError;
                transaction.oncomplete = function() {
                    resolve();
                };
                transaction.onabort = transaction.onerror = function() {
                    if (!firstError) {
                        reject(transaction.error || "Unknown error");
                    }
                };
                function requestOnError(evt) {
                    var request = evt.target || this;
                    if (!firstError) {
                        firstError = request.error || request.transaction.error;
                        reject(firstError);
                    }
                }
                for (var i = 0, len = keys.length; i < len; i++) {
                    var key = keys[i];
                    if (typeof key !== "string") {
                        console.warn(key + " used as a key, but it is not a string.");
                        key = String(key);
                    }
                    var request = store.delete(key);
                    request.onerror = requestOnError;
                }
            });
        });
        executeCallback(promise, callback);
        return promise;
    }
    function executeSqlAsync(transaction, sql, parameters) {
        return new Promise(function(resolve, reject) {
            transaction.executeSql(sql, parameters, function() {
                resolve();
            }, function(t, error) {
                reject(error);
            });
        });
    }
    function removeItemsWebsql(keys, callback) {
        var localforageInstance = this;
        var promise = localforageInstance.ready().then(function() {
            return new Promise(function(resolve, reject) {
                var dbInfo = localforageInstance._dbInfo;
                dbInfo.db.transaction(function(t) {
                    var storeName = dbInfo.storeName;
                    var itemPromises = [];
                    for (var i = 0, len = keys.length; i < len; i++) {
                        var key = keys[i];
                        if (typeof key !== "string") {
                            console.warn(key + " used as a key, but it is not a string.");
                            key = String(key);
                        }
                        itemPromises.push(executeSqlAsync(t, "DELETE FROM " + storeName + " WHERE key = ?", [ key ]));
                    }
                    Promise.all(itemPromises).then(resolve, reject);
                }, function(sqlError) {
                    reject(sqlError);
                });
            });
        });
        executeCallback(promise, callback);
        return promise;
    }
    function localforageRemoveItems() {
        var localforageInstance = this;
        var currentDriver = localforageInstance.driver();
        if (currentDriver === localforageInstance.INDEXEDDB) {
            return removeItemsIndexedDB.apply(localforageInstance, arguments);
        } else if (currentDriver === localforageInstance.WEBSQL) {
            return removeItemsWebsql.apply(localforageInstance, arguments);
        } else {
            return removeItemsGeneric.apply(localforageInstance, arguments);
        }
    }
    function extendPrototype(localforage) {
        var localforagePrototype = Object.getPrototypeOf(localforage);
        if (localforagePrototype) {
            localforagePrototype.removeItems = localforageRemoveItems;
            localforagePrototype.removeItems.indexedDB = function() {
                return removeItemsIndexedDB.apply(this, arguments);
            };
            localforagePrototype.removeItems.websql = function() {
                return removeItemsWebsql.apply(this, arguments);
            };
            localforagePrototype.removeItems.generic = function() {
                return removeItemsGeneric.apply(this, arguments);
            };
        }
    }
    var extendPrototypeResult = extendPrototype(localforage);
    exports.localforageRemoveItems = localforageRemoveItems;
    exports.extendPrototype = extendPrototype;
    exports.extendPrototypeResult = extendPrototypeResult;
    exports.removeItemsGeneric = removeItemsGeneric;
});