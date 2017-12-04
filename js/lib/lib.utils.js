// @ts-check
/* eslint wrap-iife: 0 */
/* global LIB */
!function (LIB, undef) {
    
    'use strict;';

    const Utils = {
        
        pause: function (fn, delay) {

            let timeout, undef;

            return function () {

                const context = this, args = arguments;

                if(timeout) {

                    clearTimeout(timeout);
                    timeout = undef;
                }

                timeout = setTimeout(function () {

                    timeout = undef;
                    fn.apply(context, args);
                }, delay || 250);
            }
        },
        throttle: function (fn, delay) {

            let time, undef;

            if(delay == undef) {

                delay = 250;
            }

            return function () {

                const now = Date.now();

                if(time == undef || time + delay >= now) {

                    time = now;
                    fn.apply(this, arguments);
                }
            }
        },
        implement: function (target) {

            const proto = target.prototype, args = Array.prototype.slice.call(arguments, 1);
            let i, source, key;

            function makefunc(fn, previous, parent) {

                return function () {

                    const self = this,
                        hasPrevious = 'previous' in self,
                        hasParent = 'parent' in self,
                        oldPrevious = self.previous,
                        oldParent = self.parent;

                    self.previous = previous;
                    self.parent = parent;

                    const result = fn.apply(self, arguments);

                    if(hasPrevious) {

                        self.previous = oldPrevious;
                    }
                    else {

                        delete self.previous;
                    }

                    if(hasParent) {

                        self.parent = oldParent;
                    }
                    else {

                        delete self.parent;
                    }

                    return result;
                }
            }

            for(i = 0; i < args.length; i++) {

                for(key in args[i]) {

                    source = args[i][key];

                    switch(typeof source) {

                        case 'function':

                            proto[key] = makefunc(source, target[key], proto[key]);

                            break;

                        case 'object':

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
        extendArgs: function (fn) {

            return function (key) {

                if(typeof key == 'object') {

                    const args = Array.prototype.slice.apply(arguments, 1);
                    let k;

                    for(k in key) {

                        fn.apply(this, [k, key[k]].concat(args))
                    }
                }

                else {

                    fn.apply(this, arguments);
                }

                return this;
            }
        },
        getAllPropertiesName: function (object) {

            const properties = [];
            let current = object, props, prop, i;

            do {

                props = Object.getOwnPropertyNames(current);
                
                for(i = 0; i < props.length; i++) {

                    prop = props[i];
                    if (properties.indexOf(prop) === -1) {

                        properties.push(prop);
                    }
                }
            }
            
            while((current = Object.getPrototypeOf(current)));

            return properties;
        }
    };

    function merge(target) {

        const deep = target === true, args = Array.prototype.slice.call(arguments, 1);
        let i, source, prop, value;

        if(deep === true) {

            target = args.shift();
        }

        for(i = 0; i < args.length; i++) {

            source = args[i];

            for(prop in source) {

                value = source[prop];

                switch(typeof value) {

                    case 'object':

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

    function reset (object) {

        const properties = Utils.getAllPropertiesName(object);
        let name, descriptor, i = properties.length;

        while(i && i--) {

            name = properties[i];
            descriptor = Object.getOwnPropertyDescriptor(object, name);

            //
            if(object[name] == undef || typeof object[name] != 'object' || descriptor == undef || (!('value' in descriptor) || !(descriptor.writable && descriptor.configurable))) {

                continue;
            }

            object[name] = merge(true, object[name] instanceof Array ? [] : {}, reset(object[name]));
        }

        return object;
    }

    LIB.Utils = Utils;

}(LIB);