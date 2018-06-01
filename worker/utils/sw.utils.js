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

!(function() {
	"use strict;";

	const Utils = {
		implement(target) {
			const proto = target.prototype,
				args = [].slice.call(arguments, 1);
			let i, source, key;

			function makefunc(fn, previous, parent) {
				return function() {
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
						proto[key] = makefunc(
							source,
							target[key],
							proto[key]
						);

						break;

					case "object":
						proto[key] = merge(
							true,
							Array.isArray(source) ? [] : {},
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
						fn.apply(this, [k, key[k]].concat(args));
					}
				} else {
					fn.apply(this, arguments);
				}

				return this;
			};
		},
		getAllPropertiesName(object) {
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
		},
		getOwnPropertyDescriptorNames(object) {
			let properties = Object.keys(
				Object.getOwnPropertyDescriptors(object)
			);
			let current = Object.getPrototypeOf(object);
			while (current) {
				properties = properties.concat(
					Object.keys(Object.getOwnPropertyDescriptors(current))
				);

				current = Object.getPrototypeOf(current);
			}

			return properties;
		},
		getObjectHash(object) {
			return hashCode(getObjectHashString(object)).toString(16);
		}
	};

	function getObjectHashString(object) {
		let toString = "",
			property,
			value,
			key,
			i = 0,
			j;

		if (
			(!object && typeof object == "object") ||
			typeof object == "string"
		) {
			toString =
				"" + (object == "string" ? JSON.stringify(object) : object);
		} else {
			const properties = Utils.getOwnPropertyDescriptorNames(object);

			for (; i < properties.length; i++) {
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

							/* eslint no-loop-func: 0 */
							value.forEach(
								(value, key) =>
									(toString +=
										key +
										":" +
										getObjectHashString(value) +
										",")
							);

							if (toString[toString.length - 1] == ",") {
								toString = toString.substr(
									0,
									toString.length - 2
								);
							}

							toString += "}";
						} else {
							toString += "[";

							for (key of value) {
								toString += getObjectHashString(key) + ",";
							}

							if (toString[toString.length - 1] == ",") {
								toString = toString.substr(
									0,
									toString.length - 2
								);
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
		let hash = 0,
			char,
			i;

		if (string.length == 0) {
			return hash;
		}

		for (i = 0; i < string.length; i++) {
			char = string.charCodeAt(i);

			hash = (hash << 5) - hash + char;

			hash = hash & hash; // Convert to 32bit integer
		}

		return hash;
	}

	function merge(target) {
		const args = [].slice.call(arguments, 1);
		let deep = typeof target == "boolean",
			i,
			source,
			prop,
			value;

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
						target[prop] = merge(
							deep,
							typeof target[prop] == "object" &&
								target[prop] != undef
								? target[prop]
								: Array.isArray(value)
									? []
									: {},
							//
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

	function reset(object) {
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
				Array.isArray(object[name]) ? [] : {},
				reset(object[name])
			);
		}

		return object;
	}

	SW.Utils = Utils;
})();
