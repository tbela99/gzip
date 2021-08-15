# Search in Map

Here are the search methods in a filled map.
As result they return an instance of [PosMap](PosMap.md) or a list of PosMap objects.

## Get a position

```php
getPosition(int $line, int $column):PosMap
```

Returns a position map by the position in generated content.
Returns `NULL` if the position was not found. 

```php
$map = SourceMap::loadFromFile('a.js.map');

$position = $map->getPosition(10, 20);
```

Do not forget that the lines and columns is zero-based.
In the example we search 11 line and 21 column.

## Find in source

```php
findPositionInSource(int $fileIndex, int $line, int $column):PosMap
```

Similarly, searches for the position in source files.
Returns `NULL` if the position was not found.

It is more consuming operation than `getPosition()`.

How to find the index file by the file name see [sources](Sources.md).

## Find by filter

```php
find([PosMap $filter]):PosMap[]
```

Looking for positions that match your filter.
As the filter used [PosMap](PosMap.md).
All fields is optional.

Find all positions from 5th line of the generated content

```php
$generated = new PosGenerated();
$generated->line = 5;
$map->find(new PosMap($generated));
```

Or find all entry of `myFunction` in `a.js` source file:

```php
$map->find([
    'source' => [
        'file' => 'a.js',
        'name' => 'myFunction',
    ],
]);
```

If no position matches the filter then empty array will be return.

If the filter is empty then all positions will be return:

```php
$all = $map->find();
```
