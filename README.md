# HobbyJP

Dokumentasi lengkap proyek HobbyJP — platform baca Manga / Manhwa / Novel online.

## Daftar Isi

- Ringkasan
- Teknologi
- Struktur Proyek
- Setup & Menjalankan (lokal)
- Skema Database (ringkasan)
- Fitur Utama
- Endpoints AJAX
- Admin: CRUD (Categories, Contents, Episodes)
- Best Practices & Keamanan
- Pengujian Manual
- Pengembangan Lanjutan

---

## Ringkasan

HobbyJP adalah aplikasi web berbasis PHP + MySQL untuk membaca konten (manga/novel), dilengkapi fitur user (bookmark, rating, history) dan area admin untuk mengelola kategori, konten, dan episode.

## Teknologi

- PHP (mysqli, procedural)
- MySQL / MariaDB
- Bootstrap 5 (UI)
- JavaScript (vanilla) untuk beberapa interaksi

## Struktur Proyek (ringkasan)

- `include/` — koneksi DB, auth, helper
- `page/` — partial header & footer
- `public/` — halaman front-end, ajax endpoints, admin area, uploads, asset
  - `public/ajax/` — endpoints JSON (rating, bookmark)
  - `public/admin/` — halaman admin (contents, categories, episodes, users)
  - `public/uploads/` — folder untuk cover image (pastikan writable oleh web-server)
- `asset/` — CSS, JS, images

Contoh file penting:

- `include/koneksi.php` — konfigurasi database
- `public/detail.php` — halaman detail konten (rating + bookmark)
- `public/bookmarks.php` — daftar bookmark user
- `public/admin/contents.php` — manajemen konten (admin)

## Setup & Menjalankan (lokal)

1. Clone repo:
   ```bash
   git clone <repo-url>
   cd HobbyJP
   ```
2. Siapkan environment (direkomendasikan ddev atau LAMP):
   - ddev: `ddev start` lalu `ddev import-db --src=path/to/dump.sql` atau gunakan phpMyAdmin
3. Pastikan DB credentials di `include/koneksi.php` sesuai environment
4. Buat folder uploads dan berikan permission:
   ```bash
   mkdir -p public/uploads
   chmod 775 public/uploads
   ```
5. Buka app di browser: `http://<your-site>/public/`

Sample credential (seed):

- **admin** / password
- **user** / password

## Skema Database (ringkasan)

- `users` (id_user, username, email, password, role, created_at)
- `categories` (id_category, category_name)
- `contents` (id_content, id_category, title, cover, synopsis, status, created_at)
- `episodes` (id_episode, id_content, episode_number, title, content, created_at)
- `bookmarks`, `history`, `ratings`, `content_stats`

`content_stats` menyimpan statistik: total_episode, total_bookmark, average_rating, view_count.

## Fitur Utama

- User: register, login, profil, bookmark, rate (1-5), lihat history bacaan
- Content: daftar, detail, view count
- Admin: manage categories, contents (upload cover), episodes (add/edit/delete), users

## Endpoints AJAX (JSON)

- `public/ajax/submit_rating.php` — submit/update rating
- `public/ajax/remove_rating.php` — remove rating
- `public/ajax/submit_bookmark.php` — add bookmark
- `public/ajax/remove_bookmark.php` — remove bookmark

All endpoints memeriksa session (`$_SESSION['user_id']`) dan mengembalikan JSON: `{success, message, ...}`.

## Admin: Alur CRUD

- Categories: `public/admin/categories.php` (add modal, edit modal, delete)
- Contents: `public/admin/contents.php`, `content-add.php`, `content-edit.php`, `content-detail.php` (lihat info + link ke episodes)
- Episodes: `public/admin/episodes.php`, `episodes-add.php`, `episodes-edit.php` — setiap perubahan episode mengupdate `content_stats.total_episode`.

## Best Practices & Keamanan

- Gunakan prepared statements untuk semua query (sudah dipakai di proyek ini)
- Validasi file upload (tipe, ukuran) dan simpan dengan nama acak
- Sanitasi output dengan `htmlspecialchars()` untuk mencegah XSS
- Tambahkan CSRF token jika ingin memperkuat form POST

## Pengujian Manual Singkat

1. Login sebagai admin — akses `/public/admin/` untuk CRUD
2. Tambah konten (dengan cover), tambah episode, lihat jumlah episode ter-update
3. Login sebagai user — coba bookmark & beri rating, cek perubahan `content_stats`

## Pengembangan Lanjutan (opsional)

- Tambah CSRF protection
- Image processing (resize/thumbnail) saat upload
- Pagination & search pada listing admin
- Unit/integration tests (PHPUnit)

---

Jika Anda ingin, saya bisa:

- Menambahkan file README lengkap ke repo (sudah saya buat)
- Membuat checklist issue untuk fitur lanjutan
- Menambahkan CI atau skrip untuk backup/restore DB

Selanjutnya: beri tahu bila mau saya commit README.md (sudah ditambahkan), atau saya buat checklist issue di repo.

---

Dokumentasi ini disusun oleh GitHub Copilot (Raptor mini (Preview)).

# HobbyJP

HobbyJP is a web application for browsing and discovering Japanese hobby-related content, such as anime or manga. It allows users to find popular, recent, and featured content, view details, and more. The project includes an administrative panel for content management.

## Features

- **Content Discovery:** Browse featured, popular, and recently updated content.
- **Categorization:** Content is organized into categories for easy navigation.
- **Detailed Views:** Each content item has a dedicated page with a synopsis, rating, and other details.
- **User Interaction:** (Planned/Inferred) Bookmark and rate content.
- **Admin Panel:** A dashboard for administrators to add, edit, and manage content, categories, and episodes.

## Technology Stack

- **Backend:** PHP
- **Database:** MySQL
- **Frontend:** HTML, CSS, JavaScript
- **Development Environment:** DDEV

## Local Development Setup

This project is configured to run with [DDEV](https://ddev.readthedocs.io/).

1.  **Prerequisites:**

    - [Docker](https://www.docker.com/products/docker-desktop/)
    - [DDEV](https://ddev.readthedocs.io/en/latest/users/install/ddev-installation/)

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
    _If you have a database dump file (`.sql` or `.sql.gz`), you can import it using:_

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
