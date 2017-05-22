if ('serviceWorker' in navigator) {
    
    let sc = document.createElement('script');
    
    sc.async = true;
    sc.src = '{worker-db}';
    sc.onload = function () {
        
    //    sc.parentNode.removeChild(sc);
        
        let db = new Dexie('db_assets'); 
        let sw = navigator.serviceWorker;
        let deleted = [], 
            files = '{worker-files}';
        
        db.version(1).stores({
            files: "++id,&name,path"
        });
        
        // worker.js has changed!
        if(localStorage.swstate != '{worker-hash}') {

            sw.getRegistrations().then(function(registrations) {

                for(let registration of registrations) {

                    console.log(['unregister worker ...', registration]);

                    registration.unregister();
                } 
            });

            localStorage.swstate = '{worker-hash}';
        }
        
        db.open().then(function () {

            db.files.where('name').anyOf(files.map(function (file) {

                return file.name;

            })).
            toArray().
            then(function (results) {

                let result, i = 0, j, file;
                
                for(; i < results.length; i++) {
                    
                    for(j = 0; j < files.length; j++) {
                        
                        file = files[j];
                        
                        // exists
                        if(file.name == results[i].name) {
                            
                            result = results[i];
                            
                            if(result.path != file.path) {
                                
                                // out of date
                                deleted.push(result.path);
                            }
                            
                            // file has not changed
                            else {
                                
                                files.splice(j, 1);
                            }
                            
                            break;
                        }
                    }
                }
                
                console.log('files');
                console.log(files.map(function (file) {
                                    
                                    return file.path;
                                }));

                console.log('deleted');
                console.log(deleted.map(function (file) {
                                    
                                    return file.path;
                                }));

                sw.register('{worker-location}').then(function(registration) {

                     let state = registration.installing || registration.waiting || registration.active;

                     if(state) {

                        if(files.length > 0) {

                            state.postMessage({
                                action: 'addFiles', 
                                files: files.map(function (file) {
                                    
                                    return file.path;
                                })
                            });
                        }
                        
                        if(deleted.length > 0) {

                            state.postMessage({
                                action: 'removeFiles', 
                                files: deleted
                            });
                        }
                     } 

                 }).
                catch(function(error) {

                 //    console.log('Registration failed with ' + error);
                     // unregister(sw);
                 });

                
            });
        });
    }
    
    document.head.appendChild(sc);
}