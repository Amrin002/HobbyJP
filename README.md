# HobbyJP

HobbyJP is a web application for browsing and discovering Japanese hobby-related content, such as anime or manga. It allows users to find popular, recent, and featured content, view details, and more. The project includes an administrative panel for content management.

## Features

*   **Content Discovery:** Browse featured, popular, and recently updated content.
*   **Categorization:** Content is organized into categories for easy navigation.
*   **Detailed Views:** Each content item has a dedicated page with a synopsis, rating, and other details.
*   **User Interaction:** (Planned/Inferred) Bookmark and rate content.
*   **Admin Panel:** A dashboard for administrators to add, edit, and manage content, categories, and episodes.

## Technology Stack

*   **Backend:** PHP
*   **Database:** MySQL
*   **Frontend:** HTML, CSS, JavaScript
*   **Development Environment:** DDEV

## Local Development Setup

This project is configured to run with [DDEV](https://ddev.readthedocs.io/).

1.  **Prerequisites:**
    *   [Docker](https://www.docker.com/products/docker-desktop/)
    *   [DDEV](https://ddev.readthedocs.io/en/latest/users/install/ddev-installation/)

2.  **Clone the repository:**
    ```bash
    git clone https://github.com/Amrin002/HobbyJP.git
    cd HobbyJP
    ```

3.  **Start the DDEV project:**
    This command will read the configuration in the `.ddev` directory and spin up the necessary containers (web server and database).
    ```bash
    ddev start
    ```

4.  **Database Setup:**
    The database connection is pre-configured in `include/koneksi.php` to work with DDEV's database service. To get the application running, you'll need to import the database structure. 
    *If you have a database dump file (`.sql` or `.sql.gz`), you can import it using:*
    ```bash
    ddev import-db --file=/path/to/your/database.sql.gz
    ```

5.  **Access the application:**
    Once DDEV is running, you can open the application in your browser.
    ```bash
    ddev launch
    ```

## Project Structure

```
hobbyjp/
│
├── .ddev/            # DDEV local development environment configuration
├── admin/            # Admin panel for content management
│   ├── index.php
│   ├── tambah_content.php
│   └── ...
├── include/
│   └── koneksi.php   # Database connection
├── page/
│   ├── header.php    # Global page header
│   └── footer.php    # Global page footer
├── public/           # Publicly accessible web root
│   ├── index.php     # Homepage
│   ├── detail.php
│   ├── category.php
│   └── asset/        # CSS, JavaScript, and images
└── sturuktur_project.md # Original project structure notes
```
