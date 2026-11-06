#!/usr/bin/env bash
# Downloads the WordPress core files the tests load into .cache/wordpress/.
# Usage: bin/install-wp-core.sh [version]   (default: the version pinned below)
set -euo pipefail

VERSION="${1:-${WP_TOC_WP_VERSION:-7.1.4}}"
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
DEST="$ROOT/.cache/wordpress"
STAMP="$DEST/.version"

if [ -f "$STAMP" ] && [ "$(cat "$STAMP")" = "$VERSION" ]; then
	exit 0
fi

TMP="$(mktemp -d)"
trap 'rm -rf "$TMP"' EXIT

echo "Downloading WordPress $VERSION..."
curl -fsSL "https://wordpress.org/wordpress-${VERSION}.tar.gz" -o "$TMP/wp.tar.gz"
tar -xzf "$TMP/wp.tar.gz" -C "$TMP"

rm -rf "$DEST"
mkdir -p "$DEST"
mv "$TMP/wordpress/wp-includes" "$DEST/wp-includes"
echo "$VERSION" > "$STAMP"
echo "WordPress $VERSION installed in .cache/wordpress"
