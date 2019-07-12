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

/**
 *
 * Exponentially increase the delay until it reaches max hours. It will no longer increase from that point.
 * - 0
 * - 1 minute
 * - 2 minutes
 * - 4 minutes
 * - 8 minutes
 * - 16 minutes
 * - 32 minutes
 * - max hours ...
 * @param {number} max
 * @returns {function(number): number}
 */
export function expo(max = 1) {

	max *= 3600;

	return function (n) {

		// 1 hour max
		return 1000 * Math.min(max, 1 / 2 * (2 ** n - 1));
	}
}

/**
 * return the same delay in seconds
 *
 * @param {number} n
 * @returns {function(): number}
 */
export function periodic(n) {

	n *= 1000;

	return function () {

		return n;
	}
}