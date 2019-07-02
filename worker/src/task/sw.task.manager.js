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
//import {workerize} from "../task/task.worker";
import {
    serialize
} from "./task.serialize.js";
import {
    script
} from "./task.script.js";

const undef = null;

/*
function nextRetry(n, max = 1000 * 60 * 60) {

    // 1 hour
    return Math.min(max, 1 / 2 * (2 ** n - 1));
}
*/

export class TaskManager {

    /**
     *
     * @param queueName
     * @returns {Promise<void>}
     */
    async run(queueName = "{SYNC_API_TAG}") {

        const db = await this.getDB();
        const tasks = await db.getAll();

        for (const task of tasks) {

            console.log({
                task,
                'task.queueName == queueName': task.queueName == queueName
            });

            if (task.queueName == queueName) {

                const fn = script(task.data);

                if (task.isAsync) {

                    await fn();
                } else fn();
            }
        }
    }

    /**
     * @returns {Promise<DBType>}
     */
    async getDB() {

        if (this.db == undef) {

            this.db = await DB('gzip_sw_worker_sync_tasks', 'id');
        }

        return this.db;
    }

    /**
     *
     * @param {function} task
     * @param {string} queueName
     * @returns {object}
     */
    async register(task, queueName = "{SYNC_API_TAG}") {

        const data = serialize(task);
        const db = await this.getDB();
        return await db.put(

            {
                id: hashCode(JSON.stringify(data)),
                queueName,
                data
            }
        );
    }

    async unregister(serialized) {

        const db = await this.getDB();

        await db.delete(serialized.id)
    }
}