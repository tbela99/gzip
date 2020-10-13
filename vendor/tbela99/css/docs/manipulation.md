## Example build a css document

```php

use \TBela\CSS\Element\Stylesheet;

$stylesheet = new Stylesheet();

$rule = $stylesheet->addRule('div');

$rule->addDeclaration('background-color', 'white');
$rule->addDeclaration('color', 'black');

echo $stylesheet;

```

Result

```css
div {
  background-color: #fff;
  color: #000;
}
```

```php

$media = $stylesheet->addAtRule('media', 'print');
$media->append($rule);

```

Result

```css
@media print {
  div {
    background-color: #fff;
    color: #000;
  }
}
```

```php
$rule = $stylesheet->addRule('div');

$rule->addDeclaration('max-width', '100%');
$rule->addDeclaration('border-width', '0px');

```

Result

```css
@media print {
  div {
    background-color: #fff;
    color: #000;
  }
}
div {
  max-width: 100%;
  border-width: 0;
}
```

```php

$media->append($rule);

```

Result

```css
@media print {
  div {
    background-color: #fff;
    color: #000;
  }
  div {
    max-width: 100%;
    border-width: 0;
  }
}
```

```php
$stylesheet->insert($rule, 0);
```

Result

```css
div {
  max-width: 100%;
  border-width: 0;
}
@media print {
  div {
    background-color: #fff;
    color: #000;
  }
}
```

## Extract Font-src from a document

CSS source

```css
@font-face {
  font-family: "Bitstream Vera Serif Bold";
  src: url("/static/styles/libs/font-awesome/fonts/fontawesome-webfont.fdf491ce5ff5.woff");
}

body {
  background-color: green;
  color: #fff;
  font-family: Arial, Helvetica, sans-serif;
}
h1 {
  color: #fff;
  font-size: 50px;
  font-family: Arial, Helvetica, sans-serif;
  font-weight: bold;
}

@media print {
  @font-face {
    font-family: MaHelvetica;
    src: local("Helvetica Neue Bold"), local("HelveticaNeue-Bold"),
      url(MgOpenModernaBold.ttf);
    font-weight: bold;
  }
  body {
    font-family: "Bitstream Vera Serif Bold", serif;
  }
  p {
    font-size: 12px;
    color: #000;
    text-align: left;
  }

  @font-face {
    font-family: Arial, MaHelvetica;
    src: local("Helvetica Neue Bold"), local("HelveticaNeue-Bold"),
      url(MgOpenModernaBold.ttf);
    font-weight: bold;
  }
}
```

php source

```php

use \TBela\CSS\Parser;

$parser = new Parser();

$parser->setOptions([
                        'silent' => false,
                        'flatten_import' => true
                    ]);
$parser->load('./css/manipulate.css');

$stylesheet = $parser->parse();

// get all src properties in a @font-face rule
$nodes = $stylesheet->query('@font-face/src');

$stylesheet->setChildren(array_map(function ($node) { return $node->copy(); }, $nodes));
$stylesheet->deduplicate();

echo $stylesheet;
```

Result

```css
@font-face {
  src: url("/static/styles/libs/font-awesome/fonts/fontawesome-webfont.fdf491ce5ff5.woff");
}
@media print {
  @font-face {
    src: local("Helvetica Neue Bold"), local("HelveticaNeue-Bold"), url(MgOpenModernaBold.ttf);
  }
}
```

## Extract @Font-face rules from a document

CSS source

```css
@font-face {
  font-family: "Bitstream Vera Serif Bold";
  src: url("/static/styles/libs/font-awesome/fonts/fontawesome-webfont.fdf491ce5ff5.woff");
}

body {
  background-color: green;
  color: #fff;
  font-family: Arial, Helvetica, sans-serif;
}
h1 {
  color: #fff;
  font-size: 50px;
  font-family: Arial, Helvetica, sans-serif;
  font-weight: bold;
}

@media print {
  @font-face {
    font-family: MaHelvetica;
    src: local("Helvetica Neue Bold"), local("HelveticaNeue-Bold"),
      url(MgOpenModernaBold.ttf);
    font-weight: bold;
  }
  body {
    font-family: "Bitstream Vera Serif Bold", serif;
  }
  p {
    font-size: 12px;
    color: #000;
    text-align: left;
  }

  @font-face {
    font-family: Arial, MaHelvetica;
    src: local("Helvetica Neue Bold"), local("HelveticaNeue-Bold"),
      url(MgOpenModernaBold.ttf);
    font-weight: bold;
  }
}
```

php source

```php

use \TBela\CSS\Parser;

$parser = new Parser();

$parser->setOptions([
                        'silent' => false,
                        'flatten_import' => true
                    ]);
$parser->load('./css/manipulate.css');

$stylesheet = $parser->parse();

// get all src properties in a @font-face rule
$nodes = $stylesheet->query('@font-face');

$stylesheet->setChildren(array_map(function ($node) { return $node->copy(); }, $nodes));
$stylesheet->deduplicate();

echo $stylesheet;
```

Result

```css
@font-face {
  font-family: "Bitstream Vera Serif Bold";
  src: url("/static/styles/libs/font-awesome/fonts/fontawesome-webfont.fdf491ce5ff5.woff");
}
@media print {
  @font-face {
    font-family: Arial, MaHelvetica;
    src: local("Helvetica Neue Bold"), local("HelveticaNeue-Bold"), url(MgOpenModernaBold.ttf);
    font-weight: bold;
  }
}
```
