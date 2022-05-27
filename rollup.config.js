(function () {
  'use strict';

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

  const libReady = {
    input: "js/lib/lib.ready.js",
    output: {
      name: "LIB",
      file: "js/dist/lib.ready.js",
      format: "iife"
    }
  };

  const libImages = {
    input: "js/lib/index.images.js",
    output: {
      name: "LIB",
      file: "js/dist/lib.images.js",
      format: "iife"
    }
  };

  const critical = {
    input: "./worker/src/critical/critical.js",
    output: {

      name: 'critical',
      file:"./worker/dist/critical.js",
      format: "iife"
    }
  };

  const criticalExtract = {
    input: "./worker/src/critical/extract.js",
    output: {

      file:"./worker/dist/critical-extract.js",
      format: "iife"
    }
  };

  const serviceworker = {
    input: "worker/src/index.js",
    output: {
      file: "worker/dist/serviceworker.js",
      format: "iife"
    }
  };

  const oneSignal = {
    input: "./worker/src/onesignal/onesignal.js",
    output: {
      file: "./worker/dist/onesignal.js",
      format: "iife"
    }
  };

  const serviceworkerAdmin = {
    input: "worker/src/administrator/index.js",
    output: {
      file: "worker/dist/serviceworker.administrator.js",
      format: "iife"
    }
  };

  const browserPrefetch = {
    input: "worker/src/prefetch/prefetch.js",
    output: {
      name: "prefetch",
      file: "worker/dist/browser.prefetch.js",
      format: "iife"
    }
  };

  const sync = {
    input: "worker/src/sync/sw.sync.fallback.js",
    output: {
      file: "worker/dist/sync.fallback.js",
      format: "iife"
    }
  };

  var config = /*#__PURE__*/Object.freeze({
    __proto__: null,
    libReady: libReady,
    libImages: libImages,
    critical: critical,
    criticalExtract: criticalExtract,
    serviceworker: serviceworker,
    oneSignal: oneSignal,
    serviceworkerAdmin: serviceworkerAdmin,
    browserPrefetch: browserPrefetch,
    sync: sync
  });

  // @ts-check

  const rollup = require("rollup");

  for (let name in config) {
    (async function (config, name) {
      try {
        // create a bundle      
        console.log('build ' + name.replace(/[A-Z]/g, function (all) { return '.' + all.toLocaleLowerCase()}) + '.js' + ' ...');

        const bundle = await rollup.rollup(config);

        // generate code
        await bundle.generate(config.output);

        // or write the bundle to disk
        await bundle.write(config.output);
      } catch (error) {
        console.log({
          name,
          error
        });

        throw error;
      }
    })(config[name], name);
  }

}());
