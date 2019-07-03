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
// build d3477f0 2019-07-03 17:29:43-04:00
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
