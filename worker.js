'use strict';

// Install Service Worker
let CACHE_NAME = '{version}';

/* clean older versions*/
self.addEventListener('activate', function(event) {
    
  event.waitUntil(
          
    caches.keys().then(function(keyList) {
        
      return Promise.all(keyList.map(function(key) {
          
        if (key !== CACHE_NAME) {
            
          return caches.delete(key);
        }
        
      }));
    })
  );
});

self.addEventListener('message', function (event) {

    if (event.data.action == 'addFiles') {

        event.waitUntil(
            caches.open(CACHE_NAME)
                .then(function (cache) { 
                    
                    return cache.addAll(event.data.files); 
                })
            );
    }
});

self.addEventListener('fetch', function (event) {

    event.respondWith(
        caches.match(event.request)
            .then(function (response) {

                // Cache hit - return response
                if (response) {

                    return response;
                }

                return fetch(event.request).then(function (response) {

                    // Check if we received a valid response
                    if (!response || response.status !== 200 || response.type !== 'basic') {
                        
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

                    return response;
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