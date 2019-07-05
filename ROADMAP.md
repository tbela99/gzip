# Roadmap

## High priority list 
1.  Run custom code with background sync
2.  merge multiple google font < link > tag
3.  Build scripts using Babel instead of uglify-es
4.  Use ES6+ syntax for scripts used only by the service worker
5.  Clear Joomla cache when settings are updated
6.  Fetch remote resources periodically (configurable) (css, javascript, fonts, ...). This can be usefull for anaytic scripts and and hosted fonts.
7.  Web push notifications with firebase?
8.  prerender images using primitive.js svg generation https://github.com/ondras/primitive.js/blob/master/js/app.js
9.  prerender + [Page Visibility API](http://www.w3.org/TR/page-visibility/): how should prender links be chosen?
10. Messaging API (broadcasting messages to and from all/single clients)
11. Remove < Link rel=preload > http header and use < link > HTML tag instead. see [here](https://jakearchibald.com/2017/h2-push-tougher-than-i-thought/)
12. CSS: deduplicate, merge properties, rewrite rules, etc
13. Disk quota management see [here](https://developer.chrome.com/apps/offline_storage) and [here](https://developer.mozilla.org/fr/docs/Web/API/API_IndexedDB/Browser_storage_limits_and_eviction_criteria) and [here](https://gist.github.com/ebidel/188a513b1cd5e77d4d1453a4b6d060b0)
14. Clear Site Data api see [here](https://www.w3.org/TR/clear-site-data/)

## Low priority list

1. Translation: french?
1.  handle < script nomodule > and < script type=module > ? see [here](https://developers.google.com/web/fundamentals/primers/modules)
1.  Manage the service worker settings from the front end (notify when a new version is available, manually unregister, delete cache, etc ...)?
1.  Manage user push notification subscription from the Joomla backend (link user to his Id, etc ...)?
2.  Provide push notification endpoints (get user Id, notification clicked, notification closed, etc ...)