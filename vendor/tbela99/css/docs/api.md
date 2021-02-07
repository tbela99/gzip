## CSS Api

The documentation generated from the source code using phpdocs is available [here](https://htmlpreview.github.io/?https://raw.githubusercontent.com/tbela99/css/master/docs/api/html/index.html)

## Rendering an Element

Every Element instance implement a \_\_toString() method which means they are automatically converted to string where a string is expected.
However you can control how the element is rendered by using a _Renderer_.
The renderer has a _setOptions_ method that accepts the same arguments as [\TBela\CSS\Compiler::setOptions](./compiler.md#compiler-options)

### Pretty printing CSS

Elements are rendered by default using Pretty printing.

Example

```css
@media print {
  @font-face {
    font-family: Arial, MaHelvetica;
    src: local("Helvetica Neue Bold"), local("HelveticaNeue-Bold"), url(MgOpenModernaBold.ttf);
    font-weight: bold;
  }
}
```

PHP
```php

use \TBela\CSS\Parser;
use \TBela\CSS\Renderer;

$parser = new Parser();
$renderer = new Renderer();

$parser->setContent($css);

$stylesheet = $parser->parse();

// get @font-face element
$media = $stylesheet['firstChild'];
$fontFace = $media['firstChild'];
```

Render the element alone

```php
echo $renderer->render($fontFace);
```

css output

```css
@font-face {
  font-family: Arial, MaHelvetica;
  src: local("Helvetica Neue Bold"), local("HelveticaNeue-Bold"),
    url(MgOpenModernaBold.ttf);
  font-weight: bold;
}
```

render the element with its parents

```php
echo $renderer->render($fontFace, null, true);
```

Css output

```css
@media print {
  @font-face {
    font-family: Arial, MaHelvetica;
    src: local("Helvetica Neue Bold"), local("HelveticaNeue-Bold"),
      url(MgOpenModernaBold.ttf);
    font-weight: bold;
  }
}
```

### Minify CSS printing

There are additional settings to control minification.

- _compress_: true/false. Enable minification
- _css_level_: 4 or 3. Convert color using the specified CSS level syntax
- _convert_color_: _boolean_ | _string_ Convert colors to a format between _hex_, _hsl_, _rgb_, _hwb_ and _device-cmyk_. if set to false then no conversion is performed. default to _hex_.
if you want to convert hsla and rgba like colors to hex, to must set css_level to 4 otherwise they will be converted to rgba


Example

```css
@media print {
  @font-face {
    font-family: Arial, MaHelvetica;
    src: local("Helvetica Neue Bold"), local("HelveticaNeue-Bold"),
      url(MgOpenModernaBold.ttf);
    font-weight: bold;
  }
}
```

```php

use \TBela\CSS\Compiler;
use \TBela\CSS\Renderer;

$parser = new Parser();
$renderer = new Renderer();

// convert rgba to hex, not required here
$renderer->setOptions(['convert_color' => 'hex', 'compress' => true]);

$parser->setContent($css);

$stylesheet = $parser->parse();

// get @font-face element
$media = $stylesheet['firstChild'];
$fontFace = $media['firstChild'];
```

Render the element alone

```php
echo $renderer->render($fontFace);
```

css output

```css
@font-face{font-family:Arial,MaHelvetica;src:local("Helvetica Neue Bold"),local("HelveticaNeue-Bold"),url(MgOpenModernaBold.ttf);font-weight:bold}
```

render the element with its parents

```php
echo $renderer->render($fontFace, null, true);
```

Css output

```css
@media print{@font-face{font-family:Arial,MaHelvetica;src:local("Helvetica Neue Bold"),local("HelveticaNeue-Bold"),url(MgOpenModernaBold.ttf);font-weight:bold}}
```
