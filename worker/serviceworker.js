/**
 *
 * main service worker file
 * @package     GZip Plugin
 * @copyright   Copyright (C) 2005 - 2018 Thierry Bela.
 *
 * dual licensed
 *
 * @license     LGPL v3
 * @license     MIT License
 */

// @ts-check

/*  */

// build build-id build-date
/* eslint wrap-iife: 0 */
/* global */
// validator https://www.pwabuilder.com/
// pwa app image generator http://appimagegenerator-pre.azurewebsites.net/

"use strict;";
"{IMPORT_SCRIPTS}";

const undef = null; //

/**
 *
 * @var {SWType}
 */
const SW = Object.create(undef);
const CACHE_NAME = "{CACHE_NAME}";
const CRY = "😭";
const scope = "{scope}";

Object.defineProperty(SW, "app", {value: Object.create(undef)});

Object.defineProperties(SW.app, {
	name: {value: "gzip", enumerable: true},
	route: {value: "{ROUTE}", enumerable: true},
	codeName: {value: "Page Optimizer Plugin", enumerable: true},
	build: {value: "{VERSION}", enumerable: true},
	buildid: {value: "build-id", enumerable: true},
	builddate: {value: "build-date", enumerable: true},
	urls: {value: "{CDN_HOSTS}", enumerable: true},
	homepage: {value: "https://github.com/tbela99/gzip", enumerable: true}
});
// const defaultStrategy = "{defaultStrategy}";

//console.log(self);
