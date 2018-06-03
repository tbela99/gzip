/**
 *
 * type definitions file
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

/**
 * @typedef SWType
 * @method {callback} SW.resolve
 * @method {callback} SW.on
 * @method {callback} SW.off
 * @property Expiration
 */

/**
 * @typedef {RouteHandler}
 * @property {Router} router
 * @property {RouterOptions} options
 *
 */

/**
 * @typedef {RouterOptions}
 * @property {cacheName} string cache name
 * @property {number} expiration
 *
 */

/**
 *
 * @async
 * @callback routerHandle
 * @param {FetchEvent} event
 */

/**
 * @typedef routerHandleObject
 * @property {object} handler
 * @property {routerHandle} handler.handle
 */

/**
 * @typedef {RegExp|string|URL} routerPath
 */
// force uglifyjs to include this file content
if (false) {
}
