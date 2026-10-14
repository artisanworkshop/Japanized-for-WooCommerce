#!/usr/bin/env bash
#
# Run PHPUnit against a test environment that belongs to this repository only.
#
# The WordPress core, the WordPress test library and WooCommerce live under
# JP4WC_TEST_ENV_DIR (default: ~/.cache/jp4wc-tests), not in the $TMPDIR copy
# that other projects' install-wp-tests.sh scripts rewrite, and the database
# runs in its own container that is left running between runs.
#
# Usage: composer test-local [-- <phpunit arguments>]
#        bash bin/test-local.sh [<phpunit arguments>]
#
# Environment:
#   JP4WC_TEST_DB_PORT  Host port of the MySQL container (default: 10104,
#                       the spare port +4 of this repository's dev-env slot 10).
#   JP4WC_TEST_ENV_DIR  Where the test environment is installed.
#
# CI does not use this script; it runs bin/install-wp-tests.sh.

set -euo pipefail

DB_PORT="${JP4WC_TEST_DB_PORT:-10104}"
ENV_DIR="${JP4WC_TEST_ENV_DIR:-${XDG_CACHE_HOME:-$HOME/.cache}/jp4wc-tests}"
CONTAINER_NAME="jp4wc-phpunit-db"
DB_NAME="wordpress_test"
DB_USER="root"
DB_PASS="root"
DB_HOST="127.0.0.1"

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"

die() {
	echo "test-local: $*" >&2
	exit 1
}

if ! [[ "$DB_PORT" =~ ^[0-9]+$ ]] || [ "$DB_PORT" -lt 1 ] || [ "$DB_PORT" -gt 65535 ]; then
	die "JP4WC_TEST_DB_PORT must be a port number, got '$DB_PORT'."
fi

command -v docker >/dev/null 2>&1 || die "docker is not installed."
docker info >/dev/null 2>&1 || die "Docker is not running. Start Docker Desktop and try again."
command -v php >/dev/null 2>&1 || die "php is not installed."
for tool in svn curl tar unzip; do
	command -v "$tool" >/dev/null 2>&1 || die "$tool is not installed. It is needed to install the test environment."
done
php -r 'exit( extension_loaded( "mysqli" ) ? 0 : 1 );' || die "the PHP mysqli extension is not loaded."
[ -x "$ROOT_DIR/vendor/bin/phpunit" ] || die "vendor/bin/phpunit is missing. Run 'composer install' first."

# Print who is listening on the port, or nothing when it is free.
port_owner() {
	local owner
	owner="$(docker ps --format '{{.Names}}	{{.Ports}}' | grep -E "[:]${DB_PORT}->" | cut -f1 | head -n 1 || true)"
	if [ -n "$owner" ]; then
		echo "Docker container '$owner'"
		return
	fi
	if command -v lsof >/dev/null 2>&1; then
		owner="$(lsof -nP -iTCP:"$DB_PORT" -sTCP:LISTEN 2>/dev/null | awk 'NR == 2 { print $1 " (PID " $2 ")" }' || true)"
		if [ -n "$owner" ]; then
			echo "process $owner"
		fi
	fi
}

ensure_port_free() {
	local owner
	owner="$(port_owner)"
	if [ -n "$owner" ]; then
		die "port $DB_PORT is already used by $owner. Stop it, or choose another port: JP4WC_TEST_DB_PORT=<port> composer test-local"
	fi
}

start_new_container() {
	ensure_port_free
	echo "Starting MySQL container $CONTAINER_NAME on $DB_HOST:$DB_PORT..."
	docker run -d \
		--name "$CONTAINER_NAME" \
		-e MYSQL_ROOT_PASSWORD="$DB_PASS" \
		-e MYSQL_DATABASE="$DB_NAME" \
		-p "$DB_HOST:$DB_PORT:3306" \
		mysql:8.0 \
		--default-authentication-plugin=mysql_native_password >/dev/null
}

# The host port is fixed when a container is created, so a container that was
# created for another port is replaced. It only holds the throwaway test database.
running="$(docker inspect -f '{{.State.Running}}' "$CONTAINER_NAME" 2>/dev/null || true)"
if [ -z "$running" ]; then
	start_new_container
else
	bound_port="$(docker inspect -f '{{range $p, $b := .HostConfig.PortBindings}}{{if eq $p "3306/tcp"}}{{range $b}}{{.HostPort}}{{end}}{{end}}{{end}}' "$CONTAINER_NAME")"
	if [ "$bound_port" != "$DB_PORT" ]; then
		echo "Container $CONTAINER_NAME uses port ${bound_port:-none}, not $DB_PORT. Recreating it..."
		docker rm -f "$CONTAINER_NAME" >/dev/null
		start_new_container
	elif [ "$running" != "true" ]; then
		ensure_port_free
		echo "Starting the stopped MySQL container $CONTAINER_NAME..."
		docker start "$CONTAINER_NAME" >/dev/null
	fi
fi

# mysql:8.0 initialises the database with a temporary server that does not
# listen on TCP and then restarts, so a successful TCP query means the real
# server is up. The hostname check proves the port reaches our container.
expected_host="$(docker inspect -f '{{.Config.Hostname}}' "$CONTAINER_NAME")"
echo "Waiting for MySQL on $DB_HOST:$DB_PORT..."
server_host=""
for _ in $(seq 1 60); do
	# shellcheck disable=SC2016 # The PHP code is meant to be single-quoted.
	server_host="$(DB_HOST="$DB_HOST" DB_PORT="$DB_PORT" DB_USER="$DB_USER" DB_PASS="$DB_PASS" php -r '
		mysqli_report( MYSQLI_REPORT_OFF );
		$link = @mysqli_connect( getenv( "DB_HOST" ), getenv( "DB_USER" ), getenv( "DB_PASS" ), "", (int) getenv( "DB_PORT" ) );
		if ( ! $link ) { exit( 1 ); }
		$result = mysqli_query( $link, "SELECT @@hostname" );
		if ( ! $result ) { exit( 1 ); }
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

export WP_TESTS_DIR="$ENV_DIR/wordpress-tests-lib"
export WP_CORE_DIR="$ENV_DIR/wordpress"

JP4WC_TEST_CONTAINER="$CONTAINER_NAME" JP4WC_TEST_REQUIRE_STAMP=1 bash "$ROOT_DIR/bin/setup-test-env.sh" \
	"$DB_NAME" "$DB_USER" "$DB_PASS" "$DB_HOST" "$DB_PORT" latest ||
	die "the test environment could not be set up (see above)."

# setup-test-env.sh reports success even when a download failed, and skips what
# already exists on the next run, so check that the install is complete.
for required in \
	"$WP_TESTS_DIR/includes/functions.php" \
	"$WP_TESTS_DIR/.jp4wc-tests-tag" \
	"$WP_CORE_DIR/wp-settings.php" \
	"$WP_CORE_DIR/wp-content/plugins/woocommerce/woocommerce.php"; do
	[ -f "$required" ] || die "the test environment is incomplete: $required is missing. Delete $ENV_DIR and run this again."
done

cd "$ROOT_DIR"
exec vendor/bin/phpunit "$@"
