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

if ('serviceWorker' in navigator && 'SyncManager' in window) {
    navigator.serviceWorker.ready.then(function(reg) {
      return reg.sync.register('gzip');
    }).
    catch(function(error) {
      // system was unable to register for a sync,
      // this could be an OS-level restriction
      console.error('cannot setup sync api 😭', error);

    });
  } else {
    // serviceworker/sync not supported
    console.error('sync api not supported 😭');

    // fallback support - maybe someday
  }
  