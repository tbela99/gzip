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

export const isCacheableRequest = (request, response) =>
	response instanceof Response &&
	("cors" == response.type ||
		new URL(request.url, self.origin).origin == self.origin) &&
	request.method == "GET" &&
	response.ok && ["default", "cors", "basic", "navigate"].includes(response.type) &&
	!response.bodyUsed;
