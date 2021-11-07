// @ts-check
/**
 * lazy image loader
 * @package     GZip Plugin
 * @copyright   Copyright (C) 2005 - 2018 Thierry Bela.
 *
 * dual licensed
 *
 * @license     LGPL v3
 * @license     MIT License
 */

import * as config from "./terser.config.js";

const fs = require("fs");
const Terser = require("terser");

for (let name in config) {
  (async function (config, name) {
    try {
      // create a bundle
      console.log('build ' + config.input + ' > ' + config.output + ' ...');
      const result = Terser.minify({[config.input]: fs.readFileSync(config.input, "utf8")},
        Object.assign({}, config.config)
      );

      if (result.error || result.code == undefined) {

        console.error('build failed ...');
        console.error(JSON.stringify({result}, null, 1));

      }
      else {

        fs.writeFileSync(config.output, result.code);
      }

    } catch (error) {
      console.error({
        name,
        error
      });
    }
  })(config[name], name);
}