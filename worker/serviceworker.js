// @ts-check
/* eslint wrap-iife: 0 */
/* main service worker file */
/* global */
// validator https://www.pwabuilder.com/
// pwa app image generator http://appimagegenerator-pre.azurewebsites.net/

"use strict;";
"{IMPORT_SCRIPTS}";

const SW = Object.create(null);
const CACHE_NAME = "{CACHE_NAME}";
const scope = "{scope}";
// const defaultStrategy = "{defaultStrategy}";

let undef; //

console.log(self);
