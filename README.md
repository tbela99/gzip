# Joomla Website Optimizer Plugin

[![Known Vulnerabilities](https://snyk.io/test/github/tbela99/gzip/badge.svg)](https://snyk.io/test/github/tbela99/gzip) [![Download from JED](https://img.shields.io/badge/JED-Download-blueviolet.svg)](https://extensions.joomla.org/extensions/extension/core-enhancements/performance/gzip/)

Do you want to improve your website by

- improving loading performance?
- enable offline capabilities via service worker?
- or turn it into a progressive web application?

This extension allows you to do all of these things.

There are additional plugins that help optimizing and profiling your page or server

- [Server Timing Plugin](https://github.com/tbela99/server-timing) enable the server timing http headers. see [here](https://www.w3.org/TR/server-timing/)
- [HTML Minifier](https://github.com/tbela99/html-minifier) minify html in an html5 compliant way.

![screenshot](https://raw.githubusercontent.com/tbela99/gzip/master/Capture.PNG)

- [Joomla Website Optimizer Plugin](#Joomla-Website-Optimizer-Plugin)
  - [1. General improvements](#1-General-improvements)
    - [Moving script and css position in the page](#Moving-script-and-css-position-in-the-page)
  - [1.2 Caching](#12-Caching)
  - [1.3 Sub Resource Integrity (SRI)](#13-Sub-Resource-Integrity-SRI)
  - [1.4 Critical CSS Path](#14-Critical-CSS-Path)
  - [2. Images](#2-Images)
    - [Responsive images](#Responsive-images)
  - [3. Javascript Improvements](#3-Javascript-Improvements)
  - [4. CSS Improvements](#4-CSS-Improvements)
  - [5. Progressive Web App](#5-Progressive-Web-App)
    - [Features](#Features)
    - [5.1. Installable web app](#51-Installable-web-app)
    - [5.2. Web Share Target Level 2](#52-Web-Share-Target-Level-2)
    - [5.3. Offline mode](#53-Offline-mode)
    - [5.4. Configurable cache](#54-Configurable-cache)
    - [5.5. Network cache strategies](#55-Network-cache-strategies)
    - [5.6. Web Push](#56-Web-Push)
    - [5.7. Service worker router api](#57-Service-worker-router-api)
    - [5.8. Exclude resources from the service worker management](#58-Exclude-resources-from-the-service-worker-management)
    - [5.9 Background Sync](#59-Background-Sync)
  - [6. CDN and Cookieless Domains](#6-CDN-and-Cookieless-Domains)
  - [7. Misc](#7-Misc)

## 1. General improvements

- advanced page optimizations which drastically improve the page performance score over various tools.
- turn the website into an installable Progressive Web Application
- Sub-resources integrity check: computed for javascript and css files (for now). see [here](https://hacks.mozilla.org/2015/09/subresource-integrity-in-firefox-43/)
- Push resources (require http 2 protocol). you can configure which resources will be pushed
- Insert scripts and css that have 'data-position="head"' attribute in head instead of the body
- force script and css to be ignored by the optimizer by setting 'data-ignore="true"' attribute
- connect to domains faster: automatically detect domains and add < link rel=preconnect > header

### Moving script and css position in the page

script and css position can be controlled by add 'data-position' attribute to the tag. possible values are

- head: move the file to the head (if not present yet)
- ignore: ignore the file
- missing tag or other values means move to the footer of the page.

## 1.2 Caching

- Efficiently cache resources using http caching headers. This requires apache mod_rewite. I have not tested on other web servers
- Range requests are supported for cached resources (you can cache audio & video content)
- Configurable cache using the service worker

## 1.3 Sub Resource Integrity (SRI)

- compute SRI for css and javascript files

If you use a cdn, you will need to disable cdn optimizations for css and javascript. They must not alter css and javascript

## 1.4 Critical CSS Path

- Eliminate **FOUC** by providing critical css path selectors. See [here](https://developers.google.com/speed/docs/insights/OptimizeCSSDelivery) for more info.
- The critical path enable instant page rendering by providing a minimal set of selectors and classes used to render the visible part of the page before the stylesheets are loaded.
- Any selector that affects the page rendering is a good candidate (set dimensions, define positioning, fonts, sections background color, etc..).
- There is no automatic extraction and you must provide these settings to extract css classes.

- CSS class definitions for critical css path
- A list of selectors to extract from the page css
- The web fonts are extracted automatically and preloaded

## 2. Images

- deliver images in webp format when the browser signals it supports it
- generate svg placeholder from images for quick image preview
- generate responsive images automatically
- generate svg placeholder for images for faster page load
- lazyload images that are using svg placeholder

### Responsive images

- automatically add srcset and sizes for images. Only necessary images are generated. Images smaller that the breakpoint are ignored.
- resize and crop images using a one of these methods (face detection, entropy, center or default).
- configure breakpoints used to create smaller images
- scrset urls are automatically rewritten when http cache is enabled
- automatically resize css background images. You can configure breakpoints for this feature.

## 3. Javascript Improvements

- Fetch remote javascript files locally
- Merge javascript files
- Minify javascript files
- Ignore javascript files that match a pattern
- Remove javascript files that match a pattern
- Move javascript at the bottom of the page
- load javascript in a non blocking way if there is only one javascript file in the page.

## 4. CSS Improvements

- Fetch remote css files, images and fonts and store them locally
- Merge css files (this process @import directive)
- Minify css files
- Do not process css files that match a pattern
- Remove css files that match a pattern
- Ignore css files that match a pattern
- Load css files in a non blocking way

## 5. Progressive Web App

### Features

here are some of the features implemented

- Offline mode
- Installable web app with configurable settings
- Background Sync with fallback support
- Configurable manifest settings
- Configurable cache settings per resource type
- Limit the number of files in the cache
- Limit the maximum file size
- Preloaded resource can be configured
- Configurable network caching strategies
- Automatic service worker update. A new version is available whenever you change the settings
- Mobile apps deep linking
- Web share target level 2 support.
- Push notifications using Onesignal

### 5.1. Installable web app

- The app can be installed as a standalone web app with google chrome / firefox on android via the menu “Menu / Add to home page”. You need to configure the manifest file and provide icons first.
- The app can be installed as a standalone desktop application (tested on windows 10) with google chrome as long as you provide a 512x512 icon.
- Alternative links to native mobile apps can be provided and the preference can be configured

### 5.2. Web Share Target Level 2

![web-share-target.jpg](https://raw.githubusercontent.com/tbela99/gzip/master/web-share-target.jpg)

Web Share Target allows your pwa app to receive data (text and files) shared by other applications on your mobile device. You can configure your pwa to become a receiver on android and IOS using the web target api level 2.
You can also configure which data you want to be sent to your app. To learn more about web shared target api, please go [here](https://wicg.github.io/web-share-target/) and [here](https://wicg.github.io/web-share-target/level-2/)

### 5.3. Offline mode

- **Offline page**:
  You can configure an offline page that will be show whenever the user navigates to a page and that page could not be loaded.

- **Preloaded resources**:
  You can provide the list of urls to load when the service worker is installed like icons, logo, css files, web pages, etc ...

### 5.4. Configurable cache

- Configure cache eviction settings by file type
- Configure the maximum file size for files that can be stored in the cache
- Configure the maximum number of files allowed in the cache

### 5.5. Network cache strategies

You can choose how all your web assests will be cached.

- Cache only
- Network only
- Cache first, falling back to network
- Network first, falling back to cache
- Cache, with network update - stale while revalidate <- this is the default.

You can also customize settings per resource type.

### 5.6. Web Push

- Manage Web Push subscription using OneSignal
- Added basic push notification settings for Joomla articles

Note that some adblockers may break feature.

### 5.7. Service worker router api

Add routes to customize fetch event networking strategy by using either a static route or a regexp

### 5.8. Exclude resources from the service worker management

You can specify which resource are not managed by the service worker by specifying a list of patterns. They will always use the network only strategy.

### 5.9 Background Sync

With this API you can automatically replay some or all the requests that fail. You can choose to replay either GET or POST requests or requests that match a pattern.

## 6. CDN and Cookieless Domains

- Configure up to 3 domains from which resources will be loaded.
- You can also configure which kind of resource are loaded from these domains.
- CORS headers are automatically added for responses sent from these domains.

## 7. Misc

- Preview the changes to the manifest before you save them
- Joomla administrator is excluded from the service worker cached resources
- You can secure your Joomla administrator access by defining a secret access token.
