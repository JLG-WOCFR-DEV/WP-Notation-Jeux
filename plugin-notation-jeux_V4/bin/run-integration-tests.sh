#!/usr/bin/env bash
set -euo pipefail

DB_NAME=${WP_TESTS_DB_NAME:-wordpress_test}
DB_USER=${WP_TESTS_DB_USER:-root}
DB_PASS=${WP_TESTS_DB_PASSWORD:-}
DB_HOST=${WP_TESTS_DB_HOST:-127.0.0.1}
WP_VERSION=${WP_TESTS_WP_VERSION:-latest}
SKIP_DB_CREATE=${WP_TESTS_SKIP_DB_CREATE:-false}

"$(dirname "$0")/install-wp-tests.sh" "$DB_NAME" "$DB_USER" "$DB_PASS" "$DB_HOST" "$WP_VERSION" "$SKIP_DB_CREATE"

export WP_TESTS_DIR=${WP_TESTS_DIR:-$(pwd)/tests/integration/wordpress-tests-lib}
export WP_CORE_DIR=${WP_CORE_DIR:-$(pwd)/tests/integration/wordpress}

phpunit -c phpunit.integration.xml.dist "$@"
