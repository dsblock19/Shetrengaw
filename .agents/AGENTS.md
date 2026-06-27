# Shetrengaw Project Rules & Guidelines for AI Assistants

This file outlines the rules, architecture, and deployment standards for the Shetrengaw project. Future AI assistants working in this repository must adhere to the configurations and guidelines below.

---

## 1. Project Architecture

Shetrengaw is a real-time asymmetric strategy game that runs on a web stack:
- **Client**: Single-page HTML/CSS/JS game client (`game_files/shetrengaw_html/shetrengaw_v7_stg.html`).
- **Backend API**: PHP endpoints inside `/var/www/html/shetrengaw/api/` (`new_game.php`, `game_api.php`, `config.php`) that read/write to the database.
- **WordPress Integration**: A custom plugin (`wp-plugin/shetrengaw/`) that programmatically creates child pages under `shetrengaw-portal` and injects access buttons into the homepage grid (page ID 21).

---

## 2. Database Isolation Rules

- **Database Separation**: All Shetrengaw tables **MUST** reside in the dedicated `shetrengaw` MariaDB database. Under no circumstances should Shetrengaw tables be written to or merged with the `wordpress` database.
- **Credentials**:
  - Database: `shetrengaw`
  - User: `archivist`
  - Password: `18sheuni19`
  - Host: `localhost`
- **Schema**: Defined in `game_files/shetrengaw_html/api/setup.sql`.

---

## 3. Web Deployment Standards

- **Folder Structure**:
  - Apache serving directory: `/var/www/html/shetrengaw/`
  - WordPress Plugin directory: `/var/www/html/wp-content/plugins/shetrengaw/`
- **Relative API Base**: The client code **MUST** use a relative API base path:
  `const API_BASE = '/shetrengaw/api';`
  Do not hardcode IP addresses or domain names to ensure the project remains portable.
- **Asset Access**: Visual assets (such as board and piece images) are copied to `/var/www/html/shetrengaw/images/` and must be referenced via `/shetrengaw/images/...` in rules and gallery pages.

---

## 4. Automated Installation & Transportability

- **Setup Script**: The bash script `setup.sh` at the root of the repository is the single source of truth for full setup on a fresh Raspberry Pi. It installs dependencies, sets up the database, copies files to the Apache root, deploys/activates the plugin, and configures the directory permissions.
- **Setup Execution**: To install or restore the project, run:
  ```bash
  sudo ./setup.sh
  ```

---

## 5. Development Guidelines
- **Aesthetic Consistency**: Maintain the premium dark-themed, gold-bordered aesthetic using Cinzel and Crimson Text typography.
- **Shortcodes**: Modifying layout templates or stylesheets must be done under the `wp-plugin/shetrengaw/` files and then redeployed.
