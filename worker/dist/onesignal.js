/* do not edit! */
// @ts-check
/* wrap-iife: 0 */
!function(script, body) {
    script.src = "https://cdn.onesignal.com/sdks/OneSignalSDK.js";
    script.defer = true;
    script.async = true;
    script.onload = function() {
        const OneSignal = window.OneSignal = window.OneSignal || [];
        OneSignal.push(function() {
            OneSignal.init({
                appId: "{APP_ID}"
            });
        });
    };
    body.removeChild(body.appendChild(script));
}(document.createElement("script"), document.body);