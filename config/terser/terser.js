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
      console.log('build ' + name.replace(/[A-Z]/g, function (all) { return '.' + all.toLocaleLowerCase()}) + '.js' + ' ...');
      const result = Terser.minify(
        fs.readFileSync(config.input, "utf8"),
        Object.assign({}, config.config)
      );

      fs.writeFileSync(config.output, result.code);

   //   console.log({
   //     name,
    //    config: JSON.stringify(config.config),
    //    result: result.code.substring(0, 350) + ' ...'
    //  });
    } catch (error) {
      console.log({
        name,
        error
      });
    }
  })(config[name], name);
}