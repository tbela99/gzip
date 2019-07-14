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

const scheduler = expo();
let thick = 0;

async function clean() {

	await cleanup();

	setTimeout(clean, scheduler(++thick));
}

setTimeout(clean, scheduler(thick));