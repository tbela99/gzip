// @ts-check

/**
 * lazy image laoder
 * @package     GZip Plugin
 * @copyright   Copyright (C) 2005 - 2018 Thierry Bela.
 *
 * dual licensed
 *
 * @license     LGPL v3
 * @license     MIT License
 */

LIB.ready(function(undef) {
	// intersection-observer.min.js

	function lazyload() {

		LIB.images.lazy(".image-placeholder").on({
			load: function(img, oldImage) {

				if (oldImage.dataset.srcset != null) {
					oldImage.srcset = oldImage.dataset.srcset;
                    oldImage.removeAttribute('data-srcset');
				}

                oldImage.removeAttribute('data-src');

				oldImage.insertAdjacentHTML(
					"beforebegin",
					'<span class="image-placeholder-wrapper"><span class="image-placeholder-opacity"><span class="image-placeholder-element" style="background-image:url(\'' +
					(oldImage.currentSrc || oldImage.src) +
						"')\">"
				);

				const container = oldImage.previousElementSibling;

				oldImage.classList.remove('image-placeholder-lqip', 'image-placeholder-svg', 'image-placeholder');
				container.insertBefore(oldImage, container.firstElementChild);

				setTimeout(function() {
					container.classList.add('image-placeholder-complete');

					setTimeout(function() {
						container.parentElement.insertBefore(
							oldImage,
							container
						);
						container.parentElement.removeChild(container);
					}, 250);
				}, 500);
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
        /*script.onreadystatechange =*/ script.onload = lazyload;
		script.defer = true;
		script.async = true;
		script.src = "{script-src}";
		document.body.appendChild(script);

	} else {
		if (!("isIntersecting" in window.IntersectionObserverEntry.prototype)) {
			Object.defineProperty(
				window.IntersectionObserverEntry.prototype,
				"isIntersecting",
				{
					get: function() {
						return this.intersectionRatio > 0;
					}
				}
			);
		}

        lazyload();
	}
});
