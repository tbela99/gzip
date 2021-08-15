# Insert/Remove Blocks

For example, we have a TypeScript-file:

```typescript
export class ParentClass {

    constructor(protected name:string) {
    }
    
    public getName():string {
        return this.name;
    }
}

export class ChildClass extends ParentClass {

    public setName(name:string):void {
        this.name = name;
    }
    
}
```

Compile it as CommonJS-module and create a source map:

```
tsc --module commonjs --sourceMap my.ts
```

The resulting JavaScript file:

```javascript
var __extends = this.__extends || function (d, b) {
    for (var p in b) if (b.hasOwnProperty(p)) d[p] = b[p];
    function __() { this.constructor = d; }
    __.prototype = b.prototype;
    d.prototype = new __();
};
var ParentClass = (function () {
    function ParentClass(name) {
        this.name = name;
    }
    ParentClass.prototype.getName = function () {
        return this.name;
    };
    return ParentClass;
})();
exports.ParentClass = ParentClass;
var ChildClass = (function (_super) {
    __extends(ChildClass, _super);
    function ChildClass() {
        _super.apply(this, arguments);
    }
    ChildClass.prototype.setName = function (name) {
        this.name = name;
    };
    return ChildClass;
})(ParentClass);
exports.ChildClass = ChildClass;
//# sourceMappingURL=my.js.map
```

We want to make on the generated file some manipulation.

1. Remove definition of `__extends`. TypeScript creates this code in each resulting file (where there is inheritance).
Move it to a single file.

2. Wrap the module code to a custom requireJS wrapper.

The result:

```javascript
myRequire("MyModule", function (require, module, exports) {
var ParentClass = (function () {
    function ParentClass(name) {
        this.name = name;
    }
    ParentClass.prototype.getName = function () {
        return this.name;
    };
    return ParentClass;
})();
exports.ParentClass = ParentClass;
var ChildClass = (function (_super) {
    __extends(ChildClass, _super);
    function ChildClass() {
        _super.apply(this, arguments);
    }
    ChildClass.prototype.setName = function (name) {
        this.name = name;
    };
    return ChildClass;
})(ParentClass);
exports.ChildClass = ChildClass;
});
//# sourceMappingURL=my.js.map
```

## Change the Source Map

We have changed the generated content.
Now we must change the source map.

```php
use axy\sourcemap\SourceMap;

$map = SourceMap::loadFromFile('my.js.map');
$map->removeBlock(0, 0, 6, 0); // remove __extends
$map->insertBlock(0, 0, 1, 0); // insert requireJS-wrapper
$map->save();
```

### removeBlock()

```php
removeBlock($sLine, $sColumn, $eLine, $eColumn)
```

Removes a block from the generated contents.
`$sLine:$sColumn` - the coordinates of the first character of block
`$eLine:$eColumn` - the coordinates of the first character of content after the block

All positions inside the block will be removed.
All positions after the block will be moved.

In the example we removed the first 6 lines entirely.

### insertBlock()

```php
insertBlock($sLine, $sColumn, $eLine, $eColumn)
```

Inserts a block to the generated contents.
The block will be inserted before the character with coordinates `$sLine:$sColumn`.
After insertions, this character will be shifted to the position `$eLine:$eColumn`.

In the example we insert one line to the beginning of the content.

## Notes

Watch to the order of calls.

```php
$map->insertBlock(0, 0, 1, 0); // insert requireJS-wrapper
$map->removeBlock(0, 0, 6, 0); // remove __extends
```

Therefore, we inserted 1 line to the top and removed it on next step.
Fail.

Remember that lines and columns is zero-based.
