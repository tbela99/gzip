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

// build b37168b 2021-12-24 17:13:52-05:00

if ("serviceWorker" in navigator && navigator.serviceWorker.controller) {
	navigator.serviceWorker.getRegistration().then(function (registration) {

		registration.unregister().then(function (result) {

			if (result) {

				console.info('The service worker has been successfully removed 😭');
			}
		});
	});
}