
# Roadmap

## High priority list

1.  Enable CDN / cookieless domain support 
1.  Use blurry image preview as an alternative to SVG
1.  Add a thrid option for service worker (disable, enable, force removal). right now enable is handled as "force removal"
1.  Fetch remote resources periodically (configurable) (css and javascript). This can be usefull for anaytic scripts and and hosted fonts. right now they are updated only once and forever
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

1.  Manage the service worker settings from the front end (notify when a new version is available, manually unregister, delete cache, etc ...)?
1.  Manage user push notification subscription from the Joomla backend (link user to his Id, etc ...)?
1.  Provide push notification endpoints (get user Id, notification clicked, notification closed, etc ...)
1.  Mobile apps deep link?
1.  PWA: Deep links in pwa app or website. see [here](http://blog.teamtreehouse.com/registering-protocol-handlers-web-applications) and [here](https://developer.mozilla.org/en-US/docs/Web-based_protocol_handlers)
