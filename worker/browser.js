/**
 * Service worker browser client
 * @package     GZip Plugin
 * @subpackage  System.Gzip *
 * @copyright   Copyright (C) 2005 - 2018 Thierry Bela.
 *
 * dual licensed
 *
 * @license     LGPL v3
 * @license     MIT License
 */

// @ts-check

if ("serviceWorker" in navigator) {
	navigator.serviceWorker.
		register("{scope}worker{debug}.js", {scope: "{scope}"}).
		//    .then(function(registration) {

		//    console.log("🍻");
		//    })
		catch(function(error) {
			//	console.log(error);
			console.error("😭", error);
		});

	if ("onbeforeinstallprompt" in window) {
		let deferredPrompt;

		const buttonHTML =
			'<div class="pwa-app-install pwa-app-install-bottom alert">' +
			"<button type=button class=close data-dismiss=alert aria-label=Close>" +
			"<span aria-hidden=true>&times;</span>" +
			"</button>" +
			"click <a href=# data-action=install-pwa-app>here</strong> to install our app.";

		const clickHandler = function(e) {
			e.preventDefault();
			e.stopPropagation();

			deferredPrompt.prompt();

			// e.target.closest('[data-action=install-pwa-app]').removeEventListener('click', clickHandler, false)
		};

		const createButton = function() {
			document.body.insertAdjacentHTML("beforeend", buttonHTML);
			document.
				querySelector("a[data-action=install-pwa-app]").
				addEventListener("click", clickHandler, false);
		};

		window.addEventListener("beforeinstallprompt", function(e) {
			console.log("beforeinstallprompt", e);

			deferredPrompt = e;

			e.preventDefault();

			if ("getInstalledRelatedApps" in navigator) {
				navigator.getInstalledRelatedApps().then(function(relatedApps) {
					if (relatedApps.length == 0) {
						createButton();
					}
				});
			} else {
				createButton();
			}
		});
	}
}
