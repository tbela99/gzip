# Web Share Target

Web Share Target Level 2 is the web standard that allow your app to receive data shared by other apps.

![Web Share Target](./img/web-share-target.jpg)

## General Settings

![Web Share Target settings](./img/web-share-target-settings.PNG)

### Enable Web Share Target

Enable or disable web share target feature

### Action

The url where data shared with your app are sent. It can be a relative URL

```text
/post/web-share
```

### Data Transfer Method

_GET | POST_, The method used to send data to your action url

### Encoding Type

The encoding used by the action request. If you enable file transfer then the encoding type is set to multipart/formdata

## Payload Settings

These fields configure the payload sent to your action url

### Use title attribute

Enable or disable the title attribute

### Use text attribute

Enable or disable the text attribute

### Use url attribute

Enable or disable the url attribute

### Enable file sharing

Enable file sharing support allow your app to receive pictures shared by the users from their mobile phone or tablet

### Title

Rename the title field to a name suitable to your application

### Text

Rename the text field to a name suitable to your application

### Url

Rename the url field to a name suitable to your application

### File Type Metadata

A JSON metadata defining which files are accepted by your application. This example show an example of configuration that accepts both csv and jpeg files

```json
[
  { "name": "records", "accept": ["text/csv", ".csv"] },
  { "name": "image", "accept": "image/jpg" }
]
```
