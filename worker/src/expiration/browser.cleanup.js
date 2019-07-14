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
	cleanup
} from "./sw.cleanup.js";
import {
	expo
} from "../utils/sw.backoff.js";

/**
 * cleanup using a web worker
 */
(async function () {

	const func = await cleanup();
	const scheduler = expo();
	let thick = 0;

	async function clean() {

		await func();

		setTimeout(clean, scheduler(++thick));
	}

	setTimeout(clean, scheduler(thick));
})();