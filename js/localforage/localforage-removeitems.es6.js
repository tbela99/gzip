import localforage from 'localforage';

function executeCallback(promise, callback) {
    if (callback) {
        promise.then(function (result) {
            callback(null, result);
        }, function (error) {
            callback(error);
        });
    }
    return promise;
}

function removeItemsGeneric(keys, callback) {
    var localforageInstance = this;

    var itemPromises = [];
    for (var i = 0, len = keys.length; i < len; i++) {
        var key = keys[i];
        itemPromises.push(localforageInstance.removeItem(key));
    }

    var promise = Promise.all(itemPromises);

    executeCallback(promise, callback);
    return promise;
}

function removeItemsIndexedDB(keys, callback) {
    var localforageInstance = this;
    var promise = localforageInstance.ready().then(function () {
        return new Promise(function (resolve, reject) {
            var dbInfo = localforageInstance._dbInfo;
            var transaction = dbInfo.db.transaction(dbInfo.storeName, 'readwrite');
            var store = transaction.objectStore(dbInfo.storeName);
            var firstError;

            transaction.oncomplete = function () {
                resolve();
            };

            transaction.onabort = transaction.onerror = function () {
                if (!firstError) {
                    reject(transaction.error || 'Unknown error');
                }
            };

            function requestOnError(evt) {
                var request = evt.target || this;
                if (!firstError) {
                    firstError = request.error || request.transaction.error;
                    reject(firstError);
                }
            }

            for (var i = 0, len = keys.length; i < len; i++) {
                var key = keys[i];
                if (typeof key !== 'string') {
                    console.warn(key + ' used as a key, but it is not a string.');
                    key = String(key);
                }
                var request = store.delete(key);
                request.onerror = requestOnError;
            }
        });
    });
    executeCallback(promise, callback);
    return promise;
}

function executeSqlAsync(transaction, sql, parameters) {
    return new Promise(function (resolve, reject) {
        transaction.executeSql(sql, parameters, function () {
            resolve();
        }, function (t, error) {
            reject(error);
        });
    });
}

function removeItemsWebsql(keys, callback) {
    var localforageInstance = this;
    var promise = localforageInstance.ready().then(function () {
        return new Promise(function (resolve, reject) {
            var dbInfo = localforageInstance._dbInfo;
            dbInfo.db.transaction(function (t) {
                var storeName = dbInfo.storeName;

                var itemPromises = [];
                for (var i = 0, len = keys.length; i < len; i++) {
                    var key = keys[i];
                    if (typeof key !== 'string') {
                        console.warn(key + ' used as a key, but it is not a string.');
                        key = String(key);
                    }
                    itemPromises.push(executeSqlAsync(t, 'DELETE FROM ' + storeName + ' WHERE key = ?', [key]));
                }

                Promise.all(itemPromises).then(resolve, reject);
            }, function (sqlError) {
                reject(sqlError);
            });
        });
    });
    executeCallback(promise, callback);
    return promise;
}

function localforageRemoveItems() /*keys, callback*/{
    var localforageInstance = this;
    var currentDriver = localforageInstance.driver();

    if (currentDriver === localforageInstance.INDEXEDDB) {
        return removeItemsIndexedDB.apply(localforageInstance, arguments);
    } else if (currentDriver === localforageInstance.WEBSQL) {
        return removeItemsWebsql.apply(localforageInstance, arguments);
    } else {
        return removeItemsGeneric.apply(localforageInstance, arguments);
    }
}

function extendPrototype(localforage) {
    var localforagePrototype = Object.getPrototypeOf(localforage);
    if (localforagePrototype) {
        localforagePrototype.removeItems = localforageRemoveItems;
        localforagePrototype.removeItems.indexedDB = function () {
            return removeItemsIndexedDB.apply(this, arguments);
        };
        localforagePrototype.removeItems.websql = function () {
            return removeItemsWebsql.apply(this, arguments);
        };
        localforagePrototype.removeItems.generic = function () {
            return removeItemsGeneric.apply(this, arguments);
        };
    }
}

var extendPrototypeResult = extendPrototype(localforage);

export { localforageRemoveItems, extendPrototype, extendPrototypeResult, removeItemsGeneric };