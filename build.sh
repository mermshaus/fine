#!/bin/bash

set -e

# Read version string (Semantic Versioning) from Application class
version=`cat src/Application.php | grep "const VERSION" | sed 's/^.*'\''\(.*\)'\''.*$/\1/'`

fine_dir="fine-${version}"

build_dir="./build"



if [ -d "${build_dir}" ];
then
    rm -r ${build_dir}
fi

mkdir ${build_dir}

php -f ./generate > ${build_dir}/index.php

cp README.md ${build_dir}


cd ${build_dir}

find . -type f ! -perm 0644 -exec chmod 644 {} \;
find . -type d ! -perm 0755 -exec chmod 755 {} \;

mkdir ${fine_dir}
chmod 0755 ${fine_dir}

cp ./index.php ${fine_dir}
cp ./README.md ${fine_dir}

tar czf ${fine_dir}.tar.gz ${fine_dir}
sha256sum ${fine_dir}.tar.gz > ${fine_dir}.tar.gz.sha256sum
chmod 0644 ${fine_dir}.tar.gz
chmod 0644 ${fine_dir}.tar.gz.sha256sum
sha256sum --quiet -c ${fine_dir}.tar.gz.sha256sum

zip -qr ${fine_dir}.zip ${fine_dir}
sha256sum ${fine_dir}.zip > ${fine_dir}.zip.sha256sum
chmod 0644 ${fine_dir}.zip
chmod 0644 ${fine_dir}.zip.sha256sum
sha256sum --quiet -c ${fine_dir}.zip.sha256sum

rm -r ${fine_dir}

cd ..
