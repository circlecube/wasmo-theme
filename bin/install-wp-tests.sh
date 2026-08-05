#!/usr/bin/env bash
# Install WordPress test library.
# Usage: bash bin/install-wp-tests.sh [db-name] [db-user] [db-pass] [db-host] [wp-version]
#
# Requires: curl or wget, git, mysql client
# Sets up:  $WP_TESTS_DIR (default /tmp/wordpress-tests-lib)

set -euo pipefail

DB_NAME="${1:-wordpress_test}"
DB_USER="${2:-root}"
DB_PASS="${3:-}"
DB_HOST="${4:-127.0.0.1}"
WP_VERSION="${5:-latest}"

WP_TESTS_DIR="${WP_TESTS_DIR:-/tmp/wordpress-tests-lib}"

# ---- helpers ----------------------------------------------------------------

download() {
    if command -v curl &>/dev/null; then
        curl -s "$1" > "$2"
    elif command -v wget &>/dev/null; then
        wget -nv -O "$2" "$1"
    else
        echo "Neither curl nor wget found." >&2; exit 1
    fi
}

resolve_wp_version() {
    if [ "$WP_VERSION" = "latest" ]; then
        local api
        api=$(download https://api.wordpress.org/core/version-check/1.7/ /dev/stdout 2>/dev/null || true)
        WP_VERSION=$(echo "$api" | grep -o '"version":"[^"]*"' | head -1 | sed 's/[^0-9.]//g')
        [ -n "$WP_VERSION" ] || WP_VERSION="6.8"
    fi
}

# ---- main -------------------------------------------------------------------

resolve_wp_version
echo "WordPress version: $WP_VERSION"
echo "WP_TESTS_DIR:      $WP_TESTS_DIR"

# Install tests lib via git sparse checkout (no svn required)
if [ ! -d "$WP_TESTS_DIR" ]; then
    mkdir -p "$WP_TESTS_DIR"
    git -C "$WP_TESTS_DIR" init -q
    git -C "$WP_TESTS_DIR" remote add origin https://github.com/WordPress/wordpress-develop.git
    git -C "$WP_TESTS_DIR" config core.sparseCheckout true
    printf 'src/wp-includes\nsrc/wp-admin\ntests/phpunit/includes\ntests/phpunit/data\n' \
        > "$WP_TESTS_DIR/.git/info/sparse-checkout"
    git -C "$WP_TESTS_DIR" fetch --depth=1 origin "tags/$WP_VERSION" 2>/dev/null \
        || git -C "$WP_TESTS_DIR" fetch --depth=1 origin HEAD
    git -C "$WP_TESTS_DIR" checkout -q FETCH_HEAD

    # Flatten: tests/phpunit/{includes,data} -> $WP_TESTS_DIR/{includes,data}
    mv "$WP_TESTS_DIR/tests/phpunit/includes" "$WP_TESTS_DIR/includes"
    mv "$WP_TESTS_DIR/tests/phpunit/data"     "$WP_TESTS_DIR/data" 2>/dev/null || true
    rm -rf "$WP_TESTS_DIR/tests"
fi

# Write wp-tests-config.php
cat > "$WP_TESTS_DIR/wp-tests-config.php" << PHP_EOF
<?php
define( 'ABSPATH', '${WP_TESTS_DIR}/src/' );
define( 'WP_DEBUG', true );
define( 'DB_NAME', '${DB_NAME}' );
define( 'DB_USER', '${DB_USER}' );
define( 'DB_PASSWORD', '${DB_PASS}' );
define( 'DB_HOST', '${DB_HOST}' );
define( 'DB_CHARSET', 'utf8' );
define( 'DB_COLLATE', '' );
\$table_prefix = 'wptests_';
define( 'WP_TESTS_DOMAIN', 'example.org' );
define( 'WP_TESTS_EMAIL', 'admin@example.org' );
define( 'WP_TESTS_TITLE', 'Wasmo Test' );
define( 'WP_PHP_BINARY', 'php' );
define( 'WPLANG', '' );
PHP_EOF

# Create the test database
if [ -n "$DB_PASS" ]; then
    MYSQL_ARGS=(-u"$DB_USER" -p"$DB_PASS" -h"$DB_HOST")
else
    MYSQL_ARGS=(-u"$DB_USER" -h"$DB_HOST")
fi
mysql "${MYSQL_ARGS[@]}" -e "CREATE DATABASE IF NOT EXISTS \`${DB_NAME}\`;" 2>/dev/null \
    && echo "Database '${DB_NAME}' ready." \
    || echo "::warning::Could not create DB '${DB_NAME}' — ensure it exists before running tests."

echo "Done."
