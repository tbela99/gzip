
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

# CSS Improvements

- Fetch remote css files, images and fonts and store them locally
- Merge css files (this process @import directive)
- Do not process css files that match a pattern
- Remove css files that match a pattern
- Load css files in a non blocking way

# Critical CSS Path

See [here](https://developers.google.com/speed/docs/insights/OptimizeCSSDelivery) for more info. The critical path enable instant page rendering by providing classes used to render the page before the stylesheets are loaded.
Any selector that affects the page rendering is a good candidate (set dimensions, define positioning, fonts, sections background color, etc..). There is no automatic extraction and you must provide these settings to extract css classes.

- CSS class definitions for critical css path
- A list of selectors to extract from the page css
- The web fonts are extracted automatically and preloaded

# Progressive Web App
Offline mode capabilities using one of these PWA network strategy:
    a. Cache only (currently disabled in the settings page)
    b. Network only
    c. Cache first, falling back to network
    d. Network first, falling back to cache
    e. Cache, with network update - stale while revalidate <- this is the default

Roadmap
-------
0. Remove <Link rel=preload> http header and use <link> HTML tag instead. see [here](https://jakearchibald.com/2017/h2-push-tougher-than-i-thought/)
0. PWA: Implement out of the box support for progressive web apps (provide a manifest, a skeleton, a start url?). see [here](https://techbeacon.com/how-use-service-workers-progressive-web-apps?utm_source=mobilewebweekly&utm_medium=email) - we need to define an app architecture
0. Create a standalone app a using android and chrome. see [here](https://developers.google.com/web/updates/2014/11/Support-for-installable-web-apps-with-webapp-manifest-in-chrome-38-for-Android)
0. IMAGES: read this [here](https://kinsta.com/blog/optimize-images-for-web/)
0. PWA: Deep links in pwa app or website. see [here](http://blog.teamtreehouse.com/registering-protocol-handlers-web-applications) and [here](https://developer.mozilla.org/en-US/docs/Web-based_protocol_handlers)
0. IMAGES: Implement progressive images loading [here](https://jmperezperez.com/medium-image-progressive-loading-placeholder/)
0. IMAGES: Implement images delivery optimization see [here](https://www.smashingmagazine.com/2017/04/content-delivery-network-optimize-images/) and [here](https://developers.google.com/web/updates/2015/09/automating-resource-selection-with-client-hints)
0. IMAGES: Implement support for <pictures> element see [here](https://www.smashingmagazine.com/2013/10/automate-your-responsive-images-with-mobify-js/)
0. CORS for PWA:https://filipbech.github.io/2017/02/service-worker-and-caching-from-other-origins | https://developers.google.com/web/updates/2016/09/foreign-fetch | https://stackoverflow.com/questions/35626269/how-to-use-service-worker-to-cache-cross-domain-resources-if-the-response-is-404
0. CSS: deduplicate, merge properties, rewrite rules, etc
0. PWA: Web Push Notification. see [here](https://serviceworke.rs/web-push.html)

Change History
--------------
# V2.0
0. PWA: implement network strategies:
    a. Cache only
    b. Network only
    c. Cache first, falling back to network
    d. Network first, falling back to cache
    e. Cache, with network update

# V1.1
0. CSS: preload web fonts