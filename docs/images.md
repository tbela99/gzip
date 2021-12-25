# Images

Configure images settings: automatic conversion, resizing, lazyloading and responsive images generation

![Images settings](./img/images-settings.PNG)

## Requirement

Image processing requires the gd library

## General Settings

### Process Images

Turn images feature ON or OFF. Alt attribute is also enforced on HTML images when this setting is enabled

### Enforce Width and Height Attributes

Whether or not enforce width and height attributes for all HTML \<IMG\> tags

### Fetch Remote Images

Fetch images hosted on remote hosts and store them locally. This will allow you to apply further optimizations.

### Ignored Images

Images that match any pattern you specify here will be ignored. Example: ignore images that contain _/images/optimized-files/_ in their path

```txt 
/images/optimized-files/
```
## Image Optimization Settings

### Convert Images to Webp

Convert images to Avif and Webp when this format is supported by the HTTP client.
Avif requires PHP >= 8.1 and GD support

### Convert Inline Background

Convert background images defined in HTML 'style' attribute

### Crop Method

Algorithm used when resizing images. Values are:

#### Default
.

#### Center

 Crop the image from its center
 
#### Entropy

.

#### Face Detection

try to crop the region captured by the face detection algorithm

### Image Placeholder

Whether or not use an image placeholder. **Choosing a placeholder algorithm will enable images lazyloading**. Values are

- None: disable lazyloading
- SVG: use an svg image as the placeholder
- Low Quality Image: generate a low quality image from the picture.

## Responsive Images Settings

### Responsive Images

Enable or disable automatic generation of responsive images. Responsive images are generated for the selected css breakpoints

### Responsive Image Breakpoints

Selected breakpoints from which responsive images will be generated. The algorithm used is whatever you have specified in the _CROP Method_ parameter

### Responsive CSS background Images

Enable or disable automatic generation of css background images. Responsive css are generated for the selected css breakpoints you select

### Responsive CSS Image Breakpoints

Selected breakpoints from which css background responsive images will be generated. The algorithm used is whatever you have specified in the _CROP Method_ parameter
