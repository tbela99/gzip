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
        }
        /*,
			// get user ID
			function() {
				OneSignal.on("subscriptionChange", function(isSubscribed) {
					if (isSubscribed) {
						// The user is subscribed
						//   Either the user subscribed for the first time
						//   Or the user was subscribed -> unsubscribed -> subscribed
						OneSignal.getUserId(function(userId) {
							// Make a POST call to your server with the user ID
						});
					}
				});
			} */);
    };
    body.removeChild(body.appendChild(script));
}(document.createElement("script"), document.body);