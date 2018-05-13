## Page Optimizer Plugin

Do you want to improve your website by

*   improving loading performance?
*   enable offline capabilities via service worker?
*   or turn it into a pwa?

This extension allows you to do all of these things.

There are additional plugins that help optmizing and profiling your page or server

*   [Server Timing Plugin](https://github.com/tbela99/server-timing) enable the server timing http headers. see [here](https://www.w3.org/TR/server-timing/)
*   [HTML Minifier](https://github.com/tbela99/html-minifier) minify html in an html5 compliant way.

# General improvements

*   advanced page optimizations which drastically improve the page performance score over various tools.
*   turn the website into an installable Progressive Web Application
*   Sub-resources integrity check: computed for javascript and css files (for now). see [here](https://hacks.mozilla.org/2015/09/subresource-integrity-in-firefox-43/)
*   Push resources (require http 2 protocol). you can configure which resources will be pushed
*   Insert scripts and css that have 'data-position="head"' attribute in head instead of the body
*   force script and css to be ignored by the optimizer by setting 'data-ignore="true"' attribute
*   connect to domains faster: automatically detect domains and add < link rel=preconnect > header

# Moving script and css position in the page

script and css position can be controlled by add 'data-position' attribute to the tag. possible values are

*   head: move the file to the head (if not present yet)
*   ignore: ignore the file
*   missing tag or other values means move to the footer of the page.

# Caching

*   Efficiently cache resources using http caching headers. This requires apache mod_rewite. I have not tested on other web servers
*   Range requests are supported for cached resources (you can cache audio & video content)

# SRI

*   compute SRI for css and javascript files

If you use a cdn, you will need to disable cdn optimizations for css and javascript. They must not alter css and javascript

# Critical CSS Path

Eliminate **FOUC** by providing critical css path selectors. See [here](https://developers.google.com/speed/docs/insights/OptimizeCSSDelivery) for more info.
The critical path enable instant page rendering by providing a minimal set of selectors and classes used to render the visible part of the page before the stylesheets are loaded.
Any selector that affects the page rendering is a good candidate (set dimensions, define positioning, fonts, sections background color, etc..).
There is no automatic extraction and you must provide these settings to extract css classes.

*   CSS class definitions for critical css path
*   A list of selectors to extract from the page css
*   The web fonts are extracted automatically and preloaded

# Images

*   deliver images in webp format when the browser signals it supports it
*   generate svg placeholder from images for quick image preview
*   generate responsive images automatically
*   generate svg placeholder for images for faster page load
*   lazyload images that are using svg placeholder

## Responsive images

*   automatically add srcset and sizes for images. Only necessary images are generated. Images smaller that the breakpoint are ignored.
*   resize and crop images using a one of these methods (face detection, entropy, center or default).
*   configure breakpoints used to create smaller images
*   scrset urls are automatically rewritten when http cache is enabled
*   automatically resize css background images. You can configure breakpoints for this feature.

# Javascript Improvements

*   Fetch remote javascript files locally
*   Merge javascript files
*   Minify javascript files
*   Ignore javascript files that match a pattern
*   Remove javascript files that match a pattern
*   Move javascript at the bottom of the page
*   load javascript in a non blocking way if there is only one javascript file in the page.

# CSS Improvements

*   Fetch remote css files, images and fonts and store them locally
*   Merge css files (this process @import directive)
*   Minify css files
*   Do not process css files that match a pattern
*   Remove css files that match a pattern
*   Ignore css files that match a pattern
*   Load css files in a non blocking way

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
2.  The app can be installed as a standalone desktop application (tested on windows 10) with google chrome as long as you provide a 512x512 icon.
3.  Alternative links to native mobile apps can be provided and the preference can be configured

## Web Push

1.  Manage Web Push subscription using OneSignal
1.  Added basic push notification settings for Joomla articles

## Service worker router api

Add routes to customize fetch event networking strategy by using either a static route or a regexp

## Exclude resources from the service worker management

You can specify which resource are not managed by the service worker by specifying a list of patterns. They will always use the network only strategy.

# CDN and Cookieless Domains

*   Configure up to 3 domains from which resources will be loaded.
*   You can also configure which kind of resource are loaded from these domains.
*   CORS headers are automatically added for responses sent from these domains.

# Misc

*   Joomla administrator is excluded from the service worker cached resources
*   You can secure your Joomla administrator access by defining a secret access token.

# Change History

## V2.3

1.  Enable CDN / cookieless domain support
1.  Enable CORS headers for cached resources
1.  Extend the list of file type supported by the cdn or cookieless domain
1.  Extend the list of file type supported by the url rewrite feature
1.  Choose how the text is rendered while web fonts are loading by customizing font-display
1.  Add a third option for service worker (disable, enable, force removal).
1.  Configure service worker route strategy per resource type from the Joomla administrator
1.  Implement the beforeinstallprompt event. see [here](https://w3c.github.io/manifest/#beforeinstallpromptevent-interface)

## V2.2

1.  optimized image lazyloader
1.  generate svg placeholder from images for quick preview
1.  resize css images for mobile / tablet
1.  IMAGES: Implement progressive images loading with intersectionObserver
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

### PWA: implement network strategies:

*   Cache only (disabled)
*   Network only
*   Cache first, falling back to network
*   Network first, falling back to cache
*   Cache, with network update

## V1.1

CSS: preload web fonts

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
