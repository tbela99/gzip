/**
 *
 * @package     GZip Plugin
 * @copyright   Copyright (C) 2005 - 2018 Thierry Bela.
 *
 * dual licensed
 *
 * @license     LGPL v3
 * @license     MIT License
 */
import {
    Event
} from "./lib.event.js";

Event.addPseudo("once", function (event) {
    event.cb = function () {
        const context = this;

        const result = event.fn.apply(context, arguments);
        context.off(event.name, event.fn);

        return result;
    };
});
/*
    addPseudo('times', function (event) {

        let context = this, times = event.parsed[4] == undef && 1 || +event.parsed[4];

        event.cb = function () {

            event.fn.apply(context, arguments);

            if(--times <= 0) {

                context.off(event.name, event.fn);
            }
        }

    }).*/