// @ts-check
/**
 * Rollup transform settings
 * @package     GZip Plugin
 * @copyright   Copyright (C) 2005 - 2018 Thierry Bela.
 *
 * dual licensed
 *
 * @license     LGPL v3
 * @license     MIT License
 */

export const libReady = {
  input: "js/lib/lib.ready.js",
  output: {
    name: "LIB",
    file: "js/dist/lib.ready.js",
    format: "iife"
  }
};

export const libImages = {
  input: "js/lib/index.images.js",
  output: {
    name: "LIB",
    file: "js/dist/lib.images.js",
    format: "iife"
  }
};

export const serviceworker = {
  input: "worker/src/index.js",
  output: {
    file: "worker/dist/serviceworker.js",
    format: "iife"
  }
};

export const serviceworkerAdmin = {
  input: "worker/src/administrator/index.js",
  output: {
    file: "worker/dist/serviceworker.administrator.js",
    format: "iife"
  }
};

export const browserPrefetch = {
  input: "worker/src/prefetch/prefetch.js",
  output: {
    name: "prefetch",
    file: "worker/dist/browser.prefetch.js",
    format: "iife"
  }
};

export const sync = {
  input: "worker/src/sync/sw.sync.fallback.js",
  output: {
    file: "worker/dist/sync.fallback.js",
    format: "iife"
  }
};