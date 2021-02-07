## Query Api

It allows you to query the CSS node in using and xpath like syntax or class names. 

### queryByClassNames

Search nodes by class names.

```php
$result = $element->queryByClassNames('@font-face, .foo .bar, .class, .another-class');
```

### query

Search nodes using an expression.

```php
// search @font-face that contain an src attribute
$result = $element->query('@font-face[@src]/..');

```

### Node Selectors

the node selectors are

- '*': match all nodes
- '.' : match the current node
- '..': match the parent node
- '/': match the root nodes
- '//' : match all descendant nodes
- 'a': match all nodes with a selector or name 'a'
- 'a[2]': match the second node with name or selector equals to 'a'
- '|': match nodes that match selector a or selector /b in 'a|/b'. It will set the root node as the current node before searching the right operand
- 'a,b': match nodes with name or selector that is either 'a' or 'b'
- '@media[@value=print]': match all media with value equals to 'print'. Example: @media print {}
- '@media[@value^=print]': match all media with value that begins with 'print'. Example: @media print and (max-width: 320px)
- '@media[@value*=print]': match all media with value that contains 'print'
- '@media[contains(@value, 'print')]': match all media with value that contains 'print'
- '@media[@value$=print]': match all media with value that ends with 'print'

```php
# get all @font-face in the stylesheet
$nodes = $element->query('@font-face');
```

### Attributes Filters

Nodes can be filtered using attributes. An attribute is contained inside '\[' and '\]'. An attribute name starts with '@'.
Attributes are attribute name (which are @name and @value) or function filter

- @name: this attribute designates either the node selector, the @atRule name or the css declaration name
Example
```php
// match all nodes with a name
$nodes = $element->query('[@name]');
// match all @media
$nodes = $element->query('[@name="media"]');
```
#### @value

this attribute designates the css declaration value or @AtRule attributes

```php
// match all nodes with a value like @media or a declaration node
$nodes = $element->query('[@value]');
// match all nodes with value print like @media print {}
$nodes = $element->query('[@value="print"]');
```
### Operators

#### or (|)

select nodes that match any of the selectors

```php
$nodes = $element->query('[@value="url(./images/flower.jpg)"]|@media|@font-face');
```
#### equals (=)

```php
$nodes = $element->query('[@value="url(./images/flower.jpg)"]');
```
#### begins with (^=)

```php
$nodes = $element->query('[@value^="print"]');
```
#### ends with ($=)

```php
$nodes = $element->query('[@value$="print"]');
```
#### contains (*=)

```php
$nodes = $element->query('[@value*="print"]');
```

### Function Filters

Nodes can be filtered using functions. Supported functions are 

#### color(@attr, 'value')
 
match all nodes with attributes that match the specified color

```php
// match all declarations with value that match white color
$nodes = $element->query('[color(@value, "white")]');
```
#### contains(@attr, 'value')

match all nodes with attributes that contains the specified value. This is equivalent to using the operator '\*='

```php
// match all nodes with value that contains print
$nodes = $element->query('[contains(@value, "print")]');
```
#### beginswith(@attr, 'value')

match all nodes with attributes that begin with the specified value. This is equivalent to using the operator '^='

```php
// match all nodes with value that contains print
$nodes = $element->query('[beginswith(@value, "print")]');
```
#### endswith(@attr, 'value')

match all nodes with attributes that end with the specified value. This is equivalent to using the operator '$='

```php
// match all nodes with value that contains print
$nodes = $element->query('[endswith(@value, "print")]');
```
####  equals(@attr, 'value')

match all nodes with attributes that are equal to the specified value. This is equivalent to using the operator '='

```php
// match all nodes with value that contains print
$nodes = $element->query('[equals(@value, "print")]');
```
#### empty()

match empty rules

```php
// match all empty nodes
$nodes = $element->query('[empty()]');
```
#### comment()

match comments node. this will not match comments after a node name or inside node value

```php
// match all comment nodes
$nodes = $element->query('[comment()]');
```

#### not()

match nodes that do not match an expression

```php
// match all nodes with name 'color' and value that is not white
$nodes = $element->query('[equals(@name, "color")][not(color(@value, "white"))]');
```
