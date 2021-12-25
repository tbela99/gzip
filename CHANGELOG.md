# Change History

# 3.0

- #84 Automatically generate critical css path. no more parameter are required
- #138 AVIF image support
- changed LQIP blur value #b188238
- documentation update

# 2.11.0

- @cbahiana-sd Added info on enabling zlib.output_compression 495b84d
- @cbahiana-sd #132 Changing {$r} for [$r] at line 650
- @tbela99 #135 Correcting typo on PLG_GZIP_FIELD_PWA_APP_URL_DESCRIPTION
- @tbela99 #111 replace javascript minifier with an ES6+ compatible alternative
- @tbela99 Merge pull request #140 from tbela99/javascript_minifier 39a7e38
- @tbela99 #137 compute integrity even when HTTP caching is off
- @tbela99 #142 add missing font-display from extracted font-face rules
- @tbela99 #144 cannot edit Joomla settings
- @tbela99 #147 fix css parser fatal error
- @tbela99 #148 fix warning
- @tbela99 #149 fix Joomla javascript error

## 2.9.3

- Do not remove quotes when some characters are present #126
- Fix notice when parse inline CSS is enabled #105
- Update version number #124
- Add missing onesignal javascript #125

## 2.9.2

fix bug that prevent installation

## 2.9.1

fix bug that prevent installation

## 2.8.1

- Fix empty service worker files #102

## 2.8.0

- Add \.ico as a supported PWA icon format #78
- Add purpose property to the PWA manifest options #78
- Add screenshots property to the PWA manifest options #78- remove duplicated path the file name #88
- change default pwa display to _standalone_ #78
- \[performance\] precompress cache files. If the client advertises compression support, then send the compressed file. Brotli support requires the brotli extension #89
- optionally preserve IE conditional comments #88
- remove the length restriction of the manifest short_name #78
- fix security and performance issues induced by links with _target=\_blank_ attribute #91
- asynchronously initialize the service worker using \<script defer\> #78
- Link preloading : preload only once #99
- \[bug\] fix invalid configuration path #94
- \[bug\] the minification produced invalid HTML when the HTML provided had missing space between attributes #88
- \[bug\] the protocol is removed in the HTML content instead of href|src attributes only
- \[bug\] fix an uncaught javascript error in the service worker js #78
- \[bug\] fix 404 error when Hotlink protection is ON and cache files is OFF #78i
- \[bug\] fix ignored javascript are removed from the page #96

## 2.7.3

- change Upgrade-Insecure-Requests to be used as a CSP setting instead of an HTTP header
- update the documentation
- add CHANGELOG.md, LICENSE.md and docs folder to the installer
- fix invalid HTML caused by unescaped characters in Joomla print button
- fix incorrectly handled utf8 file names
- fix terser config to preserve global vars
- improve hotlink protection performance by caching data
- more accurate server timing headers

## 2.7.2

- remove extra quote at the end of filename

## 2.7.1

- fix case in folder name that prevent the plugin installation on Linux

## 2.7.0

- new documentation using docsify
- better character encoding detection of the offline HTML page
- javascript files are now built using terser instead of uglifyjs
- instant page loading
- missing translation added
- hotlink protection
- better detect character encoding when manually editing the offline page HTML
- Implement instant page loading
- Replace ugify-es with terser. Use configuration files to build scripts instead of shell commands
- missing translation added
- Access-Control-Allow-Origin is always set to '\*'
- Create links to resources that expire after a given time
- \[Bug\] Missed cache lead to performance issue when parse critical css returns an empty string

## 2.6.1

- Add documentation

## 2.6.0

- partly cache security
- add csp level 3 headers
- add nonce or hash to support inline script and style
- fix a regression with images lazy loading in google chrome
- add configurable secure headers (X-Frame-Options, Strict-Transport-Security, X-Content-Type-Options, XSS-Protection)
- add html minification
- Ignore unsupported images types when optimizing images
- add server timing headers for server side performance profiling
- enforce optimization when [Save-Data header](https://developers.google.com/web/fundamentals/performance/optimizing-content-efficiency/save-data/) is sent
- add badges for snyk, current version number and a link to the JED
- add state-while-revalidate to Cache-Control header
- fix compatibility issues with Joomla 4
- fix performance regression when critical css feature or resize css image are enabled
- expose header date to the service worker

## V2.5.4

- add the possibility to provide the html for the offline page as an alternative to providing an url
- add support of stale-while-revalidate in Cache-Control header
- add missing administrator service worker client file

## V2.5.3

- Limit the number of files cached
- Limit the size of files that can be cached
- Cache settings do not apply to precached resources
- Fix issue that prevented to apply settings on cached resources based of the file type
- Add a switch to disable CSS processing
- Add a switch to disable Javascript processing

## V2.5.2

- Added the possibility to display an offline page when a navigation page request fails.

## V2.5.1

- Implement background sync with fallback
- Build scripts using rollup instead of webpack. This produce smaller output.

## V2.5.0

- Implement Web Share Target api level 2

## V2.4.2

- Fix file not found error
- Add the possibility to ignore images based on a pattern

## V2.4.1

- Load images using LQIP technique
- Add a service worker for administrator with no caching because admin requests were still cached by the website service worker
- Make lazyloaded images indexable using noscript tag
- force file name generation whenever the settings are changed
- Add new breakpoint 1920px for responsive images and css background images

## V2.4

- Customize max-age value for cached resources
- remove Expires header in favor of max-age
- Service worker cache expiration api
- Define cache expiration rule per file type
- Add missing files to the git repo

## V2.3

- Web fonts preloading: Choose how the text is rendered while web fonts are loading by customizing font-display
- Enable CDN / cookieless domain support
- Enable CORS headers for cached resources
- The service worker is able to intercept CDN files as long as they are sent with CORS headers
- Access to the website through CDN / cookieless domain can be redirected to a custom domain
- Extend the list of file type supported by the cdn or cookieless domain
- Extend the list of file type supported by the url rewrite feature
- Add a third option for service worker (disable, enable, force removal).
- Configure service worker route strategy per resource type from the Joomla administrator
- Implement the beforeinstallprompt event. see [here](https://w3c.github.io/manifest/#beforeinstallpromptevent-interface)

## V2.2

- optimized image lazyloader
- generate svg placeholder from images for quick preview
- resize css images for mobile / tablet
- IMAGES: Implement progressive images loading with intersectionObserver
- remove '+' '=' and ',' from the hash generation alphabet
- Responsive images: resize images using breakpoints and leverage < img srcset >
- serve webp whenever the browser/webserver (using gd) supports it
- Disabling service worker will actually uninstall it
- Server Timing Header see [here](https://w3c.github.io/server-timing/#examples)
- automatic preconnect < link > added, web fonts preload moved closer to < head > for faster font load
- Add < link > with < noscript > when async css loading is enabled. without javascript, stylesheet were not previously rendered.

## V2.1

- Added push notifications using onesignal
- Added pwa manifest. The app is installable as a standalone application (tested on google chrome/android é windows 10 / firefox android)
- Precached urls list. You can now provide a list of urls that will be precached when the service worker is installed.
- Added router api. Add routes to customize fetch event networking strategy by using either a static route or a regexp
- Rebuild service worker and the manifest whenever the plugin is installed or the settings are updated
- Override meta name=generator with custom text
- Add a secret token to prevent administrator access
- Insert scripts and css that have 'data-position="head"' attribute in head instead of the body

## V2.0

PWA: implemented network strategies:

- Cache only
- Network only
- Cache first, falling back to network
- Network first, falling back to cache
- Cache, with network update

## V1.1

CSS: preload web fonts

## V1.0

this release implements to to bottom page loading optimization

SRI (Sub resources integrity)

generate SRI for javascript and css files

Critical CSS Path

generate critical css path based on the list of selectors you provide.

Javascript

- fetch files hosted on remote servers
- minify javascript files
- merge javascript files
- minify inline javascript
- ignore files based on pattern
- remove javascript files that match a pattern
- remove duplicates
- move javascript at the bottom of the page

### CSS

- fetch files hosted on remote servers
- minify css files
- merge css files (flatten @import)
- minify inline css
- ignore files based on pattern
- remove css files that match a pattern
- remove duplicates
- move css at the bottom of the page
- load css in a non blocking way
