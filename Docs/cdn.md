# CDN

Configure CDN settings.

This feature allow you to serve files from a different cookieless domain on your server. You create a different domain that point to the same root as your web site.

![CDN settings](./img/cdn-settings.PNG)

## Enable CDN

Enable or disable the CDN feature.

## Access Control Allow Origin

Value sent in the _Access Control Allow Origin_ HTTP header. The default value is \*\*\*

## Redirect CDN Request

If a user access your cdn host name directly, he will be redirected to the url you specify here.

## CDN1, CDN2, CDN3

CDN host names from which files will be loaded.

## Enable CDN Domain for Custom Resources Type

You can specify which custom resources will be served from your custom CDN. you may for example serve pdf from you cdn by adding this for example

```markdown
pdf application/octetstream
```

## Use CDN Domain

Specify which resource are served from CDN domains.
