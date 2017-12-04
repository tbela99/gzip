// @ts-check
if ("serviceWorker" in navigator) {

    navigator.serviceWorker.register("{scope}worker{debug}.js", { scope: "{scope}" })
        .then(function(registration) {

            console.log("🍻");
        })
        .catch(function(error) {
            console.log("😭", error);
        });
    }