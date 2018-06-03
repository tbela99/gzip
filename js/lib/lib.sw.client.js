// @ts-check
/* global LIB, document */
/* eslint wrap-iife: 0 */
/* global localforage */
/**
 * Manage file versioning using localForage
 *
 * @package     GZip Plugin
 * @copyright   Copyright (C) 2005 - 2018 Thierry Bela.
 *
 * dual licensed
 *
 * @license     LGPL v3
 * @license     MIT License
 */
!function (LIB, undef) {

    'use strict;';

    console.log(LIB);

    const SW = LIB.SW;
    const ServiceWorker = SW.ServiceWorker;

    LIB.Utils.implement(ServiceWorker, {

        constructor: function () {

            const self = this;

            self.on({

                add: function (files) {

                    const store = self.store, deleted = [];

                    if(store != undef) {
                        
                        store.getItems(Object.keys(files)).
                            then(function (results) {

                                let key;

                                for(key in results) {

                                    if(results[key] != undef && results[key] != files[key]) {

                                        deleted.push(results[key]);
                                    }
                                }

                            }).
                            then(function () {

                                store.setItems(files);

                                if(deleted.length > 0) {

                                    // remove obsolete files
                                    self.postMessage({
                                        action: "removeFiles", 
                                        files: deleted
                                    });
                                }
                            });
                    }

                }
            });

            this.previous.apply(this, arguments);
        },
        clearCache: function () {

            if(this.store != undef) {

                this.store.clear();
            }

            return this.previous();
        },
        setStorage: function (storeInfo) {

            const store = this.store = localforage.createInstance(storeInfo);
                    
            store.config({
                    
                    driver: [
                        
                        localforage.INDEXEDDB,
                        localforage.WEBSQL,
                        localforage.LOCALSTORAGE
                    ]
                });   

            return this;
        }
    });

}(LIB);

