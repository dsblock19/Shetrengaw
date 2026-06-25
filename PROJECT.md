# SHETRENGAW — Project Description & Deployment Guide

This project contains the online multiplayer version of **SHETRENGAW — The Game of the Two Peoples**. It has been migrated from its legacy WordPress setup into a fully isolated, database-backed web application on the Raspberry Pi.

---

## 1. Project Overview

SHETRENGAW is an asymmetric strategy game where players swap roles across a two-round session. The game is served via Apache and uses a MariaDB/MySQL backend database to maintain real-time board states and synchronize moves between players.

### Core Features:
- **Asymmetric Rules**: Implements v7 rules engine completely client-side in HTML/JavaScript.
- **Online Multiplayer**: Real-time board state synchronization via a lightweight PHP API.
- **Game Polling**: Continuous game-state updates without web sockets (using 2-second HTTP polling).
- **Isolated Schema**: Runs on its own database, preventing crossover with WordPress or other programs.

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
└── game_files/
    ├── images/                # Reference game board and piece graphics
    └── shetrengaw_html/
        ├── shetrengaw_v7_stg.html  # Main game client (Online & local play support)
        └── api/
            ├── config.php     # Database PDO connection and CORS headers
            ├── game_api.php   # Handles state retrieval, moves, and polling
            ├── new_game.php   # Generates unique IDs (SHTR-XXXX) and saves game initialization
            └── setup.sql      # Database schema creation commands
```

When deployed to the Apache server, files are mapped to:
- **Game Frontend**: `/var/www/html/shetrengaw/index.html`
- **Game API Backend**: `/var/www/html/shetrengaw/api/`

---

## 3. Technology Stack & Dependencies

The project relies on standard Debian/Raspberry Pi OS packages:
- **Web Server**: Apache 2 (`apache2`)
- **Database**: MariaDB Server (`mariadb-server`)
- **Runtime**: PHP 8.x (`php`, `php-mysql` for PDO extension)
- **Client**: HTML5, CSS3, Vanilla JavaScript (Cinzel & Crimson Text typography loaded via Google Fonts CDN)

---

## 4. Database Schema & Credentials

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

## 5. Deployment Guide (Transporting to a fresh Pi)

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
   *The script will automatically install Apache, MariaDB, PHP, create the database and user, import the schema, copy frontend/backend files into the web root, and set the appropriate group permissions.*

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
4. **Deploy Files**:
   Create the directory:
   ```bash
   sudo mkdir -p /var/www/html/shetrengaw/api
   ```
   Copy the files:
   ```bash
   sudo cp game_files/shetrengaw_html/shetrengaw_v7_stg.html /var/www/html/shetrengaw/index.html
   sudo cp game_files/shetrengaw_html/api/*.php /var/www/html/shetrengaw/api/
   ```
5. **Set Permissions**:
   ```bash
   sudo chown -R www-data:www-data /var/www/html/shetrengaw
   sudo chmod -R 755 /var/www/html/shetrengaw
   ```
6. **Restart Apache**:
   ```bash
   sudo systemctl restart apache2
   ```

Once deployed, access the game via browser:
- Local access: `http://localhost/shetrengaw/`
- Network access: `http://<Raspberry_Pi_IP>/shetrengaw/`
