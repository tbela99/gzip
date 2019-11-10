(function () {
  'use strict';

  // @ts-check
  /**
   * lazy image laoder
   * @package     GZip Plugin
   * @copyright   Copyright (C) 2005 - 2018 Thierry Bela.
   *
   * dual licensed
   *
   * @license     LGPL v3
   * @license     MIT License
   */

  const preamble = "/* do not edit this file! */";

  const compress = {
    // ecma: 5,
    // keep_fnames: true,
    passes: 3,
    toplevel: true,
    unsafe_proto: true,
    pure_funcs: ["console.log", "console.info"]
  };

  const mangle = {
    keep_fnames: true
  };

  const output = {
    // output options
    preamble,
    beautify: true,
    //  ecma: 5, // specify one of: 5, 6, 7 or 8
    comments: true
  };

  const minify = {

    ecma: 8, // specify one of: 5, 6, 7 or 8
    warnings: true,
    compress,
    mangle,
    output: {
      // output options
      ...output,
      preamble: '',
      beautify: false,
      //  ecma: 5, // specify one of: 5, 6, 7 or 8
      comments: false
    }
  };

  const imageLoader = {
    input: "./imagesloader.es6",
    output: "./imagesloader.js",
    config: {
      //  ie8: true,
      ecma: 5,
      output,
      warnings: true
    }
  };

  const imageLoaderMin = {
    input: "./imagesloader.js",
    output: "./imagesloader.min.js",
    config: {
      ...minify,
      //  ie8: true,
      ecma: 5 // specify one of: 5, 6, 7 or 8
    }
  };

  var config = /*#__PURE__*/Object.freeze({
    imageLoader: imageLoader,
    imageLoaderMin: imageLoaderMin
  });

  // @ts-check

  const fs = require("fs");
  const UglifyJS = require("uglify-es");

  for (let name in config) {
    (async function (config, name) {
      try {
        // create a bundle
        const result = UglifyJS.minify(
          fs.readFileSync(config.input, "utf8"),
          Object.assign({}, config.config)
        );

        fs.writeFileSync(config.output, result.code);

        console.log({
          name,
          config: JSON.stringify(config.config),
          result: result.code.substring(0, 350) + ' ...'
        });
      } catch (error) {
        console.log({
          name,
          error
        });
      }
    })(config[name], name);
    break;
  }

}());
