# Joomla Website Optimizer Plugin

![Current version](https://img.shields.io/badge/dynamic/xml?color=green&label=current%20version&query=%2Fextension%2Fversion&url=https%3A%2F%2Fraw.githubusercontent.com%2Ftbela99%2Fgzip%2Fmaster%2Fgzip.xml) [![Documentation](https://img.shields.io/badge/dynamic/xml?color=green&label=documentation&query=%2Fextension%2Fversion&url=https%3A%2F%2Fraw.githubusercontent.com%2Ftbela99%2Fgzip%2Fmaster%2Fgzip.xml)](https://tbela99.github.io/gzip/) [![Known Vulnerabilities](https://snyk.io/test/github/tbela99/gzip/badge.svg)](https://snyk.io/test/github/tbela99/gzip) [![download from JED](https://img.shields.io/badge/download%20from-JED-blueviolet.svg)](https://extensions.joomla.org/extensions/extension/core-enhancements/performance/gzip/)

![logo](./docs/logo.svg)

*_Make your website blazing fast_.*

![screenshot](https://raw.githubusercontent.com/tbela99/gzip/master/Capture.PNG)

- It includes a new Css parser
- Automatic critical path css generation which will bring you close to 100 in [lighthouse](https://developers.google.com/web/tools/lighthouse) test with no effort

Here are some features provided by this plugin

## HTML

- HTML minification
- preserve IE conditional comments

## Javascript

- merge files
- minify files
- remove files based on a pattern
- async loading
- move javascript to the bottom of the page

## CSS

- merge files
- minify files
- remove files based on a pattern
- async loading
- web fonts preloading
- automatic critical css generation

## Images

- automatically resize images
- convert to avif and webp
- generate responsive images
- generate preview images
- lazyloading images
- generate responsive css background images

## Performance

- automatic critical css path generation
- configurable HTTP caching headers
- optimizations are enforced when the client sends [Save-Data HTTP header](https://developers.google.com/web/fundamentals/performance/optimizing-content-efficiency/save-data/)
- dns prefetch
- profile plugin performance using HTTP server timing headers
- precompressed cached files using brotli or gzip.

## Service Worker

- enable service worker
- offline first support
- background sync
- web share target level 2
- web push notifications using One Signal
- immediately update the service worker when the manifest settings change

## Content Security Policy (CSP)

Configure almost every csp level 3 settings to your liking:

- disable inline scripts and css
- block css, js, workers, frames, etc.
- allow css and js from specific origin only
- dynamic csp rules generation from the page content

## Security

- Sub Resource Integrity
- HSTS header configuration
- XSS-PROTECTION header configuration
- X-Frames-Options configuration

## Hotlink Protection

- configure file type that use hotlink protection
- configure link lifetime

The complete list of features is available in the [online documentation](https://tbela99.github.io/gzip/)
and the [change log](./CHANGELOG.md)
