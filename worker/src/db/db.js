/* LICENSE: MIT LICENSE | https://github.com/msandrini/minimal-indexed-db */
/* global window */

/**
 * @typedef DBType
 * @function count
 * @function getEntry
 * @function getAll
 * @function put
 * @function delete
 * @function flush
 * @function then
 * @function catch
 */

/**
 *
 * @var {DBType} DB
 * */

export async function DB(dbName, key = "id", indexes = []) {
	return new Promise((resolve, reject) => {
		const openDBRequest = indexedDB.open(dbName, 1);
		const storeName = `${dbName}_store`;
		let db;
		const _upgrade = () => {
			db = openDBRequest.result;
			const store = db.createObjectStore(storeName, {
				keyPath: key
			});

			let index;

			for (index of indexes) {
				store.createIndex(index.name, index.key, index.options);
			}
		};
		const _query = (method, readOnly, param = null, index = null) =>
			new Promise((resolveQuery, rejectQuery) => {
				const permission = readOnly ? "readonly" : "readwrite";
				if (db.objectStoreNames.contains(storeName)) {
					const transaction = db.transaction(storeName, permission);
					const store = transaction.objectStore(storeName);
					const isMultiplePut =
						method === "put" &&
						param &&
						typeof param.length !== "undefined";
					let listener;
					if (isMultiplePut) {
						listener = transaction;
						param.forEach(entry => {
							store.put(entry);
						});
					} else {
						if (index) {
							store.index(index);
						}

						listener = store[method](param);
					}

					listener.oncomplete = event => {
						resolveQuery(event.target.result);
					};
					listener.onsuccess = event => {
						resolveQuery(event.target.result);
					};
					listener.onerror = event => {
						rejectQuery(event);
					};
				} else {
					rejectQuery(new Error("Store not found"));
				}
			});

		const methods = {
			count: () => _query("count", true, keyToUse),
			get: (keyToUse, index) => _query("get", true, keyToUse, index),
			getAll: (keyToUse, index) =>
				_query("getAll", true, keyToUse, index),
			put: entryData => _query("put", false, entryData),
			delete: keyToUse => _query("delete", false, keyToUse),
			clear: () => _query("clear", false),
			deleteDatabase: () => new Promise(function (resolve, reject) {

				const result = indexedDB.deleteDatabase;

				result.onerror = reject;
				result.onsuccess = resolve;
			})
		};
		const _successOnBuild = () => {
			db = openDBRequest.result;
			resolve(methods);
		};
		const _errorOnBuild = e => {
			reject(new Error(e.originalTarget && e.originalTarget.error || e));
		};
		openDBRequest.onupgradeneeded = _upgrade.bind(this);
		openDBRequest.onsuccess = _successOnBuild.bind(this);
		openDBRequest.onerror = _errorOnBuild.bind(this);
	});
}