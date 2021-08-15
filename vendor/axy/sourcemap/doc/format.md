# Supported Format of Source Map

The library supports only Version 3 of the format.

See [Source Map Revision 3 Proposal](https://docs.google.com/document/d/1U1RGAehQwRypUTovF1KRlpiOFze0b-_2gc6fAH0KY0k/edit).

The example of a source map file:

```json
{
    "version": 3,
    "file": "out.js",
    "sourceRoot": "",
    "sources": ["foo.js", "bar.js"],
    "sourcesContent": [null, null],
    "names": ["src", "maps", "are", "fun"],
    "mappings": "A,AAAB;;ABCDE;"
}
```

The library required next fields in input files: `version`, `sources`, `mappings`.
The other field is optional.

The output data of the library contains all these fields.

## Index Map

The library does not support the index map (field `sections`).

## Extensions

The library does not support any extensions (like `x_google_linecount`).
Source map files can contain extension fields, but they are not processed.
