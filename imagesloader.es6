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

	if (!("srcset" in new Image())) {
		//    try {

		document.body.insertAdjacentHTML(
			"beforeend",
			"<svg xmlns=http://www.w3.org/2000/svg width=1 height=1>" +
			"<defs>" +
				"<filter id=blur-lqip width=100% height=100% >" +
				"<feGaussianBlur stdDeviation=20 />" +
			    '</filter>' +
			    '</defs>'+
			   '</svg>'
		);
	}

	function lazyload() {
		LIB.images.lazy(".image-placeholder").on({

            /**
             *
             * @param {HTMLImageElement} img
             * @param {HTMLImageElement} oldImage
             */
			preload: function(img, oldImage) {

			    const legacy = !("currentSrc" in img);

				if (!legacy) {
					oldImage.insertAdjacentHTML(
						"beforebegin",
						'<span class=image-placeholder-wrapper><span class=image-placeholder-opacity><span class=image-placeholder-element style="background-image:url(\'' +
							(img.currentSrc || img.src) +
							"')\">"
					);
				}

				else {
					oldImage.insertAdjacentHTML(
						"beforebegin",
						"<span class=image-placeholder-wrapper><span class=image-placeholder-svg><svg width=100% height=100% version=1.1 xmlns=http://www.w3.org/2000/svg >" +
							'<image xlink:href="' +
							(img.currentSrc || img.src) +
							'" width=100% height=100% filter=url(#blur-lqip) x=0 y=0 />"'
					);
				}

				const container = oldImage.previousElementSibling;

				if (legacy) {

                    if ( typeof window.CustomEvent != "function" ) {

                        function CustomEvent ( event, params ) {
                            params = params || { bubbles: false, cancelable: false, detail: undefined };
                            const evt = document.createEvent( 'CustomEvent' );
                            evt.initCustomEvent( event, params.bubbles, params.cancelable, params.detail );
                            return evt;
                        }

                        CustomEvent.prototype = window.Event.prototype;
                    }

                    const svg = container.querySelector('svg');
                 //   const svgImage = container.querySelector('svg image');
                    function resize () {

                        const height = this.height;
                        const width = this.width;

                        svg.setAttribute('height', height);
                        svg.setAttribute('width', width);

                        //   svgImage.setAttribute('height', height);
                        //   svgImage.setAttribute('width', width);
                    }

                    img.addEventListener('sourcechange', resize);
                    img.addEventListener('load', resize);
                }

				oldImage.classList.remove(
					"image-placeholder-lqip",
					"image-placeholder-svg",
					"image-placeholder"
				);
				container.insertBefore(oldImage, container.firstElementChild);
			},
			load: function(img, oldImage) {
				if (oldImage.dataset.src != undef) {
					oldImage.src = oldImage.dataset.src;
					//	oldImage.removeAttribute("data-src");
				}

				if (oldImage.dataset.srcset != undef) {
					oldImage.srcset = oldImage.dataset.srcset;
					//	oldImage.removeAttribute("data-srcset");
				}

				setTimeout(function() {
					let container = oldImage;

					oldImage.removeAttribute("data-srcset");
					oldImage.removeAttribute("data-src");

					while (
						container != undef &&
						!container.classList.contains(
							"image-placeholder-wrapper"
						)
					) {
						container = container.parentElement;
					}

					container.classList.add("image-placeholder-complete");

					setTimeout(function() {

					//    if (container.parentElement != null) {

                            container.parentElement.insertBefore(
                                oldImage,
                                container
                            );

                            container.parentElement.removeChild(container);
					//	}

					}, 10);
				}, 10);
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