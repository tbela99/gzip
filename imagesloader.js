// @ts-check
LIB.ready(function(undef) {
	// intersection-observer.min.js

	function lazyload() {
		let observer = LIB.images.lazy(".image-placeholder").on({
			load: function(img, oldImage) {
				if (oldImage.dataset.srcset != null) {
					oldImage.srcset = oldImage.dataset.srcset;
				}

				oldImage.src = img.src;
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
		) ||
		!("isIntersecting" in window.IntersectionObserverEntry.prototype)
	) {
		const script = document.createElement("script");
		script.onload = lazyload;
		script.defer = true;
		script.async = true;
		script.src = "{script-src}";
		document.body.appendChild(script);
	} else {
		lazyload();
	}
});
