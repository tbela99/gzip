# Stylesheet

It is the root element of the css stylesheet. 

## Creating a Stylesheet

there are several ways to create a stylesheet.

### Using AST

```php

use \TBela\CSS\Element;

$ast = json_decode(file_get_contents('ast.json'));

$stylesheet = Element::getInstance($ast);
```
### Using Parser
```php

use \TBela\CSS\Parser;

$parser = new Parser($css);

// load css like this
$parser->load('template.css');

// or like that
$parser->setContent($css);

// and then
$stylesheet = $parser->parse();
```

### Using Compiler

```php

use \TBela\CSS\Compiler;

$compiler = new Compiler();

// load css like this
$compiler->load('template.css');

// or like that
$compiler->setContent($css);

// and then
$stylesheet = $compiler->getData();
```
## Building Manually

```php
use TBela\CSS\Element\AtRule;
use TBela\CSS\Element\Stylesheet;

$stylesheet = new Stylesheet();

$rule = $stylesheet->addRule('div');

$rule->addDeclaration('background-color', 'white');
$rule->addDeclaration('color', 'black');

$media = $stylesheet->addAtRule('media', 'print');
$media->append($rule);

$rule = $stylesheet->addRule('div');

$rule->addDeclaration('max-width', '100%');
$rule->addDeclaration('border-width', '0px');

$viewport = $stylesheet->addAtRule('viewport', null, AtRule::ELEMENT_AT_DECLARATIONS_LIST);

$viewport->addDeclaration('width', '100vw');
$viewport->addDeclaration('height', '60px');

$media->insert($viewport, 0);

$namespace = $stylesheet->addAtRule('namespace', 'svg url(http://www.w3.org/2000/svg)', AtRule::ELEMENT_AT_NO_LIST);

$import = $stylesheet->addAtRule('import', 'url(css/stylesheet.css)', AtRule::ELEMENT_AT_NO_LIST);
$stylesheet->insert($import, 0);

echo $stylesheet;
```
Result

```css
@import url(css/stylesheet.css);
@media print {
 @viewport {
   width: 100vw;
   height: 60px
 }
 div {
   background-color: #fff;
   color: #000
 }
}
div {
 max-width: 100%;
 border-width: 0
}
@namespace svg url(http://www.w3.org/2000/svg);
```

## Methods

### computeShortHand

Compute shorthand Css properties

#### Arguments

none

#### Return Type

\TBela\CSS\Stylesheet

### Append

append css elements to this node

#### Arguments

- css: \TBela\CSS\Element|\TBela\CSS\Element[]

#### Return Type

\TBela\CSS\Element|\TBela\CSS\Interfaces\RuleListInterface

### AppendCss

append css content to this at-rule.

#### Arguments

- css: _string_

#### Return Type

\TBela\CSS\Element|\TBela\CSS\Interfaces\RuleListInterface

### AddRule

Add a Rule.

#### Arguments

\TBela\CSS\Element\Rule

#### Return Type

\TBela\CSS\Element\Rule

### AddAtRule

Add at-rule

#### Arguments

- name: _string_
- value: _string_
- type: _int_, optional. Determine the type of children for this rule. Values are:

> AtRule::ELEMENT_AT_RULE_LIST

Accept Rule as children. Example: @media

> AtRule::ELEMENT_AT_DECLARATIONS_LIST

Accept Declaration as children. Example: @viewport

> AtRule::ELEMENT_AT_NO_LIST

Does not accept any child. Example: @namespace

#### Return Type

\TBela\CSS\Element\AtRule

