 // @ts-check
/* eslint wrap-iife: 0 */
/* global LIB */

 /**
  * async css loader
  * @package     GZip Plugin
  * @subpackage  System.Gzip *
  * @copyright   Copyright (C) 2005 - 2018 Thierry Bela.
  *
  * dual licensed
  *
  * @license     LGPL v3
  * @license     MIT License
  */
 !function (LIB, undef) {

    'use strict;';

    const SW = {};
    const Utils = LIB.Utils;
    //const merge = Utils.merge;

    const supported = 'serviceWorker' in navigator;

    const serviceworker = navigator.serviceWorker;
    
    LIB.SW = SW;

    Object.defineProperties(SW, {

        supported: {

            get: function () {

                return supported;
            },
            enumerable: true
        },
        serviceworker: {

            get: function () {

                return serviceworker;
            },
            enumerable: true
        },
        ServiceWorker: {

            get: function () {

                return ServiceWorker;
            },
            enumerable: false
        }
    });

    if(!supported) {

        console.log('service worker not supported.');
        return;
    }

    /**
     * storeInfo: {name: 'db', storeName: 'store1' }
     */
    SW.getInstance = function (options) {

        return new ServiceWorker(options);
    }

    function ServiceWorker(options) {
        
        'use strict;';
    
        const self = this;
        let worker, state;

        Utils.reset(self);

        self.setOptions(options); //

        Object.defineProperties(self, {

            state: {

                get: function () {

                    return state;
                },
                enumerable: true
            },
            worker: {

                get: function () {

                    return worker;
                },
                enumerable: true
            }
        });

        serviceworker.register(options.worker).then(function (registration) {

            if (registration.installing) {

                worker = registration.installing;
                state = 'installing';
            } 
            
            else if (registration.waiting) {

                worker = registration.waiting;
                state = 'waiting';
            } 
            
            else if (registration.active) {

                worker = registration.active;
                state = 'active';
            }

            if (worker != undef) {

                worker.addEventListener('statechange', function (e) {
                    
                    self.trigger('statechange', e);
                });

                self.trigger('ready');
            }

        }).catch (function (error) {
            // L'enregistrement s'est mal déroulé. Le fichier service-worker.js
            // est peut-être indisponible ou contient une erreur.
            state = 'error';
            self.trigger('error', error);
        });
    }

    ServiceWorker.prototype.constructor = ServiceWorker;
        
    Utils.implement(ServiceWorker, {

        add: function (files) {
            
            const values = Object.values(files);

            if(values.length > 0) {

                this.trigger('add', files).postMessage({action: "addFiles", files: values});
            }

            return this;
        },
        remove: function (files) {

            const values = Object.values(files);

            if(values.length > 0) {

                this.trigger('remove', files).postMessage({action: "removeFiles", files: values});
            }

            return this;
        },
        postMessage: function (data) {

            const self = this, messageChannel = new MessageChannel();

             messageChannel.port1.onmessage = function (event) {

                self.trigger('message', event);
             };

            self.worker.postMessage(data,  [messageChannel.port2]);
            return self;
        },
        clearCache: function () {

            const self = this;

            self.postMessage({action: 'empty'});

            return self;
        },
        removeWorker: function () {

            const self = this;

            if(self.worker != undef) {

                self.worker.unregister().then(function () {

                    self.trigger('deleted');
                });
            }

            return self;
        },
        removeAllWorkers: function () {

            navigator.serviceWorker.getRegistrations().then(function(registrations) {

                let registration;
                
                for(registration of registrations) {
                    
                    registration.unregister()
                } 
            });
        }
    }, 
    LIB.Event, LIB.Options);

}(LIB);

