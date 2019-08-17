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
    pause
} from "./lib.utils.js";

Event.addPseudo("pause", function (event) {
    event.cb = pause(
        event.fn,
        (event.parsed[4] == undef && 250) || +event.parsed[4]
    );
})