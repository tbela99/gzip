// @ts-check
/**
 * Service worker browser client uninstall
 * @package     GZip Plugin
 * @subpackage  System.Gzip *
 * @copyright   Copyright (C) 2005 - 2018 Thierry Bela.
 *
 * dual licensed
 *
 * @license     LGPL v3
 * @license     MIT License
 */
if ("serviceWorker" in navigator && navigator.serviceWorker.controller) {
	navigator.serviceWorker.getRegistrations().then(function(registrations) {
		let registration,
			i = registrations.length;

		while (i && i--) {
			registration = registrations[i];
			if (registration.scope == location.origin + "{scope}") {
				registration.unregister();
			}
		}
	});
}
