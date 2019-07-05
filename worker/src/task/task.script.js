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

/**
 * revive a class or a function
 * @param {object|function} task
 * @returns {object|function}
 */
export function script(serialized) {

    return eval('(function () { return ' + serialized.body + '})()');
}