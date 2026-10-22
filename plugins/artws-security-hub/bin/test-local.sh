#!/usr/bin/env bash
#
# Run this plugin's PHPUnit tests against the test environment that the
# repository root's `composer test-local` installs and starts: the WordPress
# core and test library under JP4WC_TEST_ENV_DIR (default
# ~/.cache/jp4wc-tests) and the MySQL container jp4wc-phpunit-db. The tests
# use a database of their own (artws_sec_tests), never JP4WC's.
#
# Usage: composer test-local [-- <phpunit arguments>]
#        bash bin/test-local.sh [<phpunit arguments>]
#
# Environment (the same as the root script's):
#   JP4WC_TEST_DB_PORT  Host port of the MySQL container (default: 10104).
#   JP4WC_TEST_ENV_DIR  Where the root script installed the test environment.
#
# CI does not use this script; it runs the root's bin/install-wp-tests.sh.

set -euo pipefail

DB_PORT="${JP4WC_TEST_DB_PORT:-10104}"
ENV_DIR="${JP4WC_TEST_ENV_DIR:-${XDG_CACHE_HOME:-$HOME/.cache}/jp4wc-tests}"
CONTAINER_NAME="jp4wc-phpunit-db"
DB_NAME="artws_sec_tests"
DB_USER="root"
DB_PASS="root"
DB_HOST="127.0.0.1"

PLUGIN_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
WP_TESTS_DIR="$ENV_DIR/wordpress-tests-lib"
CONFIG_FILE="$ENV_DIR/artws-sec-tests-config.php"

die() {
	echo "test-local: $*" >&2
	exit 1
}

setup_hint="Run 'composer test-local' once at the repository root: it installs WordPress and starts the database this script uses."

if ! [[ "$DB_PORT" =~ ^[0-9]+$ ]] || [ "$DB_PORT" -lt 1 ] || [ "$DB_PORT" -gt 65535 ]; then
	die "JP4WC_TEST_DB_PORT must be a port number, got '$DB_PORT'."
fi

for required in "$WP_TESTS_DIR/includes/functions.php" "$WP_TESTS_DIR/wp-tests-config.php" "$ENV_DIR/wordpress/wp-settings.php"; do
	[ -f "$required" ] || die "$required is missing. $setup_hint"
done
[ -x "$PLUGIN_DIR/vendor/bin/phpunit" ] || die "vendor/bin/phpunit is missing. Run 'composer install' in $PLUGIN_DIR first."

command -v docker >/dev/null 2>&1 || die "docker is not installed."
docker info >/dev/null 2>&1 || die "Docker is not running. Start Docker Desktop and try again."
php -r 'exit( extension_loaded( "mysqli" ) ? 0 : 1 );' || die "the PHP mysqli extension is not loaded."

running="$(docker inspect -f '{{.State.Running}}' "$CONTAINER_NAME" 2>/dev/null || true)"
[ -n "$running" ] || die "the MySQL container $CONTAINER_NAME does not exist. $setup_hint"

bound_port="$(docker inspect -f '{{range $p, $b := .HostConfig.PortBindings}}{{if eq $p "3306/tcp"}}{{range $b}}{{.HostPort}}{{end}}{{end}}{{end}}' "$CONTAINER_NAME")"
if [ "$bound_port" != "$DB_PORT" ]; then
	die "container $CONTAINER_NAME uses port ${bound_port:-none}, not $DB_PORT. $setup_hint"
fi
if [ "$running" != "true" ]; then
	echo "Starting the stopped MySQL container $CONTAINER_NAME..."
	docker start "$CONTAINER_NAME" >/dev/null
fi

# Wait for the server, check that the port reaches our container, and create
# this plugin's database.
expected_host="$(docker inspect -f '{{.Config.Hostname}}' "$CONTAINER_NAME")"
echo "Waiting for MySQL on $DB_HOST:$DB_PORT..."
server_host=""
for _ in $(seq 1 60); do
	# shellcheck disable=SC2016 # The PHP code is meant to be single-quoted.
	server_host="$(DB_HOST="$DB_HOST" DB_PORT="$DB_PORT" DB_USER="$DB_USER" DB_PASS="$DB_PASS" DB_NAME="$DB_NAME" php -r '
		mysqli_report( MYSQLI_REPORT_OFF );
		$link = @mysqli_connect( getenv( "DB_HOST" ), getenv( "DB_USER" ), getenv( "DB_PASS" ), "", (int) getenv( "DB_PORT" ) );
		if ( ! $link ) { exit( 1 ); }
		$result = mysqli_query( $link, "SELECT @@hostname" );
		if ( ! $result ) { exit( 1 ); }
		if ( ! mysqli_query( $link, "CREATE DATABASE IF NOT EXISTS `" . getenv( "DB_NAME" ) . "`" ) ) { exit( 1 ); }
		echo mysqli_fetch_row( $result )[0];
	' 2>/dev/null || true)"
	if [ -n "$server_host" ]; then
		break
	fi
	sleep 1
done
if [ -z "$server_host" ]; then
	die "MySQL on $DB_HOST:$DB_PORT did not accept connections within 60 seconds. Check 'docker logs $CONTAINER_NAME'."
fi
if [ "$server_host" != "$expected_host" ]; then
	die "$DB_HOST:$DB_PORT answered as '$server_host', not as container $CONTAINER_NAME ($expected_host). Another server is using the port."
fi

# The root script rewrites wp-tests-config.php on every run; copy it with this
# plugin's database and host.
sed -E \
	-e "s/define\( *'DB_NAME', *'[^']*' *\);/define( 'DB_NAME', '$DB_NAME' );/" \
	-e "s/define\( *'DB_HOST', *'[^']*' *\);/define( 'DB_HOST', '$DB_HOST:$DB_PORT' );/" \
	"$WP_TESTS_DIR/wp-tests-config.php" >"$CONFIG_FILE"
grep -q "define( 'DB_NAME', '$DB_NAME' );" "$CONFIG_FILE" || die "could not set DB_NAME in $CONFIG_FILE."
grep -q "define( 'DB_HOST', '$DB_HOST:$DB_PORT' );" "$CONFIG_FILE" || die "could not set DB_HOST in $CONFIG_FILE."

export WP_TESTS_DIR
export ARTWS_SEC_TESTS_CONFIG="$CONFIG_FILE"

cd "$PLUGIN_DIR"
exec vendor/bin/phpunit "$@"
