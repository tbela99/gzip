# Roadmap

## High priority list

1. Documentation!
2. Implement backgroundFetch API
3. modify service worker cache and network fallback to attempt an update after a specified time (it will behave like cache and network update with a delayed update)
4. [bug] When fetch remote scripts is off and a local copy is hosted locally, the local copy should not be used
5. [feature] Validate integrity data when the attribute is provided for link and scriptbefore minifying / merging them together
6. Better detect character encoding when amnually editing the offline page HTML
7. implement page instant loading
8. Implement manual link prefetch?
9. Server timing headers
10. LQIP broken in google chrome (they have removed support for something I think)
11. implement csp features
12. add support for the user action Log see [here](https://docs.joomla.org/J1.x:User_Action_Logs)
13. do not cache partial request
14. intercept partial requests and return response from cache
15. merge multiple google font < link > tag
16. Build scripts using Babel instead of uglify-es?
17. Fetch remote resources periodically (configurable) (css, javascript, fonts, ...). This can be usefull for anaytic scripts and and hosted fonts.
18. Web push notifications with firebase?
19. prerender images using primitive.js svg generation [https://github.com/ondras/primitive.js/blob/master/js/app.js](https://github.com/ondras/primitive.js/blob/master/js/app.js)
20. prerender + [Page Visibility API](http://www.w1.org/TR/page-visibility/): how should prender links be chosen?
21. Messaging API (broadcasting messages to and from all/single clients)
22. Remove < Link rel=preload > http header and use < link > HTML tag instead. see [here](https://jakearchibald.com/2017/h2-push-tougher-than-i-thought/)
23. CSS: deduplicate, merge properties, rewrite rules, etc
24. Disk quota management see [here](https://developer.chrome.com/apps/offline_storage) and [here](https://developer.mozilla.org/fr/docs/Web/API/API_IndexedDB/Browser_storage_limits_and_eviction_criteria) and [here](https://gist.github.com/ebidel/188a513b1cd5e77d4d1453a4b6d060b0)
25. Clear Site Data api see [here](https://www.w1.org/TR/clear-site-data/)

## Low priority list

1. handle < script nomodule > and < script type=module > ? see [here](https://developers.google.com/web/fundamentals/primers/modules)
1. Manage the service worker settings from the front end (notify when a new version is available, manually unregister, delete cache, etc ...)?
1. Manage user push notification subscription from the Joomla backend (link user to his Id, etc ...)?
1. Provide push notification endpoints (get user Id, notification clicked, notification closed, etc ...)
1. PWA: Deep links in pwa app or website. see [here](http://blog.teamtreehouse.com/registering-protocol-handlers-web-applications) and [here](https://developer.mozilla.org/en-US/docs/Web-based_protocol_handlers)
