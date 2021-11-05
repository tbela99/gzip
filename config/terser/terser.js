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

/*
 const filename = "example.js";
const source = fs.readFileSync(filename, "utf8");

// Load and compile file normally, but skip code generation.
const { ast } = babel.transformSync(source, { filename, ast: true, code: false });

// Minify the file in a second pass and generate the output code here.
const { code, map } = babel.transformFromAstSync(ast, source, {
  filename,
  presets: ["minify"],
  babelrc: false,
  configFile: false,
});
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

      if (result.error) {

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