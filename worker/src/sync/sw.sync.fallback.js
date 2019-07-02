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
function nextRetry(n, max = 1000 * 60 * 60) {

    // 1 hour max
    return 1000 * Math.min(max, 1 / 2 * (2 ** n - 1));
}

async function replay() {

    console.log('replay requests ...');
    await manager.replay("{SYNC_API_TAG}");

    setTimeout(replay, nextRetry(++timeout));
}

setTimeout(replay, nextRetry(timeout));