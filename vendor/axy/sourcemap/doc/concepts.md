# Basic Concepts

For example, we have the following files: `a.js`, `b.js`, `c.js`.
We minimize them in one: `abc.min.js`.
In the minimization process is created the source map file `abc.min.js.map`.

Example of such a file:

```json
"version": 3,
"file": "abc.min.js",
"sourceRoot": "",
"sources": ["a.js", "b.js", "c.js"],
"sourcesContent": [null, null],
"names": ["src", "maps", "are", "fun"],
"mappings": "A,AAAB;;ABCDE;"
```

## Basic Terms

**Generated content**: the content of the file `abc.min.js`.
Generated content is always single.

**Generated position**: a position in the generated content.
Is described by a line number and a column number.

**Source**: a file of the original code. 
One or more.
Specific source is identified by an index of the "sources" field of the source map file.

**Source position**: a position in the original content.
Is described by a source index, a line number (in the source file) and a column number.
Additional, it can contain information about a symbol name which located in this position.
 
**Symbol name**: an identifier (a function name, a variable name or etc) from a source content.

**Position map** (or **position**): a relationship between a source position and a generated position.

The main content of the source map is the list of position maps.

## Zero-based Offsets

All line numbers, column numbers, source and symbol name indexes is zero-based.
The first symbol of the first line has a coordinates `line=0;column=0`.

## Working with Files

The library works with source map only.
The library does not process the source files and does not generates the output file.

The `sources` filed for the library is a simple list of a named source.

The `file` and `sourceRoot` fields are just the strings that you can read and modify.
The library does not resolving file names relative to the `sourceRoot`.
