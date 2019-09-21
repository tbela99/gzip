# Joomla GZIP Plugin Documentation

_V2.6.1_

- [Joomla GZIP Plugin Documentation](#joomla-gzip-plugin-documentation)
  - [Installing](#installing)
  - [Manage Settings](#manage-settings)
    - [General Settings](#general-settings)
      - [Debug](#debug)
      - [Minify HTML](#minify-html)
    - [HTTP Settings](#http-settings)
      - [Enable Save-Data support](#enable-save-data-support)
      - [HTTP Server Timing](#http-server-timing)
      - [DNS Prefetch](#dns-prefetch)
      - [HTTP Push](#http-push)
      - [Cache File](#cache-file)
      - [Hashing Method](#hashing-method)
      - [Cache Prefix](#cache-prefix)
      - [Max Age](#max-age)
      - [CORS Headers](#cors-headers)
      - [Custom Mimetypes](#custom-mimetypes)
      - [Custom Attributes](#custom-attributes)
    - [CDN](#cdn)
      - [Enable CDN](#enable-cdn)
      - [Access Control Allow Origin](#access-control-allow-origin)
      - [Redirect CDN Request](#redirect-cdn-request)
      - [CDN1, CDN2, CDN3](#cdn1-cdn2-cdn3)
      - [Enable CDN Domain for Custom Resources Type](#enable-cdn-domain-for-custom-resources-type)
      - [Use CDN Domain](#use-cdn-domain)
    - [Images](#images)
      - [Process Images](#process-images)
      - [Enforce Width and Height Attributes](#enforce-width-and-height-attributes)
      - [Fetch Remote Images](#fetch-remote-images)
      - [Convert Images to Webp](#convert-images-to-webp)
      - [Ignored Images](#ignored-images)
      - [Crop Method](#crop-method)
      - [Image Placeholder](#image-placeholder)
      - [Offline page URL](#offline-page-url)
      - [Offline HTML Page](#offline-html-page)
      - [Build Manifest File](#build-manifest-file)
      - [App Short Name](#app-short-name)
      - [App Name](#app-name)
      - [App Description](#app-description)
      - [Start URL](#start-url)
      - [Background Color](#background-color)
      - [Theme Color](#theme-color)
      - [Display Mode](#display-mode)
      - [Cache URLs](#cache-urls)
      - [Do Not Cache URLs](#do-not-cache-urls)
      - [Apps Icons Directory](#apps-icons-directory)
      - [Prefer Native Apps](#prefer-native-apps)
      - [Android App Store Url](#android-app-store-url)
      - [IOS App Store Url](#ios-app-store-url)
    - [Web Share Target](#web-share-target)
      - [Enable Web Share Target](#enable-web-share-target)
      - [Action](#action)
      - [Data Transfer Method](#data-transfer-method)
      - [Encoding Type](#encoding-type)
      - [Use title attribute](#use-title-attribute)
      - [Use text attribute](#use-text-attribute)
      - [Use url attribute](#use-url-attribute)
      - [Enable file sharing](#enable-file-sharing)

    This document explains how to configure the Joomla GZip plugin.

What is the plugin doing?

This extension provides:

- when the client sends [Save-Data header](https://developers.google.com/web/fundamentals/performance/optimizing-content-efficiency/save-data/), optimizations are enforced.
- html, images, javascript and css optimizations (and more) that greatly improve page loading time
- service worker features like transforming your website into a progressive web application, configure an offline page, offline first application, push notifications, background sync, web share target level 2, network strategy and more ...
- improve security by providing configurable HTTP secure headers
- improve security by implementing Content Security Policy level 3 headers
- improve security by adding Sub Resource Integrity tokens to javascript and css files
- profile the plugin performance (or your server performance) by using the HTTP server timing headers

There are loads of settings provided to help you fine tune your website.

## Installing

This plugin has been developed for Joomla version 3 and 4. It requires rewrite rules to be enabled in order to work properly

## Manage Settings

### General Settings

Configure general settings

![General settings](../images/general-settings.PNG)

#### Debug

If yes, load plugin's unminified css and javascript files, otherwise load minified versions.

#### Minify HTML

Enable or disable HTML minification.

### HTTP Settings

Configure HTTP settings

![HTTP settings](../images/http-settings.PNG)

#### Enable Save-Data support

If yes, when the client sends Save-Data headers, optimizations are enforced regardless which settings you have configured. Enforced optimizations are

```
- HTML minification
- Image processing: conversion, size enforcement, resize css images
- CSS: Async loading, css minification, fetching remote css files, critical css settings
- Javascript: merge, minify, fetch remote javascript files
```

#### HTTP Server Timing

Send HTTP Server Timing headers. They can be viewed in the google chrome network panel. Click on the network panel -> Click on your web page -> Click on the timing tab on the right. It should be turned off on production.
ps: this helped me spot a [regression in the image processing](https://github.com/tbela99/gzip/issues/38) that has since been fixed

![Server Timing](../images/Server-Timing.PNG)

#### DNS Prefetch

Enable or disable DNS prefetch

```
- Ignore: let the browser decide
- Enabled: tell the browser to enable DNS prefetch
- Disabled: tell the browser to turn off DNS prefetch
```

#### HTTP Push

Choose which resource type will be pushed to the browser using HTTP push. Your web server need to use HTTP/2 for this feature to work properly.

#### Cache File

If Yes send file with http caching headers. This helps loading the page faster on the next visit because the file will be loaded from the browser cache instead of the network. Whenever a file is modified a new URL is generated so that the user will not see outdated content.

#### Hashing Method

Algorithm chosen when generating cached URL.

```
- Time: use file last modified time
- Etag: use file content instead of last modified time.
```

#### Cache Prefix

Cached URLs prefix.

#### Max Age

Specify how long files are cached by the browser.

#### CORS Headers

If yes send CORS headers when cached files are requested.

#### Custom Mimetypes

Extend the type of files that support HTTP caching. By default the plugin will cache images, javascript, css, fonts, xml. If you want to add pdf and psd files for example you will add this

```
  pdf application/octetstream
  psd application/octetstream
```

#### Custom Attributes

Extend the list of attributes scanned in order to get urls of resources to cache. If you use lazylaoding, you might want to use custom attributes in order to store full size image. You can specify these here like that

```
  data-href
  data-src
  data-srcset
```

### CDN

Configure CDN settings.

This feature allow you to serve files from a different cookieless domain on your server. You create a different domain that point to the same root as your web site.

![CDN settings](../images/cdn-settings.PNG)

#### Enable CDN

Enable or disable the CDN feature.

#### Access Control Allow Origin

Value sent in the *Access Control Allow Origin* HTTP header. The default value is *\**

#### Redirect CDN Request

If a user access your cdn host name directly, he will be redirected to the url you specify here.

#### CDN1, CDN2, CDN3

CDN host names from which files will be loaded.

#### Enable CDN Domain for Custom Resources Type

You can specify which custom resources will be served from your custom CDN. you may for example serve pdf from you cdn by adding this for example

```
  pdf application/octetstream
```

#### Use CDN Domain

Specify Which resource are served from CDN domains.

### Images

Configure images settings.

![Images settings](../images/images-settings.PNG)

#### Process Images

Turn images feature ON or OFF. Alt attribute is also enforced on HTML images when this setting is enabled

#### Enforce Width and Height Attributes

Whether or not enforce width and height attributes for all HTML \<IMG\> tags

#### Fetch Remote Images

Fetch images hosted on remote hosts and store them locally.

#### Convert Images to Webp

Convert images to Webp. Webp produce smaller images than jpg or png.

#### Ignored Images

Images that match any pattern you specify here will be ignored. Example

```
  images/optimized/
  images/thumbnails/
```

#### Crop Method

Algorithm used when resizing images. Values are

```
- Default
- Center
- Entropy
- Face Detection
```

#### Image Placeholder

Whether or not use an image placeholder. **Choosing a placeholder algorithm will enable images lazyloading**. Values are

```
  - None
  - SVG: use an svg image as the placeholder
  - Low Quality Image: generate a low quality image from the picture.
  ```

#### Responsive Images

Enable or disable automatic generation of responsive images. Responsive images are generated for all the breakpoints you select

#### Responsive Image Breakpoints

Selected breakpoints from which responsive images will be generated. The algorithm used is whatever you have specified in the *CROP Method* parameter

#### Responsive CSS background Images

Enable or disable automatic generation of css background images. Responsive css are generated for the css breakpoints you select

#### Responsive CSS Image Breakpoints

Selected breakpoints from which css background responsive images will be generated. The algorithm used is whatever you have specified in the *CROP Method* parameter

### Javascript

Configure Javascript settings.

![Javascript settings](../images/javascript-settings.PNG)

#### Process Javascript

Enable or disable javascript processing

#### Fetch Remotely Hosted Javascript

Fetch javascript files hosted on remote hosts like cdn and store them locally. The local copy will be used instead.

#### Minify Javascript

Minify inline javascript and javascript files

#### Merge Javascript

Enable or disable merging javascript files together

#### Ignore Javascript

If you want to exclude a javascript file from processing, add a pattern to match that file here. For example you might have an obligation to run a file from a remote cdn. Adding it here will reven fetching that file locally

#### Remove Javascript Files

Remove a javascript file from the page. Any file that matches the pattern provided is removed from the page.

### CSS

Configure CSS settings.

![CSS settings](../images/css-settings.PNG)

#### Process CSS

Enable or disable CSS processing

#### Font Display

Set font-display css property. It requires Parse Critical CSS settings must be enabled 

#### Fetch Remotely Hosted CSS

Fetch CSS files hosted on remote servers.

#### Minify CSS

Enable or disable merging css files together

#### Load CSS Async

Asyncronously load css so that page rendering is not blocked while css files are loading

#### Merge CSS

Enable or disable merging css files together

#### Ignored Javacript files

If you want to exclude a css file from processing add a pattern that match that file here.

#### Removed Javacript Files

Remove a css file from the page. Any file that matches the pattern provided is removed from the page

### Critical CSS

Configure Critical CSS path.

![Critical CSS settings](../images/critical-css-settings.PNG)

#### Parse Critical CSS

Enable or disable Critical CSS processing

#### Critical Css Rules

Add CSS that will be included with the extracted critical css. In general the classes you add there should not contain absolute measures but percentage. Otherwise responsive design might be broken.
Example

```css
.container {
 max-width: 100%;   
}
.col-sm-10 {

 width: 10%   
}"
```

#### Critical CSS Selectors

List of css selectors that will be extracted from the page in order to build the critical css.
Example
```css
.nav
header
```

### Service Worker

Configure progressive web application settings

![Service Worker settings](../images/service-worker-settings.PNG)

#### Enable Service Worker

Configure the service worker. Values are

```
- Enabled: Activate the service worker
- Disabled:This will not deactivate the service worker if it is already running in the browser. The service worker initialization script will not be included in the pages.
- Force Uninstall: Actively remove the service worker from the browser
```

#### Debug Service Worker

Enable or disable debug mode in the service worker.

```
Yes: Use unminified files from the service worker. Also log messages in the browser console
No: Use minified files from the service worker. Only error messages are logged into the browser console
```

#### Enable Offline Page

Enable or disable offline page settings. Offline page is shown whenever the browser cannot load the page.

#### Offline Method

Configure which requests are intercepted by the service worker. The service worker will intercept the requests that use the method selected here

#### Prefered Offline Page

Configure which method to use in order to display the offline page. You have two ways of specifying the offline page

```
URL: use 'Offline Page URL' setting
HTML: use 'Offline HTML Page' setting
```

#### Offline page URL

Specify the URL from which the offline page will be loaded

#### Offline HTML Page

Provide HTML content of the offline page

These settings control the service worker manifest file

#### Build Manifest File

Enable or disable the manifest file. The manifest file is used to configure the progressive app settings. For more information about the manifest settings go to this [mdn page](https://developer.mozilla.org/en-US/docs/Web/Manifest)

#### App Short Name

Provide the app short name

#### App Name

Provide the app name

#### App Description

Provide app description

#### Start URL

Provide the URL of the start page of your progressive web application once it is installed

#### Background Color

Provide the background color

#### Theme Color

Provide the app theme color

#### Display Mode

Configure the app display mode

#### Cache URLs

Provide a list of urls available in offline mode.

#### Do Not Cache URLs

Provide a list of urls that will never be cached by the service worker

#### Apps Icons Directory

Select the directory that contains your app icons. The directory must be located under the /images folder

#### Prefer Native Apps

Indicate whether native apps should be preferred over this app

#### Android App Store Url

Android app associated with this app

#### IOS App Store Url

IOS app associated with this app

### Web Share Target

Configure your app to be a target for app data sharing

![Web Share Target settings](../images/web-share-target-settings.PNG)

#### Enable Web Share Target

Enable or disable web share target feature

#### Action

The url where data shared with your app are sent. It can be a relative URL

#### Data Transfer Method

The method used to send data to your action url

#### Encoding Type

The encoding used by the action request. If you enable file transfer then the encoding type is set to multipart/formdata

These fields configure the payload sent to your action url

#### Use title attribute

Enable or disable the title attribute

#### Use text attribute

Enable or disable the text attribute

#### Use url attribute

Enable or disable the url attribute

#### Enable file sharing

Enable or disable file sharing support. Your app may for example receive pictures shared by the users