import {
    SW
} from "../serviceworker.js";

if (SW.app.offline.enabled) {

    SW.routes.on('fail', async (event) => {

        if (event.request.mode == 'navigate' && SW.app.offline.methods.includes(event.request.method)) {

            if (SW.app.offline.type == 'response') {

                return new Response(SW.app.offline.body, {
                    headers: new Headers({
                        'Content-Type': 'text/html; charset="{offline_charset}"'
                    })
                });
            }

            if (SW.app.offline.url != '') {

                return caches.match(SW.app.offline.url);
            }
        }

    });
}