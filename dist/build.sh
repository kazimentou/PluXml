#!/usr/bin/sh

cd $(dirname $0)/..
echo "working directory: $(pwd)"

VERSION=$(grep '\bPLX_VERSION\b' core/lib/config.php |sed "s/^.*'\([0-9.]\+\).*/\1/")
ZIPNAME="$(dirname $0)/pluxml-${VERSION}.zip"
rm -f $ZIPNAME

git archive -o $ZIPNAME --prefix=PluXml/ master

echo "Success:"
ls -hl $ZIPNAME
