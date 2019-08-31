/* do not edit! */
/**
 * Service worker browser client
 * @package     GZip Plugin
 * @copyright   Copyright (C) 2005 - 2018 Thierry Bela.
 *
 * dual licensed
 *
 * @license     LGPL v3
 * @license     MIT License
 */
// @ts-check
// build 5be4004 2019-08-31 10:22:44-04:00
if ("serviceWorker" in navigator) {
    navigator.serviceWorker.register("{scope}worker{debug}.js", {
        scope: "{scope}"
    }).catch(function(error) {
        //	console.log(error);
        console.error("😭", error);
    });
    if ("onbeforeinstallprompt" in window) {
        let deferredPrompt;
        let button;
        const buttonHTML = '<div class="pwa-app-install pwa-app-install-bottom"><div class="alert alert-success"><div class=alert-body>' + "<button type=button class=close data-dismiss=alert aria-label=Close>" + "<span aria-hidden=true>&times;</span>" + "</button>" + "Click <a href=# data-action=install-pwa-app>here</a> to make this site available offline.";
        const clickHandler = function(e) {
            e.preventDefault();
            e.stopPropagation();
            deferredPrompt.prompt();
            button.removeEventListener("click", clickHandler, false);
            button = null;
            // log the platforms provided as options in an install prompt
            //	console.log(deferredPrompt.platforms); // e.g., ["web", "android", "windows"]
                        deferredPrompt.userChoice.then(function(outcome) {
                console.info(outcome);
 // either "installed", "dismissed", etc.
                        }, function(error) {
                console.error("😭", error);
            });
            // e.target.closest('[data-action=install-pwa-app]').removeEventListener('click', clickHandler, false)
                };
        const createButton = function() {
            document.body.insertAdjacentHTML("beforeend", buttonHTML);
            button = document.querySelector("a[data-action=install-pwa-app]");
            button.addEventListener("click", clickHandler, false);
        };
        window.addEventListener("beforeinstallprompt", function(e) {
            //	console.log("beforeinstallprompt", e);
            deferredPrompt = e;
            e.preventDefault();
            //	if ("getInstalledRelatedApps" in navigator) {
            //		navigator.getInstalledRelatedApps().then(function(relatedApps) {
            //			if (relatedApps.length == 0) {
            //				createButton();
            //			}
            //		});
            //	} else {
                        createButton();
            //	}
                });
    }
}
