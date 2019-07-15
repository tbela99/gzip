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

export function num2FileSize(size, units) {

	if(size == 0) return 0;

	const s = ['b', 'Kb', 'Mb', 'Gb', 'Tb', 'Pb'], e = Math.floor(Math.log(size) / Math.log(1024));

	return (size/ Math.pow(1024, Math.floor(e))).toFixed(2) + " " + (units && units[e] ? units[e] : s[e]);
}