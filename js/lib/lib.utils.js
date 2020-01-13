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
//!(function(LIB, window, undef) {
//"use strict;";

import {
	//	LIB,
	undef
} from "./lib.js"

const requestAnimationFrame =
	window.requestAnimationFrame || window.webkitRequestAnimationFrame;
const cancelAnimationFrame =
	window.cancelAnimationFrame || window.webkitCancelAnimationFrame;

export function pauseRAF(fn) {
	let raf;

	return function () {
		const context = this,
			args = arguments;

		if (raf != undef) {
			cancelAnimationFrame(raf);
			raf = undef;
		}

		raf = requestAnimationFrame(function () {
			raf = undef;
			fn.apply(context, args);
		});
	};
}

export function throttleRAF(fn) {
	let raf;

	return function () {
		const context = this,
			args = arguments;

		if (raf == undef) {
			raf = requestAnimationFrame(function () {
				raf = undef;
				fn.apply(context, args);
			});
		}
	};
}

export function pause(fn, delay) {
	let timeout;

	return function () {
		const context = this,
			args = arguments;

		if (timeout) {
			clearTimeout(timeout);
			timeout = undef;
		}

		timeout = setTimeout(function () {
			timeout = undef;
			fn.apply(context, args);
		}, delay || 250);
	};
}
export function throttle(fn, delay) {
	let time;

	if (delay == undef) {
		delay = 250;
	}

	return function () {
		const now = Date.now();

		if (time == undef || time + delay >= now) {
			time = now;
			fn.apply(this, arguments);
		}
	};
}
export function implement(target) {
	const proto = target.prototype,
		args = Array.prototype.slice.call(arguments, 1);
	let i, source, key;

	function makefunc(fn, previous, parent) {
		return function () {
			const self = this,
				hasPrevious = "previous" in self,
				hasParent = "parent" in self,
				oldPrevious = self.previous,
				oldParent = self.parent;

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
					proto[key] = makefunc(
						source,
						target[key],
						proto[key]
					);

					break;

				case "object":
					proto[key] = merge(
						true,
						source instanceof Array ? [] : {},
						source
					);
					break;

				default:
					proto[key] = source;
					break;
			}
		}
	}

	return target;
}

export function extendArgs(fn) {
	return function (key) {
		if (typeof key == "object") {
			const args = Array.prototype.slice.call(arguments, 1);
			let k;

			for (k in key) {
				fn.apply(this, [k, key[k]].concat(args));
			}
		} else {
			fn.apply(this, arguments);
		}

		return this;
	};
}

export function getAllPropertiesName(object) {
	const properties = [];
	let current = object,
		props,
		prop,
		i;

	do {
		props = Object.getOwnPropertyNames(current);

		for (i = 0; i < props.length; i++) {
			prop = props[i];
			if (properties.indexOf(prop) === -1) {
				properties.push(prop);
			}
		}
	} while ((current = Object.getPrototypeOf(current)));

	return properties;
}

export function merge(target) {
	const args = Array.prototype.slice.call(arguments, 1);
	let deep = typeof target == "boolean",
		i,
		source,
		prop,
		value;

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
						target[prop] = merge(
							deep,
							typeof source[prop] == "object" &&
							source[prop] != undef ?
							source[prop] :
							value instanceof Array ? [] : {},
							value
						);
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

export function reset(object) {
	const properties = Utils.getAllPropertiesName(object);
	let name,
		descriptor,
		i = properties.length;

	while (i && i--) {
		name = properties[i];
		descriptor = Object.getOwnPropertyDescriptor(object, name);

		//
		if (
			object[name] == undef ||
			typeof object[name] != "object" ||
			descriptor == undef ||
			(!("value" in descriptor) ||
				!(descriptor.writable && descriptor.configurable))
		) {
			continue;
		}

		object[name] = merge(
			true,
			object[name] instanceof Array ? [] : {},
			reset(object[name])
		);
	}

	return object;
}