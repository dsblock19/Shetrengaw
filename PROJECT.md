# SHETRENGAW — Project Description & Deployment Guide

This project contains the online multiplayer version of **SHETRENGAW — The Game of the Two Peoples**. It has been integrated into the local Raspberry Pi web architecture as both a standalone application and a fully integrated WordPress portal.

---

## 1. Project Overview

SHETRENGAW is an asymmetric strategy game played on an 8x8 board. It runs on a MariaDB/MySQL backend database to maintain real-time states and synchronize moves between players.

### Core Features:
- **Asymmetric Rules**: Implements v7 rules engine completely client-side in HTML/JavaScript.
- **Online Multiplayer**: Real-time state synchronization via a lightweight PHP API.
- **Game Polling**: Continuous game-state updates without web sockets (2-second HTTP polling).
- **Isolated Schema**: Runs on its own database, preventing crossover with WordPress tables.
- **WordPress Integration**: Features a custom plugin that creates a parent page (`shetrengaw-portal`) and child pages (Rules, Game, Gallery), and integrates an access button on the local homepage grid.

---

## 2. Directory Structure

The files are structured in the repository as follows:

```text
shetrengaw/
├── README.md                  # General game overview, pieces, rules, and history
├── PROJECT.md                 # This document (Project details and setup guide)
├── setup.sh                   # Bash automation script for deployment to a fresh Pi
├── rules/
│   └── Shetrengaw_Rulebook_v7.docx
├── game_files/
│   ├── images/                # Board and piece graphic assets
│   └── shetrengaw_html/
│       ├── shetrengaw_v7_stg.html  # Main game client
│       └── api/
│           ├── config.php     # Database connection and CORS headers
│           ├── game_api.php   # Handles state retrieval, moves, and polling
│           ├── new_game.php   # Generates unique IDs (SHTR-XXXX) and saves game init
│           └── setup.sql      # Database schema creation commands
└── wp-plugin/
    └── shetrengaw/
        ├── shetrengaw.php     # Core WordPress plugin code (Handles page creation and hooks)
        └── assets/
            └── css/
                └── style.css  # Dark, high-end theme styles for WordPress pages
```

When deployed to the Apache server, files are mapped to:
- **Game API & Standalone Backend**: `/var/www/html/shetrengaw/`
- **WordPress Plugin**: `/var/www/html/wp-content/plugins/shetrengaw/`

---

## 3. Technology Stack & Dependencies

The project relies on standard Debian/Raspberry Pi OS packages:
- **Web Server**: Apache 2 (`apache2`)
- **Database**: MariaDB Server (`mariadb-server`)
- **Runtime**: PHP 8.x (`php`, `php-mysql` for PDO extension)
- **Client**: HTML5, CSS3, Vanilla JavaScript (Cinzel & Crimson Text typography loaded via Google Fonts CDN)
- **CMS**: WordPress (local installation)

---

## 4. WordPress Shortcodes & Navigation

The plugin defines four custom shortcodes to render pages with consistent tabbed navigation:
- `[shetrengaw_home]`: Renders the parent portal home page with introductory lore and a directory grid linking to the child pages.
- `[shetrengaw_rules]`: Displays the gameplay guide, five-action terms table (Move: Vedux, Topple: Oniawxt, Kill: Nineut, Shelter: Dawminia, Drop: Pawrawt), and win conditions.
- `[shetrengaw_game]`: Embeds the active multiplayer game via a responsive HTML iframe.
- `[shetrengaw_gallery]`: Showcases the pieces of the Igto (Bronze and Amber) and Mawdige (Jade and Lapis) court sets in a side-by-side comparison layout.

---

## 5. Database Schema & Credentials

To ensure complete isolation, a separate database is configured:
- **Database Name**: `shetrengaw`
- **Database User**: `archivist`
- **Database Password**: `18sheuni19`
- **Database Host**: `localhost`

### Database Table: `games`
The game history and active sessions are stored in a single table:
```sql
CREATE TABLE games (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    game_id       VARCHAR(12) NOT NULL UNIQUE,       -- format SHTR-XXXX
    board_state   LONGTEXT NOT NULL,                 -- JSON serialized game state
    last_updated  BIGINT NOT NULL,                   -- UNIX epoch timestamp in ms
    created_at    DATETIME DEFAULT CURRENT_TIMESTAMP,
    status        ENUM('active','complete') DEFAULT 'active'
);
```

---

## 6. Deployment Guide (Transporting to a fresh Pi)

To set up this game on a completely new Raspberry Pi, follow these simple steps:

### Option A: Automated Setup (Recommended)
We have provided an automated deployment script `setup.sh` at the root of the repository.

1. Clone this repository onto the new Raspberry Pi:
   ```bash
   git clone <repository_url>
   cd Shetrengaw
   ```
2. Make the script executable and run it with `sudo`:
   ```bash
   sudo ./setup.sh
   ```
   *The script will automatically install Apache, MariaDB, PHP, create the database and user, import the schema, copy frontend/backend files into the web root, deploy the WordPress plugin, activate the plugin programmatically, and inject the card into your homepage (ID 21).*

### Option B: Manual Setup
If you prefer to configure the system manually:

1. **Install Packages**:
   ```bash
   sudo apt update
   sudo apt install apache2 mariadb-server php php-mysql curl -y
   ```
2. **Setup the Database**:
   Log in to MariaDB as root:
   ```bash
   sudo mysql
   ```
   Run the SQL statements:
   ```sql
   CREATE DATABASE IF NOT EXISTS shetrengaw CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   CREATE USER IF NOT EXISTS 'archivist'@'localhost' IDENTIFIED BY '18sheuni19';
   GRANT ALL PRIVILEGES ON shetrengaw.* TO 'archivist'@'localhost';
   FLUSH PRIVILEGES;
   ```
3. **Import Database Schema**:
   ```bash
   mysql -u archivist -p18sheuni19 shetrengaw < game_files/shetrengaw_html/api/setup.sql
   ```
4. **Deploy Standalone Web Files & Images**:
   Create the directory:
   ```bash
   sudo mkdir -p /var/www/html/shetrengaw/api
   sudo mkdir -p /var/www/html/shetrengaw/images
   ```
   Copy the files:
   ```bash
   sudo cp game_files/shetrengaw_html/shetrengaw_v7_stg.html /var/www/html/shetrengaw/index.html
   sudo cp game_files/shetrengaw_html/api/*.php /var/www/html/shetrengaw/api/
   sudo cp -r game_files/images/* /var/www/html/shetrengaw/images/
   ```
5. **Deploy & Activate WordPress Plugin**:
   Create directory:
   ```bash
   sudo mkdir -p /var/www/html/wp-content/plugins/shetrengaw
   ```
   Copy plugin files:
   ```bash
   sudo cp -r wp-plugin/shetrengaw/* /var/www/html/wp-content/plugins/shetrengaw/
   ```
   Set permissions and ownership:
   ```bash
   sudo chown -R www-data:www-data /var/www/html/shetrengaw
   sudo chown -R www-data:www-data /var/www/html/wp-content/plugins/shetrengaw
   sudo chmod -R 755 /var/www/html/shetrengaw
   sudo chmod -R 755 /var/www/html/wp-content/plugins/shetrengaw
   ```
   Activate the plugin programmatically:
   ```bash
   sudo php -r "define('WP_ADMIN', true); require_once '/var/www/html/wp-load.php'; require_once ABSPATH . 'wp-admin/includes/plugin.php'; activate_plugin('shetrengaw/shetrengaw.php');"
   ```
6. **Restart Apache**:
   ```bash
   sudo systemctl restart apache2
   ```

Once deployed, access the portal via browser:
- Local website homepage: `http://localhost/` (Click on the new card!)
- Direct portal page: `http://localhost/shetrengaw-portal/`
- Direct game link: `http://localhost/shetrengaw/`
