/* do not edit! */
// @ts-check
// Service worker browser client uninstall
if ("serviceWorker" in navigator && navigator.serviceWorker.controller) {
    navigator.serviceWorker.getRegistrations().then(function(registrations) {
        let registration, i = registrations.length;
        while (i && i--) {
            registration = registrations[i];
            if (registration.scope == location.origin + "{scope}") {
                registration.unregister();
            }
        }
    });
}