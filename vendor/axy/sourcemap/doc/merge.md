# Merging

The source file can be transformed multiple times.
For example:

* Compile TypeScript/CoffeeScript/ES6 source to JavaScript.
* Build module wrapper (commonJS and etc).
* Concatenate a lot of files to the single file.
* Compress it.

At each stage we can get a source map.
If we want to debug in the browser the original code, we need to merge all of the intermediate maps.

For example, we have TypeScript files: `a.ts`, `b.ts`, `c.ts`, `d.ts`.
Compile it to `ab.js` and `cd.js`:

```
tsc --out ab.js --sourceMap a.ts b.ts
tsc --out cd.js --sourceMap c.ts d.ts
```

The result is a map `ab.js.map` (refers to `a.ts` and `b.ts`) and a map `cd.js.map` (refers to `c.ts` and `d.ts`).
In the next step compress all files:

```
java -jar compiler.jar --js ab.js cd.js --js_output_file out.js --create_source_map out.js.map
```

The result is a file `out.js` and a map `out.js.map` (refers to `ab.js` and `cd.js`).
We want to this map refers to `a.ts`, `b.ts` and etc.

## Method `merge()`

```
merge(SourceMap $map [, string $file])
```

Merges the current map and a $map.

The argument `$map` can be

* A `SourceMap` instance
* An array of a source map data (`[...'sources'=>[...],'mappings'=>'...'`)
* A string of a file name of a source map

The `$file` is name in the current map `sources` section.
By default used `$map->file`.

## Solution for the example

```php
$map = SourceMap::loadFromFile('/out.js.map');

$map->merge('ab.js.map');
$map->merge('cd.js.map');
```

The start content of `out.js.map`:

```json
{
    "version":3,
    "file":"out.js",    
    "sources":["ab.js","cd.js"],
    "names":["one","two","three","four"],
    "mappings":"...",
}
```

The final content of `out.js.map`:

```json
{
    "version":3,
    "file":"out.js",
    "sourceRoot":"",
    "sources": ["a.ts","c.ts","b.ts","d.ts"],
    "names": ["one","two","three","four"],
    "mappings":"..."
}
```

`merge()` replace intermediate sources on the original TS-files and changed positions.

Now in the browser debugger will display the source TS-files.

## Note

If we call `merge()` with a SourceMap instance as argument, then it is better not to use this instance in the future.
 
```php
$mapInter = SourceMap::loadFromFile('inter.map');
$mapOut = SourceMap::loadFromFile('out.map');
$mapOut->merge($mapInter);

unset($mapInter); // this object can be incorrect
```

If the object is required in the future then clone it.
```php
$mapOut->merge(clone $mapInter);
```
