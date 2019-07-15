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

export function sprintf(string) {

	let index = -1;
	let value;
	const args = [].slice.apply(arguments).slice(1);

	return string.replace(/%([s%])/g, function (all, modifier) {

		if (modifier == '%') {

			return modifier;
		}

		value = args[++index];

		switch (modifier) {

			case 's':

				return value == null ? '' : value;
		}
	});
}

export function capitalize(string) {

	return string[0].toUpperCase() + string.slice(1);
}

export function ellipsis (string, max = 30, end = 15, fill = '...') {

	if(string.length > max) return string.slice(0, max - end - fill.length + 1) + fill + string.slice(string.length - end + 1);

	return string
}
