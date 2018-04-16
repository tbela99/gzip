// @ts-check
/* global SW */
/* eslint wrap-iife: 0 */

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

// If a match isn't found in the cache, the response
// will look like a connection error);
SW.strategies.add("co", (event) => caches.match(event.request));
