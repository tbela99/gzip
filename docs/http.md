# HTTP Settings

## Configure HTTP settings

![HTTP settings](./img/http-settings.PNG)

### Enable Save-Data support

If yes, when the client sends Save-Data headers, optimizations are enforced regardless which settings you have configured. Enforced optimizations are

- HTML minification
- Image processing: conversion, size enforcement, resize css images
- CSS: Async loading, css minification, fetching remote css files, critical css settings
- Javascript: merge, minify, fetch remote javascript files

### HTTP Server Timing

Send HTTP Server Timing headers. They can be viewed in the google chrome network panel. Click on the network panel -> Click on your web page -> Click on the timing tab on the right. It should be turned off on production.
ps: this helped me spot a [regression in the image processing](https://github.com/tbela99/gzip/issues/38) that has since been fixed

![Server Timing](./images/Server-Timing.PNG)

### DNS Prefetch

Enable or disable DNS prefetch

- Ignore: let the browser decide
- Enabled: tell the browser to enable DNS prefetch
- Disabled: tell the browser to turn off DNS prefetch

## HTTP Push Settings

### HTTP Push

Choose which resource type will be pushed to the browser using HTTP push. Your web server need to use HTTP/2 for this feature to work properly.

## HTTP Compression Settings

### Precompress Files

If Yes store a compressed copy of the file in the cache. This will improve performance because files are compressed once and reused. Supported compressed algorithms are GZIP and Brotli. Brotli support requires the PHP Brotli extension. Brotli is only available with HTTPS by design

### Minimum File Size

Will not attempt to compress and store copy of files smaller than the given size. Set to _0_ to ignore this setting

### Maximum File Size

Will not attempt to compress and store copy of files larger than the given size. Set to _0_ to ignore this setting

## HTTP Cache Settings

### Cache File

If Yes send file with http caching headers. This helps loading the page faster on the next visit because the file will be loaded from the browser cache instead of the network. Whenever a file is modified a new URL is generated so that the user will not see outdated content.

### Hashing Method

Algorithm chosen when generating cached URL.

- Time: use file last modified time
- Etag: use file content instead of last modified time.

### Cache Prefix

Cached URLs prefix.

### Max Age

Control HTTP caching. If the value is large enough, the files are stored in the browser cache.

## Misc Settings

### CORS Headers

If yes send CORS headers when cached files are requested.

### Custom Mimetypes

Extend the type of files that support HTTP caching. By default the plugin will cache images, javascript, css, fonts, xml. If you want to add pdf and psd files for example you will add this

```markdown
pdf application/octetstream
psd application/octetstream
```

### Custom Attributes

Extend the list of attributes scanned in order to get urls of resources to cache. If you use lazylaoding, you might want to use custom attributes in order to store full size image. You can specify these here like that

```markdown
data-href
data-src
data-srcset
```
