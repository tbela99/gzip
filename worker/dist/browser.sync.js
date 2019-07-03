/* do not edit! */
/**
 * Service worker browser client
 * @package     GZip Plugin
 * @copyright   Copyright (C) 2005 - 2018 Thierry Bela.
 *
 * dual licensed
 *
 * @license     LGPL v3
 * @license     MIT License
 */
// @ts-check
if ("serviceWorker" in navigator && "SyncManager" in window) {
    navigator.serviceWorker.ready.then(function(reg) {
        return reg.sync.getTags().then(function(tags) {
            if (!tags.includes("{SYNC_API_TAG}")) {
                reg.sync.register("{SYNC_API_TAG}");
            }
        });
    }).catch(function(error) {
        // system was unable to register for a sync,
        // this could be an OS-level restriction
        console.error("cannot setup sync api 😭", error);
    });
} else {
    // serviceworker/sync not supported, use a worker instead
    console.info("{fallback} sync api not supported 😭");
    const script = document.createElement("script");
    script.src = "{scope}sync-fallback{debug}.js";
    script.async = true;
    script.defer = true;
    document.body.appendChild(script);
    // fallback support - maybe someday
}
