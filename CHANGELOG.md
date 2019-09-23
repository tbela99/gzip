# Change History

## 2.7.0-dev

- [ ] modify service worker cache and network fallback and cache only behavior
- [ ] Better detect character encoding when manually editing the offline page HTML
- [ ] Implement instant page loading
- [ ] Implement backgroundFetch API
- [ ] use configuration files to build scripts instead of shell commands
- [ ] missing translation bug good first issue
- [ ] improved LQIP for jpeg files
 
## 2.6.1-dev
 
- Add documentation
  
## 2.6.0

- partly cache security headers
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
