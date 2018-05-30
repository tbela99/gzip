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
/* global CACHE_NAME */

/**
 * receive mmessage from the client. message handler must be have the suffix 'action'
 * data object passed to postMessage must have an action property used to route the message
 */
self.addEventListener("message", (event) => {
	// delete old app owned caches
	SW.resolve((event.data.action || "default") + "action", event.data);
});
