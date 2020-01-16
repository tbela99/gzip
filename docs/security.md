# Security

Configure security features

![Security settings](./img/security-settings.PNG)

Configure security settings such as XSS protection or HSTS

## Subresource Integrity Checksum (SRI)

Prevent script and css tampering by adding a signature to the HTML tag. Values are

- None: do not set the SRI
- SHA256: compute SRI using SHA256
- SHA384: compute SRI using SHA384
- SHA512: compute SRI using SHA512

## HSTS (HTTP Strict-Transport-Security)

Tell the browser it should only use https

### Maxage

HSTS header lifetime

### Include Subdomains

apply HSTS settings to subdomains

### Use HSTS Preload

Make your web site available with https only in some browsers. For more information, please see [https://hstspreload.org/](https://hstspreload.org/)

## X-Frames-Options Settings

### X-Frames-Options

Configure the X-Frames-Options header. Values are

- None: do not override the header
- Deny: set the value to DENY
- Same Origin: Set the value to SAME_ORIGIN
- Allow From an Origin: allow frame inclusion from the url provided in _Allow from URI_ setting

### Allow from URI

Allow your website to be embedded from this a specific URI. X-Frame-Options value must be set to _'Allow from an origin'_ for this to work

## Other HTTP headers

### Upgrade-Insecure-Requests

Tell the client it should use https instead of http

### X-Content-Type-Options

Configure X-Content-Type-Options header. Values are

- None: do not send the header
- No Sniff: The client should not attempt to guess the content mime type

### XSS-Protection

Configure XSS-Protection header. Values are

- None: do not override the header
- Disable: do not send the header
- Filter: enable XSS-Protection filtering. The browser will sanitize the page
- Block: enable XSS-Protection filtering. The browser will block page rendering if anattack is detected
- Block and Report: enable XSS-Protection filtering. The browser will sanitize the page and report the violation to the URI configured with _XSS Report URL_ setting

## Misc

### Meta Generator

Change the value of the meta generator header to a custom value

### Admin Area Secret

Configure the secret token used to access the Joomla administrator. For example if you configure _secret123_ as your secret token, you will need to access your joomla administrator by adding _?secret123_ at the end of the url

```http
https://www.mywebsite.com/administrator/?secret123
```
