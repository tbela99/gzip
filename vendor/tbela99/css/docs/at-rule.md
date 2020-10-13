# Rule

implement at-rule css node

## Methods

### computeShortHand

Compute shorthand Css properties

#### Arguments

none

#### Return Type

\TBela\CSS\Element\AtRule

### isLeaf

indicate if this node can contain children

#### Arguments

none

#### Return Type

_bool_

### hasDeclarations

return true if this at-rule can contain css declarations

#### Arguments

none

#### Return Type

_bool_

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

### AddDeclaration

add css declaration to this at-rule.

#### Arguments

- name: _string|\TBela\CSS\Value\Set_ declaration name
- value: _string|\TBela\CSS\Value\Set_ declaration value

#### Return Type

\TBela\CSS\Element\Rule

#### Throws

\InvalidArgumentException if the provided node cannot be a child of this node

### Supports

return true if the provided node can be a child of this at-rule

#### Arguments

\TBela\CSS\Element

#### Return Type

\TBela\CSS\Element\Rule
