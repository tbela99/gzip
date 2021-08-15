# Create, Load, Save

An instance of the `axy\sourcemap\SourceMap` class is representation of a source map file.

```php
use axy\sourcemap\SourceMap;

$map = new SourceMap();

$map->addPosition([
    'generated' => [
        'line' => 1,
        'column' => 2,
    ],
    'source' => [
        'file' => 'a.js',
        'line' => 5,
        'column' => 3,
    ],
]);

$map->save(__DIR__.'/map.js.map');
```

The library does not provide different classes for work with existing files and creating new.
The `SourceMap` class can be used for both purposes.
You can open an existing file, add new positions and save.

## Create an Instance

```php
__construct([array $data [, string $filename])
```

`$data` - the data of source map file:

```php
$data = [
    'version' => 3,
    'file' => 'script.js',
    'sourceRoot' => '',
    'sources' => ['a.js', 'b.js'],
    'names' => ['one'],
    'mappings' => 'A',
];
$map = new SourceMap($data);
```

If the data is not specified then the empty map will be created.

```php
$map = new SourceMap();
```

The optional argument `$filename` is specified the "default out file".
See below.

## Save

```php
save([string $filename, [$jsonFlag = JSON_UNESCAPED_SLASHES])
```

Saves a source map to the specified file.

```php
$map->save(__DIR__.'/a.js.map');
```

You can specify flags for `json_encode()`:

```php
$map->save(__DIR__.'/a.js.map', JSON_PRETTY_PRINT);
```

If the `$filename` is not specified used default out file (see below).

## Load from File

```php
loadFormFile(string $filename):SourceMap
```

Creates an instance on the basis of file.

```php
$map = SourceMap::loadFromFile(__DIR__.'/b.js.map');
```

## Get Data

The current state of the data can be get as follows:

```php
print_r($map->getData());
```

## Magic Properties

The fields of a source map file available as magic properties of an source map instance.

* `version`
* `file`
* `sourceRoot`
* `sources`
* `names`

At the moment, `$map->version` always contains `3`.

`file` and `sourceRoot` are strings.
They can be read and modify.

```php
$map = SourceMap::loadFromFile('map.map');
echo $map->file;
$map->file = 'new-file.js';
$map->save();
```

`sources` and `names` are object wrappers.
See [Sources and Names](sources.md).

`sourcesContent` available via [sources](sources.md).

`mappings` are not directly accessible.

## Default out file

The file name which is saved unless otherwise indicated.
Sets by `loadFromFile()`.

```php
$map = SourceMap::loadFromFile('a.map.js');
// ...
$map->save(); // save to a.map.js
```

You can specify it in the constructor:

```php
$map = new SourceMap(null, 'a.map.js');
// ...
$map->save();
```

Or directly:

```php
$map = new SourceMap();
$map->outFileName = 'a.map.js';
```

`Save()` with argument changes it.

```php
$map = SourceMap::loadFromFile('a.map.js');
// ...
$map->save(); // save to a.map.js
// ...
$map->save('b.map.js'); // save to b.map.js
// ...
$map->save(); // save to b.map.js
```

If the outFileName is not specified then save without argument throws an exception:

```php
$map = new SourceMap();
// ...
$map->save(); // error
```

## Interfaces

For `SourceMap` implements the following interfaces: 

* `ArrayAccess` (`['file'], ['sourceRoot']` and other magic properties)
* `IteratorAggregate` (iterate the data)
* `Serializable`
* `JsonSerializable`
* `Countable`
