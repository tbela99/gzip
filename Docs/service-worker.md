# Service Worker

Configure progressive web application settings

![Service Worker settings](../images/service-worker-settings.PNG)

## Enable Service Worker

Configure the service worker. Values are

- Enabled: Activate the service worker
- Disabled:This will not deactivate the service worker if it is already running in the browser. The service worker initialization script will not be included in the pages.
- Force Uninstall: Actively remove the service worker from the browser

## Debug Service Worker

Enable or disable debug mode in the service worker.

- Yes: Use unminified files from the service worker. Also log messages in the browser console
- No: Use minified files from the service worker. Only error messages are logged into the browser console

## Enable Offline Page

Enable or disable offline page settings. Offline page is shown whenever the browser cannot load the page.

## Offline Method

Configure which requests are intercepted by the service worker. The service worker will intercept the requests that use the method selected here

## Prefered Offline Page

Configure which method to use in order to display the offline page. You have two ways of specifying the offline page

- URL: use 'Offline Page URL' setting
- HTML: use 'Offline HTML Page' setting

## Offline page URL

Specify the URL from which the offline page will be loaded

## Offline HTML Page

Provide HTML content of the offline page

These settings control the service worker manifest file

## Build Manifest File

Enable or disable the manifest file. The manifest file is used to configure the progressive app settings. For more information about the manifest settings go to this [mdn page](https://developer.mozilla.org/en-US/docs/Web/Manifest)

## App Short Name

Provide the app short name

## App Name

Provide the app name

## App Description

Provide app description

## Start URL

Provide the URL of the start page of your progressive web application once it is installed

## Background Color

Provide the background color

## Theme Color

Provide the app theme color

## Display Mode

Configure the app display mode

## Cache URLs

Provide a list of urls available in offline mode.

## Do Not Cache URLs

Provide a list of urls that will never be cached by the service worker

## Apps Icons Directory

Select the directory that contains your app icons. The directory must be located under the /images folder

## Prefer Native Apps

Indicate whether native apps should be preferred over this app

## Android App Store Url

Android app associated with this app

## IOS App Store Url

IOS app associated with this app
