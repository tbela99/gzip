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
import {
    DB
} from "../db/db.js";
import {
    hashCode
} from "../crypto/sw.crypto.js";
import {
    isCacheableRequest
} from "../network/sw.iscacheable.js"

const cacheName = "{CACHE_NAME}";
let undef = null;

const serializableProperties = [
    'method',
    'referrer',
    'referrerPolicy',
    'mode',
    'credentials',
    'cache',
    'redirect',
    'integrity',
    'keepalive',
];
/*
function nextRetry(n, max = 1000 * 60 * 60) {

    // 1 hour
    return Math.min(max, 1 / 2 * (2 ** n - 1));
}
*/

// store and replay syncs

export class SyncManager {

    /**
     * 
     * @param {Request} request 
     */
    async push(request) {

        const data = await this.cloneRequestData(request);
        const db = await this.getDB();

        await db.put({
            id: hashCode(request.url + serializableProperties.map(async name => {

                let value = data[name];

                if (value == undef) {

                    return '';
                }

                if (name == 'headers') {

                    if (value instanceof Headers) {

                        return [...value.values()].filter(value => value != undef).join('');
                    }

                    return Object.values(value).map(value => data[name][value] != undef ? data[name][value] : '').join('');
                }

                if (name == 'body') {

                    return await value.text();
                }

                return value;
            }).join('')),
            //    retry: 0,
            lastRetry: Date.now() + 1000 * 60 * 60 * 24,
            url: request.url,
            request: data
        });

        return this;
    }

    /**
     * 
     * @param {Request} request 
     */
    async cloneRequestData(request) {

        const requestData = {
            headers: {},
        };

        // Set the body if present.
        if (request.method !== 'GET') {
            // Use ArrayBuffer to support non-text request bodies.
            // NOTE: we can't use Blobs because Safari doesn't support storing
            // Blobs in IndexedDB in some cases:
            // https://github.com/dfahlander/Dexie.js/issues/618#issuecomment-398348457
            requestData.body = await request.clone().arrayBuffer();
        }

        // Convert the headers from an iterable to an object.
        for (const [key, value] of request.headers.entries()) {

            requestData.headers[key] = value;
        }

        // Add all other serializable request properties
        for (const prop of serializableProperties) {

            if (request[prop] !== undefined) {

                requestData[prop] = request[prop];
            }
        }

        // If the request's mode is `navigate`, convert it to `same-origin` since
        // navigation requests can't be constructed via script.
        if (requestData.mode === 'navigate') {

            requestData.mode = 'same-origin';
        }

        return requestData;
    }

    /**
     * @returns {Promise<DBType>}
     */
    async getDB() {

        if (this.db == undef) {

            this.db = await DB('gzip_sw_worker_sync_requests', 'id');
        }

        return this.db;
    }

    async replay(tag) {

        console.log({
            tag
        });

        if (tag != "{SYNC_API_TAG}") {

            return
        }

        const db = await this.getDB();
        const requests = await db.getAll();

        if (requests.length > 0) {

            console.info('attempting to sync background requests ...');
        }

        const cache = await caches.open(cacheName);

        for (const data of requests) {

            let remove = false;

            try {

                console.info('attempting to replay background requests: [' + data.request.method + '] ' + data.url);

                const request = new Request(data.url, data.request);

                let response = await cache.match(request);

                remove = response != undef;

                if (!remove) {

                    response = await fetch(request.clone());

                    remove = response != undef && response.ok;

                    if (remove && isCacheableRequest(request, response)) {

                        await cache.put(request, response);
                    }
                }

            } catch (e) {


            }

            if (remove || data.lastRetry <= Date.now()) {

                console.log({
                    remove,
                    expired: data.lastRetry <= Date.now()
                });
                await db.delete(data.id);
            }
        }
    }
}