'use strict';

// Install Service Worker
let CACHE_NAME = 'rs-worker';

/* clean older versions*/
//self.addEventListener('install', function () {

    // oninstall
//    console.info('new content installed!');
//});

self.addEventListener('activate', function(event) {
    
//    console.info('worker activated!');

    event.waitUntil(

        caches.keys().then(function(keyList) {

                return Promise.all(keyList.map(function(key) {

                    if (key !== CACHE_NAME) {

                        return caches.delete(key);
                    }        
                })
            );
        })
    );
});

self.addEventListener('message', function (event) {

 //   console.info('message received!');

    switch(event.data.action) {

        case 'addFiles':

            event.waitUntil(
                caches.open(CACHE_NAME)
                    .then(function (cache) { 
                        
                        cache.addAll(event.data.files); 
                    })
                );

            break;

        case 'removeFiles':

            event.waitUntil(
                caches.open(CACHE_NAME)
                    .then(function (cache) { 

                        for(let file of event.data.files) {

                            cache.delete(file); 
                        }                        
                    })
                );

            break;
    }
});

self.addEventListener('fetch', function (event) {

    let request = event.request;

    event.respondWith(
        caches.match(request)
            .then(function (response) {

                // Cache hit - return response
                if (response) {

                //    console.info('cache hit!');                    
                    return response;
                }

                return fetch(request).then(function (response) {

                    // Check if we received a valid response
                    if (!response || response.status != 200 || request.method != 'GET' || response.type != 'basic') {
                        
                    //    console.warn('cache miss!');
                        return response;
                    }

                    let cacheControl = response.headers.getAll('Cache-Control').join();

                    // no store no cache and 
                    if(cacheControl.match(/(\bno-store\b)|(\bno-cache\b)/) && cacheControl.match(/(\bmust-revalidate\b)/) /*&& !cacheControl.match(/\bmax-age=/)*/) {

                        return response;
                    }

                    // IMPORTANT: Clone the response. A response is a stream
                    // and because we want the browser to consume the response
                    // as well as the cache consuming the response, we need
                    // to clone it so we have two streams.

                    caches.open(CACHE_NAME)
                            .then(function (cache) {
                                cache.put(event.request, response);
                            });
                            
                //    console.info('cache updated!');                    
                    return response.clone();
                });
            }
        )
/*
        .
        catch(function(error) {
            
            return caches.open(CACHE_NAME).
                        then(function(cache) {
                                return cache.match('/offline.html');
                        });
        })
                */
    );
});