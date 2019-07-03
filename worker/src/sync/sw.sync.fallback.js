import {
    SyncManager
} from "./sw.sync.js";
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

const manager = new SyncManager;
let timeout = 0;

// retry using back off algorithm
// 0 - 1 minute - 2 minutes - 4 minutes - 8 minutes 16 minutes 32 minutes 60 minutes ...
function nextRetry(n, max = 1000 * 60 * 60) {

    // 1 hour max
    return 60000 * Math.min(max, 1 / 2 * (2 ** n - 1));
}

async function replay() {

    console.log('replay requests ...');
    await manager.replay("{SYNC_API_TAG}");

    setTimeout(replay, nextRetry(++timeout));
}

setTimeout(replay, nextRetry(timeout));