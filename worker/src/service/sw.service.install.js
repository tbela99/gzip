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

self.addEventListener("install", event => {
	event.waitUntil(
		(async () => {
			try {
				await SW.resolve("install", event);
			} catch (e) {
				console.error("😭", e);
			}
			return self.skipWaiting();
		})()
	);
});
