cat localforage.js > localforage-all.js
cat localforage-getitems.js >> localforage-all.js
cat localforage-setitems.js >> localforage-all.js
cat localforage-removeitems.js >> localforage-all.js
uglifyjs --compress --mangle -- localforage-all.js > localforage-all.min.js
uglifyjs --compress --mangle -- load-worker.js > load-worker.min.js
uglifyjs --compress --mangle -- worker.js > worker.min.js
php -r "echo hash_file('crc32', 'load-worker.js');" > worker.checksum
php -r "echo hash_file('crc32', 'load-worker.min.js');" > worker.min.checksum
php -r "echo base64_encode(hash_file('sha256', 'localforage-all.min.js'));" > integrity.checksum