# Roadmap

## High priority list

1. [bug] When fetch remote scripts is off and a local copy is hosted, the local copy should not be used
2. [feature] Validate integrity data when the attribute is provided for link and scriptbefore minifying / merging them together
3. Better detect character encoding when amnually editing the offline page HTML
4. Evaluate page instant loading
5. Implement manual link prefetch
6. Server timing headers
7. LQIP broken in google chrome (they ahev removed support for something I think)
8. implement csp features
9. add support for the user action Log see [here](https://docs.joomla.org/J3.x:User_Action_Logs)
10. do not cache partial request
11. intercept partial requests and return response from cache
12. merge multiple google font < link > tag
13. Build scripts using Babel instead of uglify-es
14. Use ES6+ syntax for scripts used only by the service worker
15. Implement backgroundFetch API
16. Fetch remote resources periodically (configurable) (css, javascript, fonts, ...). This can be usefull for anaytic scripts and and hosted fonts.
17. Web push notifications with firebase?
18. prerender images using primitive.js svg generation [https://github.com/ondras/primitive.js/blob/master/js/app.js](https://github.com/ondras/primitive.js/blob/master/js/app.js)
19. prerender + [Page Visibility API](http://www.w3.org/TR/page-visibility/): how should prender links be chosen?
20. Messaging API (broadcasting messages to and from all/single clients)
21. Remove < Link rel=preload > http header and use < link > HTML tag instead. see [here](https://jakearchibald.com/2017/h2-push-tougher-than-i-thought/)
22. CSS: deduplicate, merge properties, rewrite rules, etc
23. Disk quota management see [here](https://developer.chrome.com/apps/offline_storage) and [here](https://developer.mozilla.org/fr/docs/Web/API/API_IndexedDB/Browser_storage_limits_and_eviction_criteria) and [here](https://gist.github.com/ebidel/188a513b1cd5e77d4d1453a4b6d060b0)
24. Clear Site Data api see [here](https://www.w3.org/TR/clear-site-data/)

## Low priority list

1. handle < script nomodule > and < script type=module > ? see [here](https://developers.google.com/web/fundamentals/primers/modules)
1. Manage the service worker settings from the front end (notify when a new version is available, manually unregister, delete cache, etc ...)?
1. Manage user push notification subscription from the Joomla backend (link user to his Id, etc ...)?
1. Provide push notification endpoints (get user Id, notification clicked, notification closed, etc ...)
1. PWA: Deep links in pwa app or website. see [here](http://blog.teamtreehouse.com/registering-protocol-handlers-web-applications) and [here](https://developer.mozilla.org/en-US/docs/Web-based_protocol_handlers)
