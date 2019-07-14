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

	self.addEventListener('sync', async (event) => {

		event.waitUntil(cleanup());
	});
}