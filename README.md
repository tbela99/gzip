## Page Optimizer Plugin

There are additional plugins that help optmizing and profiling your page

*   [Server Timing Plugin](/projects/PWA/repos/server-timing/) enable the sevrer timing http headers
*   [HTML Minifier](/projects/WO/repos/html-minifier/) minify html in an html5 compliant way.

This performs many things:

*   advanced page optimizations which drastically improve the page performance score over various tools.
*   turn the website into an installable Progressive Web Application
*   compute SRI for css and javascript files

# General improvements

*   Sub-resources integrity check: computed for javascript and css files (for now). see [here](https://hacks.mozilla.org/2015/09/subresource-integrity-in-firefox-43/)
*   Push resources (require http 2 protocol). you can configure which resources will be pushed
*   Efficiently cache resources using http caching headers. This requires apache mod_rewite. I have not tested on other web servers
*   Range requests are supported for cached resources (you can cache audio & video content)
*   Insert scripts and css that have 'data-position="head"' attribute in head instead of the body
*   force script and css to be ignored by the optimizer by setting 'data-ignore="true"' attribute
*   connect to domains faster: automatically detect domains and add < link rel=preconnect >

# Images

*   deliver images in webp format when the browser signals it supports it
*   generate svg placeholder from images for quick image preview
*   generate responsive images automatically

## Responsive images

*   automatically add srcset and sizes for images. Only necessary images are generated. Images smaller that the breakpoint are ignored.
*   resize and crop images using a one of these methods (face detection, entropy, center or default).
*   configure breakpoints used to create smaller images
*   scrset images url is automatically rewritten when http cache is enabled

# Javascript Improvements

*   Fetch remote javascript files locally
*   Merge javascript files
*   Ignore javascript files that match a pattern
*   Remove javascript files that match a pattern
*   Move javascript at the bottom of the page
*   load javascript in a non blocking way if there is only one javascript file in the page.

# CSS Improvements

*   Fetch remote css files, images and fonts and store them locally
*   Merge css files (this process @import directive)
*   Do not process css files that match a pattern
*   Remove css files that match a pattern
*   Ignore css files that match a pattern
*   Load css files in a non blocking way

# Critical CSS Path

See [here](https://developers.google.com/speed/docs/insights/OptimizeCSSDelivery) for more info. The critical path enable instant page rendering by providing classes used to render the page before the stylesheets are loaded.
Any selector that affects the page rendering is a good candidate (set dimensions, define positioning, fonts, sections background color, etc..). There is no automatic extraction and you must provide these settings to extract css classes.

*   CSS class definitions for critical css path
*   A list of selectors to extract from the page css
*   The web fonts are extracted automatically and preloaded

# Progressive Web App

Offline mode capabilities can be set to one of these network strategies

## Network cache strategies

1.  Cache only
1.  Network only
1.  Cache first, falling back to network
1.  Network first, falling back to cache
1.  Cache, with network update - stale while revalidate <- this is the default

## PWA preloaded resources

You can provide the list of urls to load when the service worker is installed like icons, logo, css files, web pages, etc ...

## Installable web app

1.  The app can be installed as a standalone web app with google chrome / firefox on android via the menu “Menu / Add to home page”. You need to configure the manifest file and provide icons first.
2.  The app can be installed as a standalone desktop application (tested on wndows 10) with google chrome as long as you provide a 512x512 icon.
3.  Alternative links to native mobile apps can be provided and the preference can be configured

## Web Push

1.  Manage Web Push subscription using OneSignal
1.  Added basic push notification settings for Joomla articles

## Service worker router api

Add routes to customize fetch event networking startegy by using either a static route or a regexp

# Roadmap

## High priority list

1.  IMAGES: Implement progressive images loading with intersectionObserver [here](https://jmperezperez.com/medium-image-progressive-loading-placeholder/) and async decoding see [here](https://medium.com/dailyjs/image-loading-with-image-decode-b03652e7d2d2)
1.  prerender images using primitive.js svg generation https://github.com/ondras/primitive.js/blob/master/js/app.js
1.  prerender + [Page Visibility API](http://www.w3.org/TR/page-visibility/): how should prender links be chosen?
1.  Service worker cache expiration api (using localforage or a lightweight indexDb library)
1.  Background Sync see [here](https://developers.google.com/web/updates/2015/12/background-sync)
1.  Messaging API (broadcasting messages to and from all/single clients)
1.  Remove < Link rel=preload > http header and use < link > HTML tag instead. see [here](https://jakearchibald.com/2017/h2-push-tougher-than-i-thought/)
1.  CORS for PWA:https://filipbech.github.io/2017/02/service-worker-and-caching-from-other-origins | https://developers.google.com/web/updates/2016/09/foreign-fetch | https://stackoverflow.com/questions/35626269/how-to-use-service-worker-to-cache-cross-domain-resources-if-the-response-is-404
1.  CSS: deduplicate, merge properties, rewrite rules, etc
1.  Disk quota management see [here](https://developer.chrome.com/apps/offline_storage) and [here](https://developer.mozilla.org/fr/docs/Web/API/API_IndexedDB/Browser_storage_limits_and_eviction_criteria) and [here](https://gist.github.com/ebidel/188a513b1cd5e77d4d1453a4b6d060b0)
1.  Clear Site Data api see [here](https://www.w3.org/TR/clear-site-data/)
1.  Foreign fetch api? - only supported by chrome right now [here](https://filipbech.github.io/2017/02/service-worker-and-caching-from-other-origins)

## Low priority list

1.  Fetch remote resources periodically (configurable) (css and javascript). right now they are updated only once.
1.  Manage the service worker settings from the front end (notify when a new version is available, manually unregister, delete cache, etc ...)?
1.  Manage user push notification subscription from the Joomla backend (link user to his Id, etc ...)?
1.  Provide push notification endpoints (get user Id, notification clicked, notification closed, etc ...)
1.  Mobile apps deep link?
1.  PWA: Deep links in pwa app or website. see [here](http://blog.teamtreehouse.com/registering-protocol-handlers-web-applications) and [here](https://developer.mozilla.org/en-US/docs/Web-based_protocol_handlers)

# Change History

## V2.2

1.  optimized image lazyloader
1.  generate svg placeholder from images for quick preview
1.  resize css images for mobile / tablet

## V2.2

1.  remove '+' '=' and ',' from the hash generation alphabet
1.  Responsive images: resize images using breakpoints and leverage < img srcset >
1.  serve webp whenever the browser/webserver (using gd) supports it
1.  Disabling service worker will actually uninstall it
1.  Server Timing Header see [here](https://w3c.github.io/server-timing/#examples)
1.  automatic preconnect < link > added, web fonts preload moved closer to < head > for faster font load
1.  Add < link > with < noscript > when async css loading is enabled. without javascript, stylesheet were not previously rendered.

## V2.1

1.  Added push notifications using onesignal
1.  Added pwa manifest. The app is installable as a standalone application (tested on google chrome/android é windows 10 / firefox android)
1.  Precached urls list. You can now provide a list of urls that will be precached when the service worker is installed.
1.  Added router api. Add routes to customize fetch event networking strategy by using either a static route or a regexp
1.  Rebuild service worker and the manifest whenever the plugin is installed or the settings are updated
1.  Override meta name=generator with custom text
1.  Add a secret token to prevent administrator access
1.  Insert scripts and css that have 'data-position="head"' attribute in head instead of the body

## V2.0

1.  PWA: implement network strategies:

*   Cache only (disabled)
*   Network only
*   Cache first, falling back to network
*   Network first, falling back to cache
*   Cache, with network update

## V1.1

1.  CSS: preload web fonts

## V1.0

this release implements to to bottom page loading optimization

### SRI (Sub resources integrity)

1.  generate SRI for javascript and css files

### Critical CSS Path

1.  generate critical css path based on the list of selectors you provide.

### Javascript

1.  fetch files hosted on remote servers
1.  minify javascript files
1.  merge javascript files
1.  minify inline javascript
1.  ignore files based on pattern
1.  remove javascript files that match a pattern
1.  remove duplicates
1.  move javascript at the bottom of the page

### CSS

1.  fetch files hosted on remote servers
1.  minify css files
1.  merge css files (flatten @import)
1.  minify inline css
1.  ignore files based on pattern
1.  remove css files that match a pattern
1.  remove duplicates
1.  move css at the bottom of the page
1.  load css in a non blocking way
