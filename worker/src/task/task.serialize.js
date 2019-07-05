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

// @ts-check

/**
 * serialize class or function
 * @param {object|function} task
 */
export function serialize(task) {

    const source = task.toString().trim();

    let type = '',
        isAsync = Object.getPrototypeOf(task).constructor.name === 'AsyncFunction',
        body;

    const data = source.match(/^((class)|((async\s+)?function)?)\s*([^{(]*)[({]/);


    type = data[1];
    let name = data[5].trim().replace(/[\s(].*/, '');

    body = type + ' ' + (name === '' ? task.name : name) + source.substring((type + (name === '' ? name : ' ' + name)).length);

    if (name === '') {

        name = task.name;
    }

    if (type === '') {

        type = 'function';
    }

    return {
        type,
        name,
        body,
        isAsync
    }
}