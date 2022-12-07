#!/bin/bash
SCRIPT_DIR=$( cd -- "$( dirname -- "${BASH_SOURCE[0]}" )" &> /dev/null && pwd )
mv -f $(ls $SCRIPT_DIR/* | grep -v $SCRIPT_DIR/pack_module.sh) $SCRIPT_DIR/clerk
zip -r $SCRIPT_DIR/../clerk_prestashop.zip $SCRIPT_DIR/clerk
git reset --hard HEAD
git pull
cd .. && ls | grep clerk_prestashop.zip
echo "Module Packed and Ready!"