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
import {
	getOwnPropertyDescriptorNames
} from "../utils/sw.utils.js";

export function hashCode(string) {
	var hash = 0,
		i, chr;
	if (string.length === 0) return hash;
	for (i = 0; i < string.length; i++) {
		chr = string.charCodeAt(i);
		hash = ((hash << 5) - hash) + chr;
		hash |= 0; // Convert to 32bit integer
	}
	return Number(hash).toString(36);
}

export function id() {

	return Number(Math.random().toString().substring(2)).toString(36)
}

export function getObjectHash(object) {
	let toString = "",
		property,
		value,
		key,
		i = 0,
		j;

	if ((!object && typeof object == "object") || typeof object == "string") {
		toString = "" + (object == "string" ? JSON.stringify(object) : object);
	} else {
		const properties = getOwnPropertyDescriptorNames(object);

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
					toString += getObjectHash(value[j]) + ",";
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
								getObjectHash(value) +
								",")
						);

						if (toString[toString.length - 1] == ",") {
							toString = toString.substr(0, toString.length - 2);
						}

						toString += "}";
					} else {
						toString += "[";

						for (key of value) {
							toString += getObjectHash(key) + ",";
						}

						if (toString[toString.length - 1] == ",") {
							toString = toString.substr(0, toString.length - 2);
						}

						toString += "]";
					}
				} else {
					toString += "{" + getObjectHash(value) + "}";
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