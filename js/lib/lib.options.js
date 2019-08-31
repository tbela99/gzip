// @ts-check
/* eslint wrap-iife: 0 */
/* global LIB */

/**
 * async css loader
 * @package     GZip Plugin
 * @copyright   Copyright (C) 2005 - 2018 Thierry Bela.
 *
 * dual licensed
 *
 * @license     LGPL v3
 * @license     MIT License
 */

import {
    //   LIB,
    undef
} from "./lib.js";

import {
    merge
} from "./lib.utils.js"

export const Options = {

    options: {},
    setOptions: function (options) {

        let key, option, match;
        const self = this,
            hasEvent = typeof self.on == 'function';

        if (hasEvent) {

            for (key in options) {

                option = options[key];

                if (typeof option == 'function') {

                    match = key.match(/^on(.*)$/);

                    if (match != undef) {

                        self.on(match[1], option);
                        delete options[key];
                        continue;
                    }
                }

                self.options[key] = (typeof option == 'object') && option != undef ? merge(true, option instanceof Array ? [] : {}, option) : option;
            }
        } else {

            merge(true, self.options, options);
        }
    }
}