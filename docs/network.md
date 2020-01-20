# Network Settings

Configure the service worker network strategies. You can customize different network strategies for different resource types

![Network settings](./img/network-settings.PNG)

Network strategies define how the service worker should handle network requests. Implemented strategies are

- Use Default: Use the value configured as **Default Network Startegy**
- None: do not intercept network requests
- Network and Cache Fallback: attempt to load resource from the network. It the request fails attempt to load it from the cache
- Cache and Network Fallback: if the resource does not exist in the cache, load it from the network and put it in the cache. otherwise always load it from the cache
- Cache and Network Update: load the resource from the cache, fetch and cache a newer copy in the background
- Cache Only: load the resource from the cache. No network request will be attempted

## Default Network Strategy

Define the default network strategy

## Images

Defines the network strategy used for images

## Javascript

Defines the network strategy used for javascript files

## CSS

Defines the network strategy used for css files

## Fonts

Defines the network strategy used for fonts

## HTML Documents

Defines the network strategy used for html documents
