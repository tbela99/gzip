# Critical CSS

Configure Critical CSS path. Critical CSS enable instant rendering of your web page.

![Critical CSS settings](./img/critical-css-settings.PNG)

## Parse Critical CSS

Enable or disable Critical CSS processing

## Critical Css Rules

Manually provided critical CSS. Add CSS that will be included with the generated critical CSS. In general the classes you add there should not contain absolute measures but only percentage. Otherwise responsive design might be broken.
Example

```css
.container {
  max-width: 100%;
}
.col-sm-10 {
  width: 10%;
}
```

## Critical CSS Selectors

Automatically extracted CSS selectors. the list of css selectors that you provide here will be extracted from the page in order to build the critical css.
Example

```css
.nav,
header
```
