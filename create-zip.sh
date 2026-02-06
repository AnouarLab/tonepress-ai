#!/bin/bash
# AI Content Engine - Create Distribution Zip
# This script creates a clean WordPress plugin zip file

set -e

# Colors for output
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${BLUE}=====================================${NC}"
echo -e "${BLUE}TonePress AI - Build Distribution${NC}"
echo -e "${BLUE}=====================================${NC}"
echo ""

# Plugin directory
PLUGIN_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PLUGIN_SLUG="tonepress-ai"

# Get Version (from new filename)
VERSION=$(grep "Version:" tonepress-ai.php | head -1 | awk '{print $3}')
ZIP_NAME="${PLUGIN_SLUG}-v${VERSION}.zip"
DIST_DIR="${PLUGIN_DIR}"  # Output in current directory

echo -e "${YELLOW}Plugin:${NC} ${PLUGIN_SLUG}"
echo -e "${YELLOW}Version:${NC} ${VERSION}"
echo -e "${YELLOW}Output:${NC} ${ZIP_NAME}"
echo ""

# Remove old zip if exists
if [ -f "${DIST_DIR}/${ZIP_NAME}" ]; then
    echo -e "${YELLOW}Removing old zip file...${NC}"
    rm "${DIST_DIR}/${ZIP_NAME}"
fi

echo -e "${BLUE}Building assets...${NC}"
npm run build

echo ""
echo -e "${BLUE}Staging files...${NC}"

# Create a temporary staging directory to ensure correct folder name in zip
STAGING_DIR="${PLUGIN_DIR}/build_staging"
TARGET_DIR="${STAGING_DIR}/${PLUGIN_SLUG}"

# Clean up any previous staging
rm -rf "$STAGING_DIR"
mkdir -p "$TARGET_DIR"

# Copy files using rsync
# We exclude dev files, git files, and source maps/src
rsync -av "$PLUGIN_DIR/" "$TARGET_DIR/" \
    --exclude 'node_modules' \
    --exclude '.git' \
    --exclude '.github' \
    --exclude '.gitignore' \
    --exclude '.gitattributes' \
    --exclude '.editorconfig' \
    --exclude '.eslintrc.js' \
    --exclude '.stylelintrc.json' \
    --exclude 'src' \
    --exclude 'webpack.config.js' \
    --exclude 'package.json' \
    --exclude 'package-lock.json' \
    --exclude 'composer.json' \
    --exclude 'composer.lock' \
    --exclude 'phpcs.xml' \
    --exclude 'phpunit.xml' \
    --exclude 'tests' \
    --exclude '.DS_Store' \
    --exclude 'create-zip.sh' \
    --exclude 'GUTENBERG_SETUP.md' \
    --exclude 'build_staging' \
    --exclude '*.zip' \
    --exclude '*.md' \
    --exclude '*.sh' \
    --exclude '*.py' \
    --exclude 'venv' \
    --exclude 'assets/screenshot-*.png' \
    --exclude 'assets/icon-*.png' \
    --exclude 'assets/banner-*.png' \
    > /dev/null

# Copy README.txt specifically if needed, but it's not excluded (it's .txt)

echo -e "${BLUE}Creating zip file...${NC}"

# Create zip from staging
cd "$STAGING_DIR"
zip -r "${DIST_DIR}/${ZIP_NAME}" "${PLUGIN_SLUG}" > /dev/null

# Cleanup
cd "$PLUGIN_DIR"
rm -rf "$STAGING_DIR"

echo ""
echo -e "${GREEN}✓ Success!${NC}"
echo ""
echo -e "${BLUE}Distribution zip created:${NC}"
echo -e "${DIST_DIR}/${ZIP_NAME}"
echo ""

# Show zip contents summary
echo -e "${BLUE}Zip contents summary:${NC}"
unzip -l "${DIST_DIR}/${ZIP_NAME}" | tail -1

echo ""
echo -e "${GREEN}Ready to upload to WordPress!${NC}"
