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
    SW
} from "../serviceworker.js";
import {
    SyncManager
} from "./sw.sync.js";
import {
    DB
} from "../db/db";
import {
    TaskManager
} from "../task/sw.task.manager.js";

if (SW.app.backgroundSync.enabled) {

    const taskManager = new TaskManager();
    const manager = new SyncManager();

    SW.on({

        /**
         * clear data from previous worker instance
         */
        async install() {

            for (const name of ['gzip_sw_worker_sync_requests', 'gzip_sw_worker_sync_tasks']) {

                const db = await DB(name, 'id');

                if (db != null) {

                    await db.clear();
                }
            }
        }
    });

    SW.routes.on({
        /**
         * 
         * @param {Request} event 
         * @param {Response} response 
         */
        fail(request, response) {

            console.info('failed request detected! trying to schedule background sync');

            const options = SW.app.backgroundSync;

            if (options.length == 0 || options.method.indexOf(request.method) != -1) {

                const location = request.url.replace(self.origin, '');

                if (options.pattern.length == 0 || options.pattern.some(pattern => location.indexOf(pattern) == 0)) {

                    return manager.push(request);
                }
            }
        }
    });

    self.addEventListener("sync",
        /**
         * {SyncEvent} event
         */
        function (event) {

            // tears of joy
            console.info('sync event supported 😭');
            console.info('Sync Tag ' + event.tag);

            event.waitUntil(
                manager.replay(event.tag).then(() => taskManager.run(event.tag)).catch(error => console.error({
                    error
                }))
            );
        });
}