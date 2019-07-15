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

if ('serviceWorker' in navigator) {

  if ('SyncManager' in window) {

    navigator.serviceWorker.ready.then(function (reg) {

      return reg.sync.getTags().then(function (tags) {

        if (!tags.includes("{SYNC_API_TAG}")) {
          reg.sync.register("{SYNC_API_TAG}");
        }
      })
    }).
    catch(function (error) {
      // system was unable to register for a sync,
      // this could be an OS-level restriction
      console.error('cannot setup native sync api, using fallback 😭', error);
      new Worker("{scope}sync-fallback{debug}.js");
    });

  } else {

    // serviceworker/sync not supported, use a worker instead
    console.info('background sync api not supported, using fallback 😭');
    new Worker("{scope}sync-fallback{debug}.js");
  }
}