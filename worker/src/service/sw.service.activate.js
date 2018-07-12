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
import {SW} from "../serviceworker.js";

self.addEventListener("activate", event => {
	// delete old app owned caches
	event.waitUntil(
		(async () => {
			try {
				await SW.resolve("activate", event);
			} catch (e) {
				console.error("😭", e);
			}
			return self.clients.claim();
		})()
	);
});
