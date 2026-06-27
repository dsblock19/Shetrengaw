#!/usr/bin/env bash
# =====================================================================
# INTEGRATED INSTALLER: SHETRENGAW STRATEGY GAME
# =====================================================================
# This script is called by BlockMania's master setup script to
# install Shetrengaw on top of an already running site setup.
# =====================================================================

set -euo pipefail

# Ensure the script is run with root privileges
if [ "$EUID" -ne 0 ]; then
  echo "Error: Please run this script with sudo or as root: sudo $0"
  exit 1
fi

REPO_DIR="/home/sg/Shetrengaw"
DB_NAME="shetrengaw"
DB_USER="archivist"
DB_PASS="18sheuni19"

echo "1. Configuring Shetrengaw Database & User..."
mysql -e "CREATE DATABASE IF NOT EXISTS ${DB_NAME} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -e "CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';"
mysql -e "GRANT ALL PRIVILEGES ON ${DB_NAME}.* TO '${DB_USER}'@'localhost';"
mysql -e "FLUSH PRIVILEGES;"

SQL_SCHEMA="${REPO_DIR}/game_files/shetrengaw_html/api/setup.sql"
if [ -f "$SQL_SCHEMA" ]; then
  echo " - Importing database schema from ${SQL_SCHEMA}..."
  mysql -u "${DB_USER}" -p"${DB_PASS}" "${DB_NAME}" < "$SQL_SCHEMA"
else
  echo "Error: setup.sql schema file not found in ${SQL_SCHEMA}"
  exit 1
fi

echo "2. Deploying Standalone Game Files..."
WEB_DIR="/var/www/html/shetrengaw"
mkdir -p "${WEB_DIR}/api"

cp "${REPO_DIR}/game_files/shetrengaw_html/shetrengaw_v7_stg.html" "${WEB_DIR}/index.html"
cp "${REPO_DIR}/game_files/shetrengaw_html/api/config.php" "${WEB_DIR}/api/config.php"
cp "${REPO_DIR}/game_files/shetrengaw_html/api/game_api.php" "${WEB_DIR}/api/game_api.php"
cp "${REPO_DIR}/game_files/shetrengaw_html/api/new_game.php" "${WEB_DIR}/api/new_game.php"
cp "${REPO_DIR}/game_files/shetrengaw_html/api/setup.sql" "${WEB_DIR}/api/setup.sql"

mkdir -p "${WEB_DIR}/images"
cp -r "${REPO_DIR}/game_files/images/"* "${WEB_DIR}/images/"

chown -R www-data:www-data "$WEB_DIR"
chmod -R 755 "$WEB_DIR"

echo "3. Deploying and Activating WordPress Plugin..."
WP_PLUGIN_DIR="/var/www/html/wp-content/plugins/shetrengaw"
mkdir -p "$WP_PLUGIN_DIR"
cp -r "${REPO_DIR}/wp-plugin/shetrengaw/"* "$WP_PLUGIN_DIR/"

chown -R www-data:www-data "$WP_PLUGIN_DIR"
chmod -R 755 "$WP_PLUGIN_DIR"

if [ -f "/var/www/html/wp-load.php" ]; then
  php -r "
    define('WP_ADMIN', true);
    require_once '/var/www/html/wp-load.php';
    require_once ABSPATH . 'wp-admin/includes/plugin.php';
    if (!is_plugin_active('shetrengaw/shetrengaw.php')) {
        activate_plugin('shetrengaw/shetrengaw.php');
        echo 'Shetrengaw plugin activated successfully!\n';
    } else {
        echo 'Shetrengaw plugin is already active.\n';
    }
  "
else
  echo "Warning: wp-load.php not found. Skipping plugin activation."
fi

echo "Shetrengaw integrated installation complete."
