const cacheName = 'gzip_docs';

self.addEventListener("fetch", (event) => {
    event.respondWith((async function () {

        return fetch(event.request).then(networkResponse => {
            // validate response before

            if (networkResponse && networkResponse.ok) {

                caches.open(cacheName)
                    .then(cache => cache.put(event.request, networkResponse.clone()));
                return networkResponse;
            }

            return response;

        }).catch(( /*error*/ ) => {

            // cache update failed
            /* console.error("😭", error) */
            return caches.match(event.request, {
                cacheName
            });

        });
    })());
});