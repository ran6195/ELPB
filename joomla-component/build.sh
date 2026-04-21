#!/bin/bash

# Build script for Landing Pages Joomla Component
# This script creates a ZIP package ready for installation in Joomla

COMPONENT_NAME="com_landingpages"
BUILD_DIR="build"
PACKAGE_NAME="${COMPONENT_NAME}.zip"

echo "======================================"
echo "Building ${COMPONENT_NAME}"
echo "======================================"

# Remove old build
if [ -d "$BUILD_DIR" ]; then
    echo "Removing old build directory..."
    rm -rf "$BUILD_DIR"
fi

if [ -f "$PACKAGE_NAME" ]; then
    echo "Removing old package..."
    rm "$PACKAGE_NAME"
fi

# Create build directory
echo "Creating build directory..."
mkdir -p "$BUILD_DIR"

# Copy component files
echo "Copying component files..."
cp -r "$COMPONENT_NAME" "$BUILD_DIR/"

# Create ZIP package
echo "Creating ZIP package..."
cd "$BUILD_DIR"
zip -r "../$PACKAGE_NAME" "$COMPONENT_NAME"
cd ..

# Cleanup
echo "Cleaning up..."
rm -rf "$BUILD_DIR"

echo "======================================"
echo "Build complete!"
echo "Package: $PACKAGE_NAME"
echo "======================================"
echo ""
echo "To install:"
echo "1. Upload $PACKAGE_NAME to your Joomla administrator"
echo "2. Go to Extensions > Manage > Install"
echo "3. Upload the ZIP file"
echo ""
