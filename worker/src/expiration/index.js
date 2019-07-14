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

import {
	SW
} from "../serviceworker.js";
import {
	cleanup
} from "./sw.cleanup.js";

if (SW.app.network.limit > 0) {

	//	(async function () {

	//	const func = await cleanup();

	self.addEventListener('sync', async (event) => {

		const callback = await cleanup();

		event.waitUntil(callback());
	});
	//	})();
}