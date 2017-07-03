if ('serviceWorker' in navigator) {

 // '{worker-hash}'
    
    let undef;
    let sc = document.createElement('script');
    
    sc.async = true;
    sc.src = "{worker-db}";
    sc.onload = function () {
        
        let store = localforage.createInstance({
            name: "{worker-name}"
        });
        
        let page;
        
        let sw = navigator.serviceWorker;
        let deleted = [], 
            files = "{worker-files}";
        
        store.config({
            
            driver: [
                
                localforage.INDEXEDDB,
                localforage.WEBSQL,
                localforage.LOCALSTORAGE
            ]
        });
        
        // worker.js has changed!
        /*
        // uninstall service worker
       
            sw.getRegistrations().then(function(registrations) {

                for(let registration of registrations) {

                    console.log(["unregister worker ...", registration]);

                    registration.unregister();
                } 
            });

            localStorage.swstate = "{worker-hash}";
        */
        
        store.getItems(Object.keys(files)).
            then(function (results) {

                for(let key in results) {

                    if(results[key] != undef && results[key] != files[key]) {

                        deleted.push(results[key]);
                    }
                }

                store.setItems(files).
                        then(function () {

                            sw.register("{worker-location}").then(function(registration) {

                                let state = registration.installing || registration.waiting || registration.active;

                                if(state != undef) {

                                    files = Object.values(files);

                                    if(files.length > 0) {

                                        state.postMessage({
                                            action: "addFiles", 
                                            files: files
                                        });
                                    }
                                    
                                    if(deleted.length > 0) {

                                        state.postMessage({
                                            action: "removeFiles", 
                                            files: deleted
                                        });
                                    }

                                    page = "{worker-page}";

                                    if(page != undef) {

                                        store.getItem(page).then(function(data) {

                                        //    console.log([Date.now(), data, Date.now() - data]);

                                            if(data == undef || Date.now() - data >= "{worker-max-age}") {

                                            //    console.log([Date.now(), data, Date.now() - data]);
                                            //    console.log("page is obsolete " + page + " timeout " + "{worker-max-age}");
                                                        
                                                state.postMessage({
                                                    action: "removeFiles", 
                                                    files: [page]
                                                });

                                                store.setItem(page, +new Date("{worker-date}")).then(function () {

                                                    state.postMessage({
                                                        action: "addFiles", 
                                                        files: [page]
                                                    });
                                                });
                                            }
                                        });
                                    }
                                } 

                        }).
                        catch(function(error) {

                        //    console.log("Registration failed with " + error);
                            // unregister(sw);
                        });
                    });

            });

        sc.parentElement.removeChild(sc);
    }
    
    document.head.appendChild(sc);
}