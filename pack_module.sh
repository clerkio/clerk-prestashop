#!/bin/bash

# Set the name of the zip file
zip_file="clerk_prestashop.zip"

# Create a temporary directory
temp_dir=$(mktemp -d)
orig_dir=$(realpath $(dirname $0))

rm -rf "./clerk_prestashop.zip"

# Create a parent folder named 'clerk' in the temporary directory and copy the contents into it
mkdir "$temp_dir/clerk" && cp -r * "$temp_dir/clerk"

cd "$temp_dir"

# Zip the 'clerk' folder
zip -r "$zip_file" "clerk"

mv "clerk_prestashop.zip" "$orig_dir/clerk_prestashop.zip"

echo "Packed Prestashop Module"
ls | grep "*.zip"

# Clean up by removing the temporary directory
rm -rf "$temp_dir"
