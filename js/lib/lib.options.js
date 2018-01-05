// @ts-check
/* eslint wrap-iife: 0 */
/* global LIB */
!function (LIB, undef) {
	
    'use strict;';

    const Utils = LIB.Utils;

    LIB.Options = {

        options: {},
        setOptions: function (options) {

            let key, option, match;
            const self = this, hasEvent = typeof self.on == 'function';

            if(hasEvent) {

                for(key in options) {

                    option = options[key];

                    if(typeof option == 'function') {

                        match = key.match(/^on(.*)$/);

                        if(match != undef) {

                            self.on(match[1], option);
                            delete options[key];
                            continue;
                        }
                    }

                    self.options[key] = (typeof option == 'object') && option != undef ? Utils.merge(true, option instanceof Array ? [] : {}, option) : option;
                }
            }

            else {

                Utils.merge(true, self.options, options);
            }
        }
    }
	
}(LIB);