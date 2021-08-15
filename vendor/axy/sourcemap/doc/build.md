# Build Source Map

Process of building a source map is simple.
You need to create an empty instance and add the position maps.
You can add positions by the method `addPosition()`.

```php
addPosition(PosMap $position):PosMap
```

The format of the position map described there: [PosMap](PosMap.md). 

Example:

```php
use axy\sourcemap\SourceMap;

$map = new SourceMap();
$map->file = 'out.js';

$map->addPosition([
    'generated' => [
        'line' => 1,
        'column' => 2,
    ],
    'source' => [
        'fileName' => 'a.js',
        'line' => 5,
        'column' => 3,
    ],
]);

$map->addPosition([
    'generated' => [
        'line' => 1,
        'column' => 12,
    ],
    'source' => [
        'fileName' => 'b.js',
        'line' => 6,
        'column' => 0,
        'name' => 'MyClass',
    ],
]);

$map->save(__DIR__.'/map.js.map', JSON_PRETTY_PRINT);
```

This will create the file `map.js.map` with the following contents:

```
{
    "version": 3,
    "file": "out.js",
    "sourceRoot": "",
    "sources": [
        "a.js",
        "b.js"
    ],
    "names": [
        "MyClass"
    ],
    "mappings": ";EAKG,UCCHA"
}
```

Example with creating an instance `PosMap`:

```php
$generated = new PosGenerated();
$generated->line = 1;
$generated->column = 12;

$source = new PosSource();
$source->fileName = 'b.js';
$source->line = 6;
$source->column = 0;
$source->name = 'MyClass';

$position = new PosMap($generated, $source);
$map->addPosition($position);
```

## Required fields

`generated` must be completely filled (`line` and `column`).

`source` can be omitted.
If it specified, must be specified file, line and column.
Name is optional.

If not all required fields is specified then thrown an exception `IncompleteData`.

## Designation of the source file

To specify the source file you can use fields `source->fileIndex` or `source->fileName`.

`fileName` is a file name (`script.js` for example).
`fileIndex` is an index in the "sources" field of the map file.

The easiest way is specify a `fileName`.
The library substitute the right index.

```php
$position = $map->addPosition([
    'generated' => [
        'line' => 1,
        'column' => 12,
    ],
    'source' => [
        'fileName' => 'b.js',
        'line' => 6,
        'column' => 0,
        'name' => 'MyClass',
    ],
]);

echo $positions->source->fileIndex; // 1
```

You can specify any `fileName`.
If it is not exists then it will be created in `sources`.

`fileIndex` must exist.
Otherwise, an exception `InvalidIndexed` is thrown.

## Designation of the symbol name

To specify the symbol name you can use fields `source->nameIndex` or `source->name`.

The rest is similar to the designation of source file (see above).

## Result of Method

The method returns a PosMap-instance.
If an instance of PosMap was used as the argument, then it will be returned.
In this case, it will be normalized (defined `source->fileName` by `source->fileIndex` for example).

These instances are stored inside the source map object.
Their modification can lead to errors.

Example:

```php
// ...
$position = new PosMap($generated, $source);
$map->addPosition($position);

// next symbol
$generated->column += 5;
$source->line++;
$source->column += 5;
$source->name = 'MyClass';
$map->addPosition($position);

// next symbol ...
```

Here we modify the same object.

Solution is to use cloning:

```php
$map->addPosition(clone $position);
// ...
$map->addPosition(clone $position);
```

## Example

We have the following file (`factorial.js`):

```js
function factorial(n) {
    if (n <= 2) {
        return n;
    } else {
        return n * factorial(n - 1);
    }
}

console.log(factorial(5));
```

Let's write a simple minifier (concat all to single line):

```php
use axy\sourcemap\SourceMap;
use axy\sourcemap\PosMap;

$inputFile = 'factorial.js';
$outputFile = 'factorial.min.js';
$mapFile = 'factorial.min.js.map';

$map = new SourceMap();
$map->file = $outputFile;

$position = new PosMap(null);
$generated = $position->generated;
$source = $position->source;

$generated->line = 0;
$generated->column = 0;

$source->fileName = $inputFile;

$result = [];
foreach (file(__DIR__.'/factorial.js') as $nl => $line) {
    $line = rtrim($line);
    $source->line = $nl;
    $lenPre = strlen($line);
    $line = ltrim($line);
    $len = strlen($line);
    $source->column = $lenPre - $len;
    if ($line === '') {
        continue;
    }
    $result[] = $line;   
    $map->addPosition(clone $position);
    $generated->column += $len;    
}

$result[] = "\n//# sourceMappingURL=".$mapFile."\n";

file_put_contents($outputFile, implode('', $result));
$map->save($mapFile, JSON_PRETTY_PRINT);
```

The result is a "compressed" file (`factorial.min.js`):

```js
function factorial(n) {if (n <= 2) {return n;} else {return n * factorial(n - 1);}}console.log(factorial(5));
//# sourceMappingURL=factorial.min.js.map
```

And a source map (`factorial.min.js.map`):

```
{
    "version": 3,
    "file": "factorial.min.js",
    "sourceRoot": "",
    "sources": [
        "factorial.js"
    ],
    "names": [],
    "mappings": "AAAA,uBACI,aACI,SACJ,QACI,4BACJ,CACJ,CAEA"
}
```

Now you can use the compressed file and debug the source file in a browser debugger.
