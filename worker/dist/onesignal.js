/* do not edit! */
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
    body.appendChild(script);
    body.removeChild(script);
}(document.createElement("script"), document.body);