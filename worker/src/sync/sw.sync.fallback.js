import {
    SyncManager
} from "./sw.sync.js";
import {expo} from "../utils/sw.backoff.js";
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
// - 0
// - 1 minute
// - 2 minutes
// - 4 minutes
// - 8 minutes
// - 16 minutes
// - 32 minutes
// - 60 minutes ...
const nextRetry = expo();

async function replay() {

    await manager.replay("{SYNC_API_TAG}");

    setTimeout(replay, nextRetry(++timeout));
}

setTimeout(replay, nextRetry(timeout));