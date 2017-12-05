/* do not edit! */
// @ts-check
if ("serviceWorker" in navigator) {
    navigator.serviceWorker.register("{scope}worker{debug}.js", {
        scope: "{scope}"
    }).catch(function(error) {
        console.log(error);
    });
}