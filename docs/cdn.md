# CDN

Configure CDN settings.

This feature allow you to serve files from a different cookieless domain on your server. 
You create a different domain that point to the same root as your web site and that is intended to serve only resources from your website.
Serving assets from a different domain can speed up your website [loading time](https://www.google.com/search?q=serve+static+assets+from+cdn)
You should not use this feature if you already use a CDN. You can configure up to 3 domains from which resources will be served.

![CDN settings](./img/cdn-settings.PNG)

## Enable CDN

Enable or disable the CDN feature.

## Access Control Allow Origin

Configure _Access Control Allow Origin_ HTTP header. The default value is _'\*'_

## Redirect CDN Request

If a user access your cdn host name directly, he will be redirected to the url you specify here. This should be your main website URL

## CDN1, CDN2, CDN3

CDN host names from which files will be served.

## Use CDN Domain

Specify which resource are served from CDN domains.

## Enable CDN Domain for Custom Resources Type

You can specify which additional resources that will be served from your custom CDN by specifying their MIME type. You may for example serve pdf from you cdn by adding this for example

```markdown
pdf application/octetstream
zip application/zip
```
