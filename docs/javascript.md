# Javascript

Configure Javascript settings: optimizing, merging and automatic async/defer of javascript files

![Javascript settings](./img/javascript-settings.PNG)

## General Settings

### Process Javascript

Enable or disable javascript processing. By default javascript will be moved at the bottom of the page if this setting is enabled. You can force a specific script to appear in \<head\> section or to be ignored by adding a custom attribute to the html tag.
Custom attributes are:

- data-position: set it to _'head'_ in order to keep the script in the \<head\> section
- data-ignore: do not treat script when this attribute is set

Example: force a script to appear in \<head\>

```html 
<!-- Google Analytics -->
<script data-position="head">
(function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
(i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
})(window,document,'script','https://www.google-analytics.com/analytics.js','ga');

ga('create', 'UA-XXXXX-Y', 'auto');
ga('send', 'pageview');
</script>
<!-- End Google Analytics -->

```

Example: force a script be ignored

```html 
<!-- Google Analytics -->
<script data-ignore="true">
(function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
(i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
})(window,document,'script','https://www.google-analytics.com/analytics.js','ga');

ga('create', 'UA-XXXXX-Y', 'auto');
ga('send', 'pageview');
</script>
<!-- End Google Analytics -->

```

### Fetch Remotely Hosted Javascript

Fetch javascript files hosted on remote hosts like cdn and store them locally. The local copy will be used instead.

### Ignore Javascript

If you want to exclude a javascript file from processing, add a pattern to match that file here. 
This script may still be moved to the bottom of the page if you did not set a custom attribute to either ignore it or move if the the head

### Remove Javascript Files

Remove a javascript file from the page. Any file that matches the pattern provided is removed from the page. This is useful when you want to remove scripts that are automatically added for example

## Javascript Optimization Settings

### Minify Javascript

Minify inline javascript and javascript files

### Merge Javascript

Enable or disable merging javascript files together
