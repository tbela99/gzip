/* do not edit! */
/**
 * Service worker browser client uninstall
 * @package     GZip Plugin
 * @copyright   Copyright (C) 2005 - 2018 Thierry Bela.
 *
 * dual licensed
 *
 * @license     LGPL v3
 * @license     MIT License
 */
// @ts-check
// build 929f400 2019-05-26 20:47:25-04:00
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
