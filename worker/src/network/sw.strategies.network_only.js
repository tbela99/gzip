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

// or simply don't call event.respondWith, which will result in default browser behaviour
export async function networkOnly(event) {
	return fetch(event.request);
}
