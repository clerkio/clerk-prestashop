#!/bin/bash
SCRIPT_DIR=$( cd -- "$( dirname -- "${BASH_SOURCE[0]}" )" &> /dev/null && pwd )
mkdir -p $SCRIPT_DIR/../PRESTASHOP_MODULE_PACK
cp -rf $SCRIPT_DIR/* $SCRIPT_DIR/../PRESTASHOP_MODULE_PACK
mv -f $SCRIPT_DIR/../PRESTASHOP_MODULE_PACK/* $SCRIPT_DIR/../PRESTASHOP_MODULE_PACK/clerk
rm -f $SCRIPT_DIR/../PRESTASHOP_MODULE_PACK/pack_module.sh
zip -r $SCRIPT_DIR/../PRESTASHOP_MODULE_PACK/clerk_prestashop.zip $SCRIPT_DIR/../PRESTASHOP_MODULE_PACK/clerk
cd $SCRIPT_DIR/../PRESTASHOP_MODULE_PACK
ls | grep clerk_prestashop.zip
echo "Module Packed and Ready!"
