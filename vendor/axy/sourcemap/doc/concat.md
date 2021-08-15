# Concatenation of Files

For example, we have the following TypeScript-files: `a.ts`, `b.ts`, `c.ts`, `d.ts`.
We compile its (`b` and `c` compile to a single file):

```sh
tsc --sourceMap a.ts
tsc --sourceMap --out bc.js b.ts c.ts
tsc --sourceMap d.ts
```

As a result, we have JavaScript sources `a.js`, `bc.js` and `d.js`.
And source maps `a.js.map`, `bc.js.map`, `d.js.map`.

We want to concatenate all the sources into one file: `a.js + bc.js + d.js -> out.js`.
And we want to create a source map file `out.js.map` that is referred to TypeScript sources.

## Method `concat()`

```
concat(SourceMap $map, int $line [, int $column]):void
```

This method takes a source map and merges it with the current.
The positions of the current map remain in its places, and new positions are added to certain places.

The argument `$map` can be

* A `SourceMap` instance
* An array of a source map data (`[...'sources'=>[...],'mappings'=>'...'`)
* A string of a file name of a source map

## The Algorithm

* Build the empty file `out.js` and the empty source map `out.js.map`.
* Add the contents of next js-file to the `out.js`.
* Calculate the number of lines of js-files and start line of each of these files.
* Add the next source map to the resulting source map to the calculated line.

```php
$resultMap->concat('d.js.map', 10);
```

10 is start line of the contents of `d.js` in `out.js`.

If we merge the files into a single string, it is necessary remember size of the content instead the lines number.
And use the argument `$column` instead `$line`.

## The example

```php
use axy\sourcemap\SourceMap;

$files = ['a.js', 'bc.js', 'd.js'];
$outFile = 'out.js';
$outMap = $outFile.'.map';

// Create the empty source map
$map = new SourceMap();
$map->file = $outFile;

// The content of the resulting file
$result = [];

$line = 0;
foreach ($files as $name) {
    $jsFile = __DIR__.'/'.$name;
    $mapFile = $jsFile.'.map';
    // load the next file
    $content = file_get_contents($jsFile);
    // remove link to a source map
    $content = preg_replace('~//# sourceMappingURL.*$~s', '', $content);
    $content = rtrim($content);
    $result[] = $content;
    // add next file
    $map->concat($mapFile, $line); 
    // shift to next position    
    $line += substr_count($content, "\n") + 1;    
}

// add a link to the resulting source map
$result[] = '//# sourceMappingURL='.$outMap;

// save the resulting JS-file
$result = implode("\n", $result);
file_put_contents(__DIR__.'/'.$outFile, $result); 

// save the resulting source map
$map->save(__DIR__.'/'.$outMap);
```

## Note

If we call `concat()` with a SourceMap instance as argument, then it is better not to use this instance in the future.
 
```php
$map1 = SourceMap::loadFromFile('one.map');
$map2 = SourceMap::loadFromFile('two.map');

$map1->concat($map2, 10);

unset($map2); // this object can be incorrect
```

If the object is required in the future then clone it.
```php
$map1->concat(clone $map2, 10);
```
