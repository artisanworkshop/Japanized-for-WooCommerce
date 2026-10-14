#!/usr/bin/env bash

# WordPress テスト環境をセットアップするスクリプト

DB_NAME=${1-wordpress_test}
DB_USER=${2-root}
DB_PASS=${3-root}
DB_HOST=${4-127.0.0.1}
DB_PORT=${5-3306}
WP_VERSION=${6-latest}

# bin/test-local.sh passes its own container through JP4WC_TEST_CONTAINER.
CONTAINER_NAME="${JP4WC_TEST_CONTAINER:-jp4wc-mysql-test}"

# MySQLコンテナを起動（既に起動している場合はスキップ）
echo "Starting MySQL container..."
if [ -z "$(docker ps -q --filter "name=^/${CONTAINER_NAME}$")" ]; then
    # 既存のコンテナがあれば削除
    docker rm -f ${CONTAINER_NAME} 2>/dev/null || true
    
    # 新しいコンテナを起動
    docker run -d \
        --name ${CONTAINER_NAME} \
        -e MYSQL_ROOT_PASSWORD=${DB_PASS} \
        -e MYSQL_DATABASE=${DB_NAME} \
        -p ${DB_PORT}:3306 \
        mysql:8.0 \
        --default-authentication-plugin=mysql_native_password
    
    echo "Waiting for MySQL to be ready..."
    for i in {1..30}; do
        if docker exec ${CONTAINER_NAME} mysqladmin ping -h"localhost" --silent 2>/dev/null; then
            echo "MySQL is ready!"
            break
        fi
        echo "Waiting for MySQL... ($i/30)"
        sleep 2
    done
else
    echo "MySQL container is already running"
fi

# データベースが存在するか確認
echo "Checking database..."
DB_EXISTS=$(docker exec ${CONTAINER_NAME} mysql -u${DB_USER} -p${DB_PASS} -e "SHOW DATABASES LIKE '${DB_NAME}';" 2>/dev/null | grep ${DB_NAME} || true)

if [ -z "$DB_EXISTS" ]; then
    echo "Creating database ${DB_NAME}..."
    docker exec ${CONTAINER_NAME} mysql -u${DB_USER} -p${DB_PASS} -e "CREATE DATABASE IF NOT EXISTS ${DB_NAME};" 2>/dev/null
    echo "Database created successfully!"
else
    echo "Database ${DB_NAME} already exists"
fi

# WordPressテストライブラリをインストール
echo "Installing WordPress test suite..."

TMPDIR=${TMPDIR-/tmp}
TMPDIR=$(echo $TMPDIR | sed -e "s/\/$//")
WP_TESTS_DIR=${WP_TESTS_DIR-$TMPDIR/wordpress-tests-lib}
WP_CORE_DIR=${WP_CORE_DIR-$TMPDIR/wordpress}

download() {
    if [ `which curl` ]; then
        curl -s "$1" > "$2";
    elif [ `which wget` ]; then
        wget -nv -O "$2" "$1"
    fi
}

# The core and the test library must be the same release: the trunk test library can
# need code that a release does not have. An installed core decides the version;
# otherwise "latest" is resolved to a version number (only then, so that later runs
# work offline).
if [ -f "$WP_CORE_DIR"/wp-includes/version.php ]; then
	WP_VERSION=$(sed -n "s/^\$wp_version = '\(.*\)';\$/\1/p" "$WP_CORE_DIR"/wp-includes/version.php)
	echo "WordPress in $WP_CORE_DIR: $WP_VERSION"
elif [[ $WP_VERSION == 'latest' ]]; then
	download https://api.wordpress.org/core/version-check/1.7/ "$TMPDIR"/jp4wc-wp-latest.json
	WP_VERSION=$(grep -o '"version":"[^"]*' "$TMPDIR"/jp4wc-wp-latest.json | head -1 | sed 's/"version":"//')
	if [[ -z $WP_VERSION ]]; then
		echo "Latest WordPress version could not be found"
		exit 1
	fi
	echo "Latest WordPress version: $WP_VERSION"
fi

install_wp() {
	if [ -d $WP_CORE_DIR ]; then
		return;
	fi

	mkdir -p $WP_CORE_DIR

	if [[ $WP_VERSION == 'nightly' || $WP_VERSION == 'trunk' ]]; then
		local ARCHIVE_NAME='nightly'
	else
		# https serves multiple requests faster
		local ARCHIVE_NAME="wordpress-$WP_VERSION"
	fi

	download https://wordpress.org/${ARCHIVE_NAME}.tar.gz  /tmp/wordpress.tar.gz
	tar --strip-components=1 -zxmf /tmp/wordpress.tar.gz -C $WP_CORE_DIR
}

install_woocommerce() {
	local WC_DIR=$WP_CORE_DIR/wp-content/plugins/woocommerce
	
	if [ -d $WC_DIR ]; then
		echo "WooCommerce already installed"
		return;
	fi
	
	echo "Installing WooCommerce..."
	mkdir -p $WP_CORE_DIR/wp-content/plugins
	
	# Download latest WooCommerce
	download https://downloads.wordpress.org/plugin/woocommerce.zip /tmp/woocommerce.zip
	
	if [ -f /tmp/woocommerce.zip ]; then
		unzip -q /tmp/woocommerce.zip -d $WP_CORE_DIR/wp-content/plugins/
		echo "WooCommerce installed successfully"
	else
		echo "Warning: Failed to download WooCommerce"
	fi
}

install_test_suite() {
	# portable in-place argument for both GNU sed and Mac OSX sed
	if [[ $(uname -s) == 'Darwin' ]]; then
		local ioption='-i.bak'
	else
		local ioption='-i'
	fi

	local WP_TESTS_TAG
	if [[ $WP_VERSION =~ ^[0-9]+\.[0-9]+\.0$ ]]; then
		# x.y.0 is tagged as x.y
		WP_TESTS_TAG="tags/${WP_VERSION%.0}"
	elif [[ $WP_VERSION =~ ^[0-9]+\.[0-9]+(\.[0-9]+)?$ ]]; then
		WP_TESTS_TAG="tags/$WP_VERSION"
	elif [[ $WP_VERSION =~ ^([0-9]+\.[0-9]+)-(beta|RC)[0-9]+$ ]]; then
		WP_TESTS_TAG="branches/${BASH_REMATCH[1]}"
	else
		# nightly, trunk, or an alpha core
		WP_TESTS_TAG="trunk"
	fi

	# The tag the test library was exported from, to catch a library that no longer
	# matches the core (e.g. a core reinstalled at a newer version).
	local STAMP="$WP_TESTS_DIR"/.jp4wc-tests-tag

	# set up testing suite if it doesn't yet exist
	if [ ! -d $WP_TESTS_DIR ]; then
		# set up testing suite
		mkdir -p $WP_TESTS_DIR
		rm -rf $WP_TESTS_DIR/{includes,data}
		svn export --quiet --ignore-externals https://develop.svn.wordpress.org/${WP_TESTS_TAG}/tests/phpunit/includes/ $WP_TESTS_DIR/includes
		svn export --quiet --ignore-externals https://develop.svn.wordpress.org/${WP_TESTS_TAG}/tests/phpunit/data/ $WP_TESTS_DIR/data
		if [ -f "$WP_TESTS_DIR"/includes/functions.php ] && [ -d "$WP_TESTS_DIR"/data ]; then
			echo "$WP_TESTS_TAG" > "$STAMP"
		fi
	elif [ -f "$STAMP" ]; then
		if [ "$(cat "$STAMP")" != "$WP_TESTS_TAG" ]; then
			echo "The WordPress test library in $WP_TESTS_DIR is from $(cat "$STAMP"), but WordPress $WP_VERSION needs $WP_TESTS_TAG."
			echo "Delete $WP_TESTS_DIR and $WP_CORE_DIR, then run this again to reinstall both."
			exit 1
		fi
	elif [ -n "${JP4WC_TEST_REQUIRE_STAMP:-}" ]; then
		echo "The WordPress test library in $WP_TESTS_DIR was not completely installed by this script, so its version cannot be checked."
		echo "Delete $WP_TESTS_DIR and $WP_CORE_DIR, then run this again to reinstall both."
		exit 1
	fi

	if [ ! -s "$WP_TESTS_DIR"/wp-tests-config-sample.php ]; then
		download https://develop.svn.wordpress.org/${WP_TESTS_TAG}/wp-tests-config-sample.php "$WP_TESTS_DIR"/wp-tests-config-sample.php
	fi

	# Write the config on every run, so it always points at the database given here.
	cp "$WP_TESTS_DIR"/wp-tests-config-sample.php "$WP_TESTS_DIR"/wp-tests-config.php
	# remove all forward slashes in the end
	WP_CORE_DIR=$(echo $WP_CORE_DIR | sed "s:/\+$::")
	sed $ioption "s:dirname( __FILE__ ) . '/src/':'$WP_CORE_DIR/':" "$WP_TESTS_DIR"/wp-tests-config.php
	sed $ioption "s:__DIR__ . '/src/':'$WP_CORE_DIR/':" "$WP_TESTS_DIR"/wp-tests-config.php
	sed $ioption "s/youremptytestdbnamehere/$DB_NAME/" "$WP_TESTS_DIR"/wp-tests-config.php
	sed $ioption "s/yourusernamehere/$DB_USER/" "$WP_TESTS_DIR"/wp-tests-config.php
	sed $ioption "s/yourpasswordhere/$DB_PASS/" "$WP_TESTS_DIR"/wp-tests-config.php
	sed $ioption "s|localhost|${DB_HOST}:${DB_PORT}|" "$WP_TESTS_DIR"/wp-tests-config.php
}

install_wp
install_woocommerce
install_test_suite

echo ""
echo "Test environment setup complete!"
echo ""
echo "To run tests: composer test (or composer test-local, which keeps its own environment and container)"
echo "To stop MySQL container: docker stop ${CONTAINER_NAME}"
echo "To remove MySQL container: docker rm ${CONTAINER_NAME}"
