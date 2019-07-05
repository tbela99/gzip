# Roadmap

## High priority list

1. Offline page: failed navigate requests should return the offline page instead of network error
2. An api to run tasks using Background API?
3. Build scripts using Babel instead of uglify-es
4. Use ES6+ syntax for scripts used only by the service worker
5. Implement backgroundFetch API
6. Clear Joomla cache when settings are updated
7. Fetch remote resources periodically (configurable) (css, javascript, fonts, ...). This can be usefull for anaytic scripts and and hosted fonts.
8. Web push notifications with firebase?
9. prerender images using primitive.js svg generation [https://github.com/ondras/primitive.js/blob/master/js/app.js](https://github.com/ondras/primitive.js/blob/master/js/app.js)
10. prerender + [Page Visibility API](http://www.w3.org/TR/page-visibility/): how should prender links be chosen?
11. Messaging API (broadcasting messages to and from all/single clients)
12. Remove < Link rel=preload > http header and use < link > HTML tag instead. see [here](https://jakearchibald.com/2017/h2-push-tougher-than-i-thought/)
13. CSS: deduplicate, merge properties, rewrite rules, etc
14. Disk quota management see [here](https://developer.chrome.com/apps/offline_storage) and [here](https://developer.mozilla.org/fr/docs/Web/API/API_IndexedDB/Browser_storage_limits_and_eviction_criteria) and [here](https://gist.github.com/ebidel/188a513b1cd5e77d4d1453a4b6d060b0)
15. Clear Site Data api see [here](https://www.w3.org/TR/clear-site-data/)

## Low priority list

1. handle < script nomodule > and < script type=module > ? see [here](https://developers.google.com/web/fundamentals/primers/modules)
1. Manage the service worker settings from the front end (notify when a new version is available, manually unregister, delete cache, etc ...)?
1. Manage user push notification subscription from the Joomla backend (link user to his Id, etc ...)?
1. Provide push notification endpoints (get user Id, notification clicked, notification closed, etc ...)
1. PWA: Deep links in pwa app or website. see [here](http://blog.teamtreehouse.com/registering-protocol-handlers-web-applications) and [here](https://developer.mozilla.org/en-US/docs/Web-based_protocol_handlers)
