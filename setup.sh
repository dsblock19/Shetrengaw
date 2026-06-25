#!/usr/bin/env bash

# Exit immediately if a command exits with a non-zero status
set -e

# Colors for terminal output
RED='\033[0;31m'
GREEN='\033[0;32m'
NC='\033[0m' # No Color

echo -e "${GREEN}==================================================${NC}"
echo -e "${GREEN}      SHETRENGAW — Pi Setup & Deployment          ${NC}"
echo -e "${GREEN}==================================================${NC}"

# Ensure the script is run with sudo privileges
if [ "$EUID" -ne 0 ]; then
  echo -e "${RED}Error: Please run this script with sudo (e.g., sudo ./setup.sh)${NC}"
  exit 1
fi

# Get the script's directory to reference repository files
REPO_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" >/dev/null 2>&1 && pwd )"

echo -e "\n1. Checking and installing package dependencies..."
apt-get update

# List of packages needed
PACKAGES=(apache2 mariadb-server php php-mysql curl)

for pkg in "${PACKAGES[@]}"; do
  if dpkg -s "$pkg" >/dev/null 2>&1; then
    echo -e " - $pkg is already installed."
  else
    echo -e " - Installing $pkg..."
    apt-get install -y "$pkg"
  fi
done

# Restart services to ensure they are clean
systemctl enable apache2
systemctl start apache2
systemctl enable mariadb
systemctl start mariadb

echo -e "\n2. Configuring MariaDB Database & User..."
# Define credentials (matches default config.php)
DB_NAME="shetrengaw"
DB_USER="archivist"
DB_PASS="18sheuni19"

# Create database and user if they do not exist
mysql -e "CREATE DATABASE IF NOT EXISTS ${DB_NAME} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -e "CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';"
mysql -e "GRANT ALL PRIVILEGES ON ${DB_NAME}.* TO '${DB_USER}'@'localhost';"
mysql -e "FLUSH PRIVILEGES;"

echo -e " - Database and user created/verified successfully."

# Initialize database schema from setup.sql
SQL_SCHEMA="${REPO_DIR}/game_files/shetrengaw_html/api/setup.sql"
if [ -f "$SQL_SCHEMA" ]; then
  echo -e " - Importing database schema from ${SQL_SCHEMA}..."
  mysql -u "${DB_USER}" -p"${DB_PASS}" "${DB_NAME}" < "$SQL_SCHEMA"
else
  echo -e "${RED}Error: setup.sql schema file not found in ${REPO_DIR}/game_files/shetrengaw_html/api/setup.sql${NC}"
  exit 1
fi

echo -e "\n3. Deploying application to Apache Web Root..."
WEB_DIR="/var/www/html/shetrengaw"
mkdir -p "${WEB_DIR}/api"

# Copy main game HTML
cp "${REPO_DIR}/game_files/shetrengaw_html/shetrengaw_v7_stg.html" "${WEB_DIR}/index.html"

# Copy API files
cp "${REPO_DIR}/game_files/shetrengaw_html/api/config.php" "${WEB_DIR}/api/config.php"
cp "${REPO_DIR}/game_files/shetrengaw_html/api/game_api.php" "${WEB_DIR}/api/game_api.php"
cp "${REPO_DIR}/game_files/shetrengaw_html/api/new_game.php" "${WEB_DIR}/api/new_game.php"
cp "${REPO_DIR}/game_files/shetrengaw_html/api/setup.sql" "${WEB_DIR}/api/setup.sql"

# Set correct ownership and permissions for Apache
chown -R www-data:www-data "$WEB_DIR"
chmod -R 755 "$WEB_DIR"

echo -e " - Files deployed successfully to ${WEB_DIR}."

echo -e "\n4. Verifying Apache service configuration..."
systemctl restart apache2

# Get local IP
IP_ADDRESSES=$(hostname -I | awk '{print $1}')

echo -e "${GREEN}==================================================${NC}"
echo -e "${GREEN}           Setup Completed Successfully!          ${NC}"
echo -e "${GREEN}==================================================${NC}"
echo -e "You can now play Shetrengaw Online."
echo -e "Local browser URL:    http://localhost/shetrengaw/"
if [ ! -z "$IP_ADDRESSES" ]; then
  echo -e "Network browser URL:  http://${IP_ADDRESSES}/shetrengaw/"
fi
echo -e "Database name:        ${DB_NAME}"
echo -e "Database user:        ${DB_USER}"
echo -e "=================================================="
