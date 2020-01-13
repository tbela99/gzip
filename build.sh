#!/bin/sh -x
## you need to install terser
# npm install --save-dev terser
## before you run uglify on WSL, you need to install it there
DIR=$(cd -P -- "$(dirname -- "$0")" && pwd -P)
cd $DIR
#
#
node rollup.config.js
#
cat worker/dist/serviceworker.js | sed "s/build-date/$(date '+%F %H:%M:%S%:z')/g" | sed "s/build-id/$(git rev-parse --short HEAD)/g" > worker/dist/serviceworker.js
#
./node_modules/terser/bin/terser --warn --comments all --beautify beautify=true,preamble='"/* do not edit! */"' --ecma=8\
 -- worker/src/browser.js | sed "s/build-date/$(date '+%F %H:%M:%S%:z')/g" | sed "s/build-id/$(git rev-parse --short HEAD)/g" > worker/dist/browser.js
#
./node_modules/terser/bin/terser --warn --comments all --beautify beautify=true,preamble='"/* do not edit! */"' --ecma=8\
 -- worker/src/browser.administrator.js | sed "s/build-date/$(date '+%F %H:%M:%S%:z')/g" | sed "s/build-id/$(git rev-parse --short HEAD)/g" > worker/dist/browser.administrator.js
#
./node_modules/terser/bin/terser --warn --comments all --beautify beautify=true,preamble='"/* do not edit! */"' --ecma=8\
 -- worker/src/browser.sync.js | sed "s/build-date/$(date '+%F %H:%M:%S%:z')/g" | sed "s/build-id/$(git rev-parse --short HEAD)/g" > worker/dist/browser.sync.js
#
./node_modules/terser/bin/terser --warn --comments all --beautify beautify=true,preamble='"/* do not edit! */"' --ecma=8\
 -- worker/src/browser.uninstall.js | sed "s/build-date/$(date '+%F %H:%M:%S%:z')/g" | sed "s/build-id/$(git rev-parse --short HEAD)/g" > worker/dist/browser.uninstall.js
#
node terser.config.js
#
sha1sum worker/dist/serviceworker.js | awk '{print $1;}' | tee ./worker_version