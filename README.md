https://extensions.joomla.org/extensions/extension/core-enhancements/performance/gzip/

## 0.1. Page Optimizer Plugin

Do you want to improve your website by

-   improving loading performance?
-   enable offline capabilities via service worker?
-   or turn it into a progressive web application?

This extension allows you to do all of these things.

There are additional plugins that help optimizing and profiling your page or server

-   [Server Timing Plugin](https://github.com/tbela99/server-timing) enable the server timing http headers. see [here](https://www.w3.org/TR/server-timing/)
-   [HTML Minifier](https://github.com/tbela99/html-minifier) minify html in an html5 compliant way.

![screenshot](https://raw.githubusercontent.com/tbela99/gzip/master/Capture.PNG)

- [1. General improvements](#1-general-improvements)
  - [1.1 Moving script and css position in the page](#11-moving-script-and-css-position-in-the-page)
  - [1.2 Caching](#12-caching)
  - [1.3 Sub Resource Integrity (SRI)](#13-sub-resource-integrity-sri)
  - [1.4 Critical CSS Path](#14-critical-css-path)
- [2. Images](#2-images)
  - [2.1. Responsive images](#21-responsive-images)
- [3. Javascript Improvements](#3-javascript-improvements)
- [4. CSS Improvements](#4-css-improvements)
- [5. Progressive Web App](#5-progressive-web-app)
  - [5.1. Installable web app](#51-installable-web-app)
  - [5.2. Web Share Target Level 2](#52-web-share-target-level-2)
  - [5.3. Offline mode](#53-offline-mode)
  - [5.4. Preloaded resources](#54-preloaded-resources)
  - [5.5. Network cache strategies](#55-network-cache-strategies)
  - [5.6. Web Push](#56-web-push)
  - [5.7. Service worker router api](#57-service-worker-router-api)
  - [5.8. Exclude resources from the service worker management](#58-exclude-resources-from-the-service-worker-management)
- [6. CDN and Cookieless Domains](#6-cdn-and-cookieless-domains)
- [7. Misc](#7-misc)
- [8. Change History](#8-change-history)
  - [V2.5.0](#v250)
  - [V2.4.2](#v242)
  - [V2.4.1](#v241)
  - [V2.4](#v24)
  - [V2.3](#v23)
  - [V2.2](#v22)
  - [V2.1](#v21)
  - [V2.0](#v20)
    - [PWA: implemented network strategies:](#pwa-implemented-network-strategies)
  - [V1.1](#v11)
  - [V1.0](#v10)
    - [SRI (Sub resources integrity)](#sri-sub-resources-integrity)
    - [Critical CSS Path](#critical-css-path)
    - [Javascript](#javascript)
    - [CSS](#css)

# 1. General improvements

-   advanced page optimizations which drastically improve the page performance score over various tools.
-   turn the website into an installable Progressive Web Application
-   Sub-resources integrity check: computed for javascript and css files (for now). see [here](https://hacks.mozilla.org/2015/09/subresource-integrity-in-firefox-43/)
-   Push resources (require http 2 protocol). you can configure which resources will be pushed
-   Insert scripts and css that have 'data-position="head"' attribute in head instead of the body
-   force script and css to be ignored by the optimizer by setting 'data-ignore="true"' attribute
-   connect to domains faster: automatically detect domains and add < link rel=preconnect > header

## 1.1 Moving script and css position in the page

script and css position can be controlled by add 'data-position' attribute to the tag. possible values are

-   head: move the file to the head (if not present yet)
-   ignore: ignore the file
-   missing tag or other values means move to the footer of the page.

## 1.2 Caching

-   Efficiently cache resources using http caching headers. This requires apache mod_rewite. I have not tested on other web servers
-   Range requests are supported for cached resources (you can cache audio & video content)

## 1.3 Sub Resource Integrity (SRI)

-   compute SRI for css and javascript files

If you use a cdn, you will need to disable cdn optimizations for css and javascript. They must not alter css and javascript

## 1.4 Critical CSS Path

-   Eliminate **FOUC** by providing critical css path selectors. See [here](https://developers.google.com/speed/docs/insights/OptimizeCSSDelivery) for more info.
-   The critical path enable instant page rendering by providing a minimal set of selectors and classes used to render the visible part of the page before the stylesheets are loaded.
-   Any selector that affects the page rendering is a good candidate (set dimensions, define positioning, fonts, sections background color, etc..).
-   There is no automatic extraction and you must provide these settings to extract css classes.

*   CSS class definitions for critical css path
*   A list of selectors to extract from the page css
*   The web fonts are extracted automatically and preloaded

# 2. Images

-   deliver images in webp format when the browser signals it supports it
-   generate svg placeholder from images for quick image preview
-   generate responsive images automatically
-   generate svg placeholder for images for faster page load
-   lazyload images that are using svg placeholder

## 2.1. Responsive images

-   automatically add srcset and sizes for images. Only necessary images are generated. Images smaller that the breakpoint are ignored.
-   resize and crop images using a one of these methods (face detection, entropy, center or default).
-   configure breakpoints used to create smaller images
-   scrset urls are automatically rewritten when http cache is enabled
-   automatically resize css background images. You can configure breakpoints for this feature.

# 3. Javascript Improvements

-   Fetch remote javascript files locally
-   Merge javascript files
-   Minify javascript files
-   Ignore javascript files that match a pattern
-   Remove javascript files that match a pattern
-   Move javascript at the bottom of the page
-   load javascript in a non blocking way if there is only one javascript file in the page.

# 4. CSS Improvements

-   Fetch remote css files, images and fonts and store them locally
-   Merge css files (this process @import directive)
-   Minify css files
-   Do not process css files that match a pattern
-   Remove css files that match a pattern
-   Ignore css files that match a pattern
-   Load css files in a non blocking way

# 5. Progressive Web App

## 5.1. Installable web app

-   The app can be installed as a standalone web app with google chrome / firefox on android via the menu “Menu / Add to home page”. You need to configure the manifest file and provide icons first.
-   The app can be installed as a standalone desktop application (tested on windows 10) with google chrome as long as you provide a 512x512 icon.
-   Alternative links to native mobile apps can be provided and the preference can be configured

## 5.2. Web Share Target Level 2

![web-share-target.jpg](https://raw.githubusercontent.com/tbela99/gzip/master/web-share-target.jpg)

Web Share Target allows your pwa app to receive data (text and files) shared by other applications on your mobile device. You can configure your pwa to become a receiver on android and IOS using the web target api level 2.
You can also configure which data you want to be sent to your app.

## 5.3. Offline mode

Offline mode capabilities can be set to one of these network strategies

## 5.4. Preloaded resources

You can provide the list of urls to load when the service worker is installed like icons, logo, css files, web pages, etc ...

## 5.5. Network cache strategies

You can choose how your web assests will be cached. You can customize settings per resource type:
-   Cache only
-   Network only
-   Cache first, falling back to network
-   Network first, falling back to cache
-   Cache, with network update - stale while revalidate <- this is the default

## 5.6. Web Push

-   Manage Web Push subscription using OneSignal
-   Added basic push notification settings for Joomla articles

## 5.7. Service worker router api

Add routes to customize fetch event networking strategy by using either a static route or a regexp

## 5.8. Exclude resources from the service worker management

You can specify which resource are not managed by the service worker by specifying a list of patterns. They will always use the network only strategy.

# 6. CDN and Cookieless Domains

-   Configure up to 3 domains from which resources will be loaded.
-   You can also configure which kind of resource are loaded from these domains.
-   CORS headers are automatically added for responses sent from these domains.

# 7. Misc

-   Joomla administrator is excluded from the service worker cached resources
-   You can secure your Joomla administrator access by defining a secret access token.

# 8. Change History

## V2.5.0

- Implement Web Share Target api level 2

## V2.4.2

- Fix file not found error
- Add the possibility to ignore images based on a pattern

## V2.4.1

-   Load images using LQIP technique
-   Add a service worker for administrator with no caching because admin requests were still cached by the website service worker
-   Make lazyloaded images indexable using noscript tag
-   force file name generation whenever the settings are changed
-   Add new breakpoint 1920px for responsive images and css background images

## V2.4

-   Customize max-age value for cached resources
-   remove Expires header in favor of max-age
-   Service worker cache expiration api
-   Define cache expiration rule per file type
-   Add missing files to the git repo

## V2.3

-   Web fonts preloading: Choose how the text is rendered while web fonts are loading by customizing font-display
-   Enable CDN / cookieless domain support
-   Enable CORS headers for cached resources
-   The service worker is able to intercept CDN files as long as they are sent with CORS headers
-   Access to the website through CDN / cookieless domain can be redirected to a custom domain
-   Extend the list of file type supported by the cdn or cookieless domain
-   Extend the list of file type supported by the url rewrite feature
-   Add a third option for service worker (disable, enable, force removal).
-   Configure service worker route strategy per resource type from the Joomla administrator
-   Implement the beforeinstallprompt event. see [here](https://w3c.github.io/manifest/#beforeinstallpromptevent-interface)

## V2.2

-   optimized image lazyloader
-   generate svg placeholder from images for quick preview
-   resize css images for mobile / tablet
-   IMAGES: Implement progressive images loading with intersectionObserver
-   remove '+' '=' and ',' from the hash generation alphabet
-   Responsive images: resize images using breakpoints and leverage < img srcset >
-   serve webp whenever the browser/webserver (using gd) supports it
-   Disabling service worker will actually uninstall it
-   Server Timing Header see [here](https://w3c.github.io/server-timing/#examples)
-   automatic preconnect < link > added, web fonts preload moved closer to < head > for faster font load
-   Add < link > with < noscript > when async css loading is enabled. without javascript, stylesheet were not previously rendered.

## V2.1

-   Added push notifications using onesignal
-   Added pwa manifest. The app is installable as a standalone application (tested on google chrome/android é windows 10 / firefox android)
-   Precached urls list. You can now provide a list of urls that will be precached when the service worker is installed.
-   Added router api. Add routes to customize fetch event networking strategy by using either a static route or a regexp
-   Rebuild service worker and the manifest whenever the plugin is installed or the settings are updated
-   Override meta name=generator with custom text
-   Add a secret token to prevent administrator access
-   Insert scripts and css that have 'data-position="head"' attribute in head instead of the body

## V2.0

### PWA: implemented network strategies:

-   Cache only
-   Network only
-   Cache first, falling back to network
-   Network first, falling back to cache
-   Cache, with network update

## V1.1

CSS: preload web fonts

## V1.0

this release implements to to bottom page loading optimization

### SRI (Sub resources integrity)

generate SRI for javascript and css files

### Critical CSS Path

generate critical css path based on the list of selectors you provide.

### Javascript

-   fetch files hosted on remote servers
-   minify javascript files
-   merge javascript files
-   minify inline javascript
-   ignore files based on pattern
-   remove javascript files that match a pattern
-   remove duplicates
-   move javascript at the bottom of the page

### CSS

-   fetch files hosted on remote servers
-   minify css files
-   merge css files (flatten @import)
-   minify inline css
-   ignore files based on pattern
-   remove css files that match a pattern
-   remove duplicates
-   move css at the bottom of the page
-   load css in a non blocking way