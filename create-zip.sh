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
echo -e "${BLUE}AI Content Engine - Build Distribution${NC}"
echo -e "${BLUE}=====================================${NC}"
echo ""

# Plugin directory
PLUGIN_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PLUGIN_NAME="ai-content-engine"
VERSION=$(grep "Version:" ai-content-engine.php | head -1 | awk '{print $3}')
ZIP_NAME="${PLUGIN_NAME}-v${VERSION}.zip"
DIST_DIR="${PLUGIN_DIR}"  # Output in current directory

echo -e "${YELLOW}Plugin:${NC} ${PLUGIN_NAME}"
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
echo -e "${BLUE}Creating zip file...${NC}"

# Create zip excluding unnecessary files
cd "${PLUGIN_DIR}/.."
zip -r "${DIST_DIR}/${ZIP_NAME}" "${PLUGIN_NAME}" \
    -x "${PLUGIN_NAME}/node_modules/*" \
    -x "${PLUGIN_NAME}/.git/*" \
    -x "${PLUGIN_NAME}/.github/*" \
    -x "${PLUGIN_NAME}/.gitignore" \
    -x "${PLUGIN_NAME}/.gitattributes" \
    -x "${PLUGIN_NAME}/.editorconfig" \
    -x "${PLUGIN_NAME}/.eslintrc.js" \
    -x "${PLUGIN_NAME}/.stylelintrc.json" \
    -x "${PLUGIN_NAME}/src/*" \
    -x "${PLUGIN_NAME}/webpack.config.js" \
    -x "${PLUGIN_NAME}/package.json" \
    -x "${PLUGIN_NAME}/package-lock.json" \
    -x "${PLUGIN_NAME}/composer.json" \
    -x "${PLUGIN_NAME}/composer.lock" \
    -x "${PLUGIN_NAME}/phpcs.xml" \
    -x "${PLUGIN_NAME}/phpunit.xml" \
    -x "${PLUGIN_NAME}/tests/*" \
    -x "${PLUGIN_NAME}/.DS_Store" \
    -x "${PLUGIN_NAME}/*/.DS_Store" \
    -x "${PLUGIN_NAME}/create-zip.sh" \
    -x "${PLUGIN_NAME}/GUTENBERG_SETUP.md" \
    -x "*__MACOSX*" \
    -x "*.md" \
    > /dev/null

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
echo -e "${BLUE}What's included:${NC}"
echo "  ✓ PHP plugin files"
echo "  ✓ Built JavaScript/CSS (build/)"
echo "  ✓ Assets (assets/)"
echo "  ✓ Templates"
echo "  ✓ README.txt for WordPress.org"
echo ""
echo -e "${BLUE}What's excluded:${NC}"
echo "  ✗ Source files (src/)"
echo "  ✗ node_modules/"
echo "  ✗ Development config files"
echo "  ✗ Git files"
echo "  ✗ Tests"
echo ""
echo -e "${GREEN}Ready to upload to WordPress!${NC}"
