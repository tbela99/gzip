#!/bin/sh -x
## see https://devilgate.org/blog/2012/07/02/tip-using-pandoc-to-create-truly-standalone-html-files/
# pandoc -f markdown -t html Docs/src/*.md  > Docs/dist/
pandoc=$(which pandoc)
php=$(which php)
tee=$(which tee)
for file in $(ls Docs/src/*.md)
do
base=$(basename $file | sed s/\.md$/\.html/)
pandoc -s --metadata-file Docs/src/metadata.yml --template Docs/template/template.html --toc --toc-depth=4 -f markdown -t html5 $file -c "https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" | php ./Docs/src/filter.php | tee "Docs/dist/$base"
done