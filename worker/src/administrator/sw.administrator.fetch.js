/**
 *
 * runtime configuration settings. placeholders are replaced when the service worker settings are saved from the plugin settings page.
 *
 * @package     GZip Plugin
 * @copyright   Copyright (C) 2005 - 2018 Thierry Bela.
 *
 * dual licensed
 *
 * @license     LGPL v3
 * @license     MIT License
 */

// @ts-check
/* eslint wrap-iife: 0 */

"use strict;";

// do not cache administrator content -> this can be done in the plugin settings / joomla administrator

self.addEventListener("fetch", (event) => {
	event.respondWith(fetch(event.request));
});