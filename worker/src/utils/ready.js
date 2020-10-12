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


'use strict;';

const queue = [];
let fired = document.readyState != 'loading';

function domReady() {

	document.removeEventListener('DOMContentLoaded', domReady);
	document.removeEventListener('readystatechange', readystatechange);
	fired = true;

	while (queue.length > 0) {

		requestAnimationFrame(queue.shift());
	}
}

function readystatechange() {

	switch (document.readyState) {

		case 'loading':
			break;

		case 'interactive':
		default:

			domReady();
			break;
	}
}

document.addEventListener('DOMContentLoaded', domReady);
document.addEventListener('readystatechange', readystatechange);

export function ready(cb) {

	if (fired) {

		while (queue.length > 0) {

			requestAnimationFrame(queue.shift());
		}

		cb();
	} else {

		queue.push(cb);
	}
}