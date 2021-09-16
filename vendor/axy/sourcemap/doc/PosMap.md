# Position Map

Classes that are described here, are used to indicate position map.
These classes are located in the `axy\sourcemap` namespace.

## PosGenerated

An instance of the `axy\sourcemap\PosGenerated` class has following public properties:

* `line` (int) - the zero-based line number of the generated content
* `column` (int) - the zero-base column number 

## PosSource

The source position has following public properties:
 
* `fileIndex` (int) - the index of the file in the `sources` list (zero-based).
* `fileName` (string) - the source file name (`a.js` for example)
* `line` (int) - the zero-based line number in the source file
* `column` (int) - the zero-based column number
* `nameIndex` (int) - the index of the symbol name in the `names` list (zero-based).
* `name` (string) - the symbol name (`myFunction` for example)

The symbol name is optional.
If the source position has no symbol name then `name` and `nameIndex` is `NULL`.

## PosMap

* `generated` (PosGenerated) - an instance of the `PosGenerated`
* `source` (PosSource) - the associated source position (an instance of the `PosSource`)

## Arguments and result of methods

If a method of the `SourceMap` class must return a position map it returns the fully-filled `PosMap` instance.  
Fills the all the indexes and names.

If a method takes a position map as an argument you have no need to create Pos-objects.
To specify the structure, you can use arrays or plain-objects.

```php
$map->addPosition([
    'generated' => [
        'line' => 10,
        'column' => 5,
    ],
    'source' => [
        'fileIndex' => 3,
        'line' => 3,
        'column' => 10,
    ],
]);
```

Or, you can still create:

```php
$generated = new PosGenerated();
$generated->line = 10;
$generated->column = 5;

$source = new PosSource();
$source->fileIndex = 3;
$source->line = 3;
$source->column = 10;

$position = new PosMap($generated, $source);

$map->addPosition($position);

// or

$map->addPosition(['generated' => $generated, 'source' => $source]);
```

## Index and name

Does not necessarily specified `fileName` and `fileIndex`.
You can specify one of these values and the other will be calculated automatically.

Similarly, for `name` and `nameIndex`.

## Optional fields

Each operation requires its own set of fields.

```php
$map->find(['source' => ['fileIndex' => 5]]); // Finds all positions from source file #5
```

For `find()` that's enough.
For `addPosition()` must specify the generated line and column.

## Changing objects

The fields of these classes are implemented as public properties for optimization.
Do not change objects which received from methods.

```php
$position = $map->getPosition(5, 10);
$position->line = 10;
```

This can lead to unintended consequences.

If you want to change the position, remove it and add a new position. 
