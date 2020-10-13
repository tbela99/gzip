# Rule

implement css rule

## Methods

### computeShortHand

Compute shorthand Css properties

#### Arguments

none

#### Return Type

\TBela\CSS\Element\Rule

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

### getSelector

Return the rule selector

#### Arguments

none

#### Return Type

array

### setSelector

set the rule selector

#### Arguments

array|string

#### Return Type

\TBela\CSS\Element\Rule

### AddSelector

Add a selector to the rule

#### Arguments

array|string

#### Return Type

\TBela\CSS\Element\Rule

### RemoveSelector

remove a selector to the rule

#### Arguments

array|string

#### Return Type

\TBela\CSS\Element\Rule

### AddDeclaration

add css declaration

#### Arguments

- name: _string|\TBela\CSS\Value\Set_ declaration name
- value: _string|\TBela\CSS\Value\Set_ declaration value

#### Return Type

\TBela\CSS\Element\Rule

### Merge

Merge the provided rule into this rule

#### Arguments

\TBela\CSS\Element\Rule

#### Return Type

\TBela\CSS\Element\Rule

### Supports

return true if the provided node can be a child of this rule

#### Arguments

\TBela\CSS\Element

#### Return Type

\TBela\CSS\Element\Rule
