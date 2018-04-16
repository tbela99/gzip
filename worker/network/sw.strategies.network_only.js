// @ts-check
/* eslint wrap-iife: 0 */
/* global SW */

/**
 *
 * @package     GZip Plugin
 * @subpackage  System.Gzip *
 * @copyright   Copyright (C) 2005 - 2018 Thierry Bela.
 *
 * dual licensed
 *
 * @license     LGPL v3
 * @license     MIT License
 */

// or simply don't call event.respondWith, which will result in default browser behaviour
SW.strategies.add("no", (event) => fetch(event.request));
