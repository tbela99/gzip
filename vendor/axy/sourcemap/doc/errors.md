# Errors

The library throws exceptions from the namespace `axy\sourcemap\error`.

## `InvalidFormat`

The basic exception for the format errors.
These errors occur when you create a SourceMap object or when load from file.

#### `InvalidSection`

```php
$data = [
    'version' => 3,
    'file' => 'out.js',
    'sources' => 'sources',
    'mappings' => 'A',
];
$map = new SourceMap($data); // Source map section "sources" is invalid: "must be an array"
```

#### `InvalidMappings`

The section `mappings` has an invalid format.

#### `InvalidJSON`

Occurs when calling `loadFromFile()`: file is not JSON.

#### `UnsupportedVersion`

Version 3 is supported only.

## `InvalidIndexed`

Error when work with the indexed fields `sources` and `names`.

```php
$pos = [
    'generated' => ['line' => 5, 'column' => 10],
    'source' => ['fileIndex' => 5, 'line' => 3, 'column' => 0],
];
```

If a file with index #5 is not exists in "sources" it is error.

## `IOError`

Error when working with files (`loadFromFile()` and `save()`).
File not found or permission denied.

## `OutFileNotSpecified`

Call `save()` without `$filename` argument when the default file is not defined.

## `IncompleteData`

```php
$pos = [
    'generated' => ['line' => 5],
    'source' => ['fileIndex' => 5],
];
$map->addPosition($pos);
```

Required `generated.column`.
