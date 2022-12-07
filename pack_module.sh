#!/bin/bash
SCRIPT_DIR=$( cd -- "$( dirname -- "${BASH_SOURCE[0]}" )" &> /dev/null && pwd )
mkdir -p $SCRIPT_DIR/../PRESTASHOP_MODULE_PACK
cp -rf $(ls $SCRIPT_DIR/* | grep -v $SCRIPT_DIR/pack_module.sh) $SCRIPT_DIR/../PRESTASHOP_MODULE_PACK
mv -f $SCRIPT_DIR/../PRESTASHOP_MODULE_PACK/* $SCRIPT_DIR/../PRESTASHOP_MODULE_PACK/clerk
zip -r $SCRIPT_DIR/../PRESTASHOP_MODULE_PACK/clerk_prestashop.zip $SCRIPT_DIR/../PRESTASHOP_MODULE_PACK/clerk
cd ../PRESTASHOP_MODULE_PACK && ls | grep clerk_prestashop.zip
echo "Module Packed and Ready!"
