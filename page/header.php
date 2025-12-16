<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HobbyJP - Baca Manga, Manhwa & Novel Online</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="asset/css/style.css">
</head>

<body>
    <nav class="navbar navbar-expand-lg sticky-top">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="bi bi-book-half"></i> HobbyJP
            </a>
            <button class="navbar-toggler navbar-dark" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item"><a class="nav-link" href="index.php">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="category.php?id=1">Manga</a></li>
                    <li class="nav-item"><a class="nav-link" href="category.php?id=2">Manhwa</a></li>
                    <li class="nav-item"><a class="nav-link" href="category.php?id=4">Novel</a></li>

                    <!-- Search Form -->
                    <li class="nav-item ms-lg-3">
                        <form action="index.php" method="GET" class="d-flex">
                            <div class="input-group input-group-sm">
                                <input type="text"
                                    class="form-control"
                                    name="search"
                                    placeholder="Cari..."
                                    value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>"
                                    style="background-color: var(--bg-element); border: 1px solid var(--bg-element); color: var(--text-primary); min-width: 200px;">
                                <button class="btn btn-outline-primary" type="submit">
                                    <i class="bi bi-search"></i>
                                </button>
                            </div>
                        </form>
                    </li>

                    <?php if (isset($_SESSION['user_id'])): ?>
                        <!-- User sudah login -->
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                                <i class="bi bi-person-circle me-2" style="font-size: 1.5rem;"></i>
                                <span class="d-none d-lg-inline"><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end" style="background-color: var(--bg-card); border: 1px solid var(--bg-element);">
                                <li>
                                    <a class="dropdown-item" href="profile.php" style="color: var(--text-secondary);">
                                        <i class="bi bi-person me-2"></i>Profile
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="bookmarks.php" style="color: var(--text-secondary);">
                                        <i class="bi bi-bookmark me-2"></i>Bookmarks
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="history.php" style="color: var(--text-secondary);">
                                        <i class="bi bi-clock-history me-2"></i>History
                                    </a>
                                </li>
                                <li>
                                    <hr class="dropdown-divider" style="border-color: var(--bg-element);">
                                </li>
                                <li>
                                    <a class="dropdown-item text-danger" href="logout.php">
                                        <i class="bi bi-box-arrow-right me-2"></i>Logout
                                    </a>
                                </li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <!-- User belum login -->
                        <li class="nav-item ms-lg-3">
                            <a class="btn btn-outline-primary btn-sm w-100 mb-2 mb-lg-0" href="login.php" style="min-width: 90px;">
                                <i class="bi bi-box-arrow-in-right me-1"></i>Login
                            </a>
                        </li>
                        <li class="nav-item ms-lg-2">
                            <a class="btn btn-primary btn-sm text-white w-100" href="register.php" style="min-width: 90px;">
                                <i class="bi bi-person-plus me-1"></i>Sign Up
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>