
Page Optimizer Plugin
---------------------

This plugin is complementary to [HTML Minifier](https://git.inimov.com/projects/WO/repos/html-minifier/) plugin. It performs advanced page optimizations which drastically improve the page performance score over various tools. Here are some of them:

# General improvements

- Sub-resources integrity check: computed for script and link (for now). see [here](https://hacks.mozilla.org/2015/09/subresource-integrity-in-firefox-43/)
- Push resources (require http 2 protocol). you can configure which resources will be pushed
- Efficiently cache resources using http caching headers. This requires apache mod_rewite. I have not tested on other web servers
- Range requests are supported for cached resources

# Javascript Improvements

- Fetch remote javascript files locally
- Merge javascript files
- Ignore javascript files that match a pattern
- Remove javascript files that match a pattern
- Move javascript at the bottom of the page
- Exclude javascript files based on pattern

# CSS Improvements

- Fetch remote css files, images and fonts and store them locally
- Merge css files
- Do not process css files that match a pattern
- Remove css files that match a pattern
- Load css files in a non blocking way
- Extract critical css path. You must provide the list of css to extract. see [here](https://developers.google.com/speed/docs/insights/OptimizeCSSDelivery) for more info
- Exclude css files based on patterns

Roadmap
-------

0. PWA: Implement out of the box support for progressive web apps. see [here](https://techbeacon.com/how-use-service-workers-progressive-web-apps?utm_source=mobilewebweekly&utm_medium=email)
0. CORS for PWA:https://filipbech.github.io/2017/02/service-worker-and-caching-from-other-origins | https://developers.google.com/web/updates/2016/09/foreign-fetch | https://stackoverflow.com/questions/35626269/how-to-use-service-worker-to-cache-cross-domain-resources-if-the-response-is-404
0. IMAGES: read this [here](https://kinsta.com/blog/optimize-images-for-web/)
0. IMAGES: Implement progressive images loading [here](https://jmperezperez.com/medium-image-progressive-loading-placeholder/)
0. IMAGES: Implement images delivery optimization see [here](https://www.smashingmagazine.com/2017/04/content-delivery-network-optimize-images/) and [here](https://developers.google.com/web/updates/2015/09/automating-resource-selection-with-client-hints)
0. IMAGES: Implement support for <pictures> element see [here](https://www.smashingmagazine.com/2013/10/automate-your-responsive-images-with-mobify-js/)