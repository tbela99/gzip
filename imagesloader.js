// @ts-check
LIB.ready(function(undef) {
	// intersection-observer.min.js

	function lazyload() {
		let observer = LIB.images.lazy(".image-placeholder").on({
			load: function(img, oldImage) {
				if (oldImage.dataset.srcset != null) {
					oldImage.srcset = oldImage.dataset.srcset;
				}

			//	const svg = atob(oldImage.src.split('base64,', 2)[1]);

                oldImage.insertAdjacentHTML('beforebegin', '<span style="position:relative;display:inline-block"><span style="transition:opacity .2s ease-out;background:#fff;display:block;position:absolute;width:100%;height:100%;left:0;top:0"><span style="display:block;width:100%;height:100%;background:url(\'' + oldImage.src + '\') 0 0 / cover no-repeat">');


                const container = oldImage.previousElementSibling;
				oldImage.src = img.src;
			//	img.src = svg;

                container.insertBefore(oldImage, container.firstElementChild);

                setTimeout(function () {

                    oldImage.nextElementSibling.style.opacity = 0;

                    setTimeout(function () {

                    	container.parentElement.insertBefore(oldImage, container);
                    	container.parentElement.removeChild(container);
					}, 250);

				}, 500);
              //  oldImage.nextElementSibling.style = ' ';
			},
			complete: function() {
				observer = null;
			}
		});
	}

	if (
		!(
			"IntersectionObserver" in window &&
			"IntersectionObserverEntry" in window &&
			"intersectionRatio" in window.IntersectionObserverEntry.prototype
		)

	) {
		const script = document.createElement("script");
		script.onload = lazyload;
		script.defer = true;
		script.async = true;
		script.src = "{script-src}";
		document.body.appendChild(script);
	}

	else {

        if (!("isIntersecting" in window.IntersectionObserverEntry.prototype)) {

            Object.defineProperty(window.IntersectionObserverEntry.prototype,
                'isIntersecting', {
                    get: function () {
                        return this.intersectionRatio > 0;
                    }
                });
        }

        lazyload();
	}
});
