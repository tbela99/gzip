/**
 *
 * @package     workerize
 * @copyright   Copyright (C) 2005 - 2019 Thierry Bela.
 *
 * dual licensed
 *
 * @license     LGPL v3
 * @license     MIT License
 */

// @ts-check
import {
    serialize
} from "./serialize.js";
import {
    id
} from "./id.js";

const map = new Map;
const store = new WeakMap;

/**
 * delete the worker instance associated to the class / function
 */
export function dispose() {

    for (let instance of [].slice.apply(arguments)) {

        const worker = store.get(instance);

        if (worker != null) {

            store.delete(instance);
            worker.terminate();
        }
    }

}

/**
 * run the class or function given from a worker context
 * @param {object|function} task
 * @returns {object|function}
 */
export function workerize(task) {

    const serialized = serialize(task);

    const data = [];
    data.push('const Class = (function () { return ' + serialized.body + '})();');

    let runner;

    if (serialized.type == 'class') {

        data.push('let instance');
        data.push('self.onmessage = async function (e) { ');
        data.push(' if (e.data.method == "constructor") {');
        data.push('     instance = new (Class.bind(Class, e.data.args));');
        data.push(' } else {');
        data.push('     try {');
        data.push('         if (Object.getPrototypeOf(instance[e.data.method]).constructor.name === "AsyncFunction") {');
        data.push('         	postMessage({id: e.data.id, data: await instance[e.data.method].apply(instance,e.data.args)});');
        data.push('         } else {')
        data.push('         	postMessage({id: e.data.id, data: instance[e.data.method].apply(instance, e.data.args)});');
        data.push('         } ');
        data.push('     }');
        data.push('     catch (error) {');
        data.push('         console.log({error});');
        data.push('         postMessage({id: e.data.id, type: "error", data: error});');
        data.push('     }');
        data.push(' }');
        data.push('}');

        runner = class {

            constructor() {

                const worker = new Worker(URL.createObjectURL(new Blob([data.join('\n')], {
                    type: 'text/javascript'
                })));

                store.set(this, worker);

                worker.onmessage = function (e) {

                    const data = map.get(e.data.id);

                    if (data != null) {

                        if (data.type == 'error') {

                            // reject
                            data[1](e.data.data);
                        } else {
                            //resolve
                            data[0](e.data.data);
                        }

                        map.delete(e.data.id)
                    }
                }

                function proxy(method) {

                    return async function () {

                        const promiseid = id();
                        const args = [].slice.apply(arguments);

                        return new Promise(function (resolve, reject) {

                            map.set(promiseid, [
                                resolve,
                                reject
                            ])

                            worker.postMessage({
                                id: promiseid,
                                method,
                                args
                            });
                        });
                    }
                }

                const proto = Object.getPrototypeOf(this);

                // all enumerable method
                for (let name of Object.getOwnPropertyNames(task.prototype)) {

                    if (name == 'constructor') {

                        continue;
                    }

                    if (typeof task.prototype[name] == 'function') {

                        proto[name] = proxy(name);
                    }
                }

                worker.postMessage({
                    method: 'constructor',
                    args: [].slice.apply(arguments)
                });
            }
        }

    } else {

        data.push('self.onmessage = async function (e) { ');
        data.push(' try {');
        data.push('     if (Object.getPrototypeOf(Class).constructor.name === "AsyncFunction") {');
        data.push('         postMessage({id: e.data.id, data: await Class.apply(null, e.data.args)});');
        data.push('     }');
        data.push('     else {')
        data.push('         postMessage({id: e.data.id, data: Class.apply(null, e.data.args)});');
        data.push('     }');
        data.push(' }');
        data.push(' catch (error) {');
        data.push('     console.log({error});');
        data.push('     postMessage({id: e.data.id, type: "error", data: error});');
        data.push(' }');
        data.push('}');

        const worker = new Worker(URL.createObjectURL(new Blob([data.join('\n')], {
            type: 'text/javascript'
        })));

        worker.onmessage = function (e) {

            const data = map.get(e.data.id);

            if (data != null) {

                if (data.type == 'error') {

                    // reject
                    data[1](e.data.data);
                } else {
                    //resolve
                    data[0](e.data.data);
                }

                map.delete(e.data.id)
            }
        }

        runner = async function () {

            const args = [].slice.apply(arguments);

            const promiseid = id();

            return new Promise(function (resolve, reject) {

                map.set(promiseid, [
                    resolve,
                    reject
                ])

                worker.postMessage({
                    id: promiseid,
                    args
                });
            })
        }

        store.set(runner, worker);
    }

    return runner;
}