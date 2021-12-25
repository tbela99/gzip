#!/bin/sh -x
## you need to install terser
# npm install --save-dev terser
## before you run uglify on WSL, you need to install it there
DIR=$(cd -P -- "$(dirname -- "$0")" && pwd -P)
DATE=$(date '+%F %H:%M:%S%:z')
VER=$(git rev-parse --short HEAD)
cd $DIR
#
#
fail() {

  echo -e '\e[31mbuild failed\e[0m'
  exit 1
}
#
#
# build config files
npm run rollup-config
npm run terser-config
# process es6+ deps
node rollup.config.js
# minify
node terser.config.js
#
sed -i "s/build-date/$DATE/g" worker/dist/serviceworker.js && sed -i "s/build-id/$VER/g" worker/dist/serviceworker.js || fail
#
[ -s worker/dist/serviceworker.js ] || fail
#
# shellcheck disable=SC2002
sed "s/build-date/$DATE/g" worker/src/browser.js | sed "s/build-id/$VER/g" > worker/dist/browser.js || fail
#
sed "s/build-date/$DATE/g" worker/src/browser.administrator.js | sed "s/build-id/$VER/g" > worker/dist/browser.administrator.js || fail
#
sed "s/build-date/$DATE/g" worker/src/browser.sync.js | sed "s/build-id/$VER/g" > worker/dist/browser.sync.js || fail
#
sed "s/build-date/$DATE/g" worker/src/browser.uninstall.js | sed "s/build-id/$VER/g" > worker/dist/browser.uninstall.js || fail
#
# node terser.config.js
#
sha1sum worker/dist/serviceworker.js | awk '{print $1;}' | tee ./worker_version