// @ts-check
/**
 * Rollup transform
 * @package     GZip Plugin
 * @copyright   Copyright (C) 2005 - 2018 Thierry Bela.
 *
 * dual licensed
 *
 * @license     LGPL v3
 * @license     MIT License
 */

import * as config from "./rollup.config.js";

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