#!/usr/bin/env bash
# Downloads the WordPress core files the tests load into .cache/wordpress/.
# Usage: bin/install-wp-core.sh [version]   (default: WP_TOC_WP_VERSION or the version pinned below)
#
# Safe to run concurrently: installs are serialized with a lock (where flock is
# available), and new files are staged next to the old ones and swapped in with
# renames, so a test run never sees a half-extracted tree.
set -euo pipefail

VERSION="${1:-${WP_TOC_WP_VERSION:-7.1.4}}"
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
CACHE="$ROOT/.cache"
DEST="$CACHE/wordpress"
STAMP="$DEST/.version"

installed() {
	[ -f "$STAMP" ] && [ "$(cat "$STAMP")" = "$VERSION" ]
}

if installed; then
	exit 0
fi

mkdir -p "$CACHE"
if command -v flock >/dev/null 2>&1; then
	exec 9>"$CACHE/.install.lock"
	flock 9
	# Another run may have finished the install while this one waited.
	if installed; then
		exit 0
	fi
fi

STAGE="$(mktemp -d "$CACHE/wordpress.new.XXXXXX")"
OLD="$CACHE/wordpress.old.$$"
trap 'rm -rf "$STAGE" "$OLD"' EXIT

echo "Downloading WordPress $VERSION..."
curl -fsSL "https://wordpress.org/wordpress-${VERSION}.tar.gz" | tar -xz -C "$STAGE" wordpress/wp-includes
mv "$STAGE/wordpress/wp-includes" "$STAGE/wp-includes"
rmdir "$STAGE/wordpress"
echo "$VERSION" > "$STAGE/.version"

if [ -d "$DEST" ]; then
	mv "$DEST" "$OLD"
fi
mv "$STAGE" "$DEST"
echo "WordPress $VERSION installed in .cache/wordpress"
