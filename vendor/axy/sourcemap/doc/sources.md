# Sources and Names

The field `sources` of the source map file contains a list of source files.
The field `mappings` contains links to `sources` items by indexes.

The field `names` is similar to the field `sources`.
It contains a list of symbol names (function and variable names and etc).

There are objects `$map->sources` and `$map->names` for work with these lists.

In additional, the object `$map->sources` also work with field `sourceContents`.

## Basic Methods

```php
$map->sources->getNames(); // get the list of files
$map->names->getNames(); // similar, get the list of symbol names

$map->sources->getNameByIndex(5); // returns the file name of index 5 or NULL (if not found)
$map->sources->getIndexByName('a.js'); // returns the index of the file or NULL
```

## Add an Item

```php
$map->sources->add('script.js');
```

Adds a file name to the list and returns its index.
If the file with same name is exists then returns existing index and does not add.

## Rename an Item

```php
$map->sources->rename(5, 'new-file.js');
$map->names->rename(10, 'newFunctionName');
```

Renames a file or a name by an index.
Changes affect the field `sources` and `names` but do not affect field `mappings` (it contains indexes only).

## Remove an Item

```php
$map->sources->remove(5);
$map->names->remove(10);
```

Removes a file or a name from the list.
Corresponding list of moves, and the indexes are recalculated.

### Remove from Sources

When a file is deleted all its positions will be deleted.

### Remove from Names

When a name is deleted all its positions will be kept.
But they will be anonymous.

## SourceContents

The `$map->source` has method `setContent`.

```php
$map->sources->setContent('a.js', file_get_contents(__DIR__.'/a.js'));
```
