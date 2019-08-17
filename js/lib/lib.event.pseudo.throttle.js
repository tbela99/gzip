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
import {
    throttle
} from "./lib.utils.js";

Event.addPseudo("throttle", function (event) {
    event.cb = throttle(
        event.fn,
        (event.parsed[4] == undef && 250) || +event.parsed[4]
    );
});