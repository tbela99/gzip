# How to build scripts

Build all javascript files

```bash
./build.sh
```

## Service Worker Scripts

Definitions are located in ./config/rollup/rollup.config.js. After you update the definitions,
you need to update the build script with

```bash
npm run rollup-config
```

The updated script will be located in ./rollup.config.js

### Run the build script

```bash
node ./rollup.config.js
```

## Other javascript files

Definitions are located in ./config/terser/terser.config.js. After you update the definitions,
you need to update the build script with

```bash
npm run terser-config
```

The updated script will be located in ./terser.config.js

### Run the build script

```bash
node ./terser.config.js
```