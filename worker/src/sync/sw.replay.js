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
class CloneRequest {

    constructor (data) {

        this._data = data;
    }

    /**
     * 
     * @return CloneRequest 
     */
    clone () {

        const data = Object.assign({}, this._data);

        data.headers = Object.assign({}, data.headers);

        if (data.body != null) {

            data.body = data.body.slice()
        }

        return new CloneRequest(data);
    }
}

export {CloneRequest};
