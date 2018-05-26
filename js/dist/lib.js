/* do not edit this file! */
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
var LIB = LIB || Object.create(null);

// @ts-check
/* global LIB, document */
/* eslint wrap-iife: 0 */
/**
 * async css loader
 * @package     GZip Plugin
 * @copyright   Copyright (C) 2005 - 2018 Thierry Bela.
 *
 * dual licensed
 *
 * @license     LGPL v3
 * @license     MIT License
 */
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
!function(LIB, window, undef) {
    "use strict;";
    const requestAnimationFrame = window.requestAnimationFrame || window.webkitRequestAnimationFrame;
    const cancelAnimationFrame = window.cancelAnimationFrame || window.webkitCancelAnimationFrame;
    const Utils = {
        pauseRAF: function(fn) {
            let raf;
            return function() {
                const context = this, args = arguments;
                if (raf != undef) {
                    cancelAnimationFrame(raf);
                    raf = undef;
                }
                raf = requestAnimationFrame(function() {
                    raf = undef;
                    fn.apply(context, args);
                });
            };
        },
        throttleRAF: function(fn) {
            let raf;
            return function() {
                const context = this, args = arguments;
                if (raf == undef) {
                    raf = requestAnimationFrame(function() {
                        raf = undef;
                        fn.apply(context, args);
                    });
                }
            };
        },
        pause: function(fn, delay) {
            let timeout;
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
            let time;
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
                    const args = Array.prototype.slice.call(arguments, 1);
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
        const args = Array.prototype.slice.call(arguments, 1);
        let deep = typeof target == "boolean", i, source, prop, value;
        if (deep) {
            deep = target;
            target = args.shift();
        }
        for (i = 0; i < args.length; i++) {
            source = args[i];
            for (prop in source) {
                value = source[prop];
                switch (typeof value) {
                  case "object":
                    if (value == undef || !deep) {
                        target[prop] = value;
                    } else {
                        target[prop] = merge(deep, typeof source[prop] == "object" && source[prop] != undef ? source[prop] : value instanceof Array ? [] : {}, value);
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
            object[name] = merge(true, object[name] instanceof Array ? [] : {}, reset(object[name]));
        }
        return object;
    }
    LIB.Utils = Utils;
}(LIB, window);

// @ts-check
/* eslint wrap-iife: 0 */
/* global LIB */
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
            const original = name;
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
        event.cb = function() {
            const context = this;
            const result = event.fn.apply(context, arguments);
            context.off(event.name, event.fn);
            return result;
        };
    })
    /*
        addPseudo('times', function (event) {

            let context = this, times = event.parsed[4] == undef && 1 || +event.parsed[4];

            event.cb = function () {

                event.fn.apply(context, arguments);

                if(--times <= 0) {

                    context.off(event.name, event.fn);
                }
            }

        }).*/ .addPseudo("pause", function(event) {
        event.cb = Utils.pause(event.fn, event.parsed[4] == undef && 250 || +event.parsed[4]);
    }).addPseudo("throttle", function(event) {
        event.cb = Utils.throttle(event.fn, event.parsed[4] == undef && 250 || +event.parsed[4]);
    });
    LIB.Event = Event;
}(LIB);

// @ts-check
/* eslint wrap-iife: 0 */
/* global LIB */
/**
 * async css loader
 * @package     GZip Plugin
 * @copyright   Copyright (C) 2005 - 2018 Thierry Bela.
 *
 * dual licensed
 *
 * @license     LGPL v3
 * @license     MIT License
 */
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