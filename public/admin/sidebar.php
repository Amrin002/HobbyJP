<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - HobbyJP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../asset/css/style.css">

    <style>
        /* --- Admin Specific Overrides --- */
        body {
            overflow-x: hidden;
        }

        /* Layout Grid */
        .admin-container {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar Styling */
        .sidebar {
            width: 260px;
            background-color: var(--bg-body);
            border-right: 1px solid var(--bg-element);
            padding: 20px;
            display: flex;
            flex-direction: column;
            position: fixed;
            height: 100vh;
            z-index: 1000;
            /* Ensure text is highly readable on dark background */
            color: var(--text-primary);
        }

        .sidebar-brand {
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--text-primary);
            margin-bottom: 40px;
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
        }

        .sidebar-menu {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .menu-header {
            font-size: 0.75rem;
            text-transform: uppercase;
            color: var(--text-secondary);
            margin-bottom: 10px;
            letter-spacing: 1px;
            margin-top: 20px;
            /* Slightly increase brightness for better legibility */
            opacity: 0.95;
        }

        .nav-item {
            margin-bottom: 5px;
        }

        .nav-link-admin {
            display: flex;
            align-items: center;
            padding: 12px 15px;
            color: var(--text-secondary);
            text-decoration: none;
            border-radius: 10px;
            transition: all 0.2s;
            font-weight: 500;
        }

        .nav-link-admin:hover,
        .nav-link-admin.active {
            background-color: var(--accent-primary);
            color: white;
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
        }

        .nav-link-admin i {
            margin-right: 12px;
            font-size: 1.1rem;
        }

        /* Use primary text color so links are more visible */
        .nav-link-admin {
            color: var(--text-primary);
            font-weight: 600;
        }

        /* Hover: subtle tint to indicate focus without reducing contrast */
        .nav-link-admin:hover {
            background-color: rgba(59, 130, 246, 0.08);
            color: var(--text-primary);
            transform: translateY(-1px);
        }

        /* Active: clear accent and white text */
        .nav-link-admin.active {
            background-color: var(--accent-primary);
            color: #ffffff;
            box-shadow: 0 6px 16px rgba(59, 130, 246, 0.2);
        }

        .nav-link-admin i {
            /* Icons inherit color so they match link text */
            color: inherit;
            opacity: 0.96;
        }

        /* Main Content Area */
        .main-content {
            flex: 1;
            margin-left: 260px;
            /* Lebar sidebar */
            padding: 30px;
        }

        /* Improve contrast for main admin content text that blends into dark BG */
        .main-content {
            /* ensure default text in the main area is bright */
            color: var(--text-primary);
        }

        /* Make secondary/helper text more readable (slightly brighter) */
        .main-content .text-secondary,
        .main-content small,
        .main-content .text-muted {
            color: rgba(248, 250, 252, 0.72) !important;
            /* ~72% white */
        }

        /* Table headers and cells: increase legibility */
        .main-content .table-custom th {
            color: rgba(248, 250, 252, 0.80) !important;
        }

        .main-content .table-custom td {
            color: var(--text-primary) !important;
        }

        /* Card helper text */
        .main-content .card .text-secondary,
        .main-content .stat-label,
        .main-content .stat-value {
            color: var(--text-primary) !important;
        }

        /* Badges: ensure category/status badges are readable */
        .main-content .badge.bg-secondary {
            background-color: rgba(255, 255, 255, 0.04);
            color: var(--text-primary) !important;
        }

        /* Pagination links */
        .main-content .pagination .page-link {
            color: var(--text-secondary);
        }

        /* Small/tertiary text still less prominent but readable */
        .main-content .small.text-secondary {
            color: rgba(248, 250, 252, 0.64) !important;
        }

        /* Header Admin */
        .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 40px;
        }

        .search-bar {
            background-color: var(--bg-card);
            border: 1px solid var(--bg-element);
            border-radius: 8px;
            padding: 10px 20px;
            width: 400px;
            color: var(--text-primary);
        }

        .search-bar:focus {
            outline: 1px solid var(--accent-primary);
        }

        /* Stats Cards */
        .stat-card {
            background-color: var(--bg-card);
            border-radius: 16px;
            padding: 25px;
            border: 1px solid var(--bg-element);
            height: 100%;
        }

        .stat-icon-wrapper {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 15px;
        }

        .stat-value {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 5px;
            color: var(--text-primary);
        }

        .stat-label {
            color: var(--text-secondary);
            font-size: 0.9rem;
        }

        /* Custom Colors for Stats */
        .bg-blue-soft {
            background-color: rgba(59, 130, 246, 0.1);
            color: #3b82f6;
        }

        .bg-purple-soft {
            background-color: rgba(139, 92, 246, 0.1);
            color: #8b5cf6;
        }

        .bg-orange-soft {
            background-color: rgba(249, 115, 22, 0.1);
            color: #f97316;
        }

        .bg-pink-soft {
            background-color: rgba(236, 72, 153, 0.1);
            color: #ec4899;
        }

        /* Table Styling */
        .table-custom {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }

        .table-custom th {
            color: var(--text-secondary);
            font-weight: 600;
            font-size: 0.85rem;
            text-transform: uppercase;
            padding: 15px 20px;
            border-bottom: 1px solid var(--bg-element);
            text-align: left;
        }

        .table-custom td {
            padding: 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            vertical-align: middle;
            color: var(--text-primary);
        }

        .content-thumb-sm {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            object-fit: cover;
            background-color: var(--bg-element);
        }

        .status-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .status-ongoing {
            background-color: rgba(59, 130, 246, 0.15);
            color: #60a5fa;
        }

        .status-completed {
            background-color: rgba(34, 197, 94, 0.15);
            color: #4ade80;
        }

        .btn-icon {
            width: 32px;
            height: 32px;
            border-radius: 6px;
            border: 1px solid var(--bg-element);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-secondary);
            background: transparent;
            transition: all 0.2s;
            opacity: 0.95;
        }

        .btn-icon:hover {
            background-color: var(--bg-element);
            color: white;
        }

        /* Ensure links inside sidebar always inherit high-contrast color */
        .sidebar a {
            color: inherit;
        }
    </style>
</head>

<body>

    <div class="admin-container">
        <aside class="sidebar">
            <a href="index.php" class="sidebar-brand">
                <i class="bi bi-book-half text-primary"></i>
                HobbyJP
            </a>

            <div class="d-flex align-items-center mb-4 p-3 rounded bg-opacity-10 bg-primary" style="background: rgba(59,130,246,0.08);">
                <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center text-white me-3" style="width: 35px; height: 35px;">
                    <i class="bi bi-person-fill"></i>
                </div>
                <div>
                    <h6 class="m-0 text-white" style="font-size: 0.9rem;">Admin User</h6>
                    <small class="text-secondary" style="font-size: 0.75rem;">Administrator</small>
                </div>
            </div>

            <ul class="sidebar-menu">
                <li>
                    <a href="index.php" class="nav-link-admin <?php echo ($current_page == 'index.php') ? 'active' : ''; ?>">
                        <i class="bi bi-grid-1x2-fill"></i> Dashboard
                    </a>
                </li>

                <li class="menu-header">Content</li>

                <li class="nav-item">
                    <a href="contents.php" class="nav-link-admin <?php echo ($current_page == 'contents.php' || $current_page == 'content-add.php' || $current_page == 'content-edit.php') ? 'active' : ''; ?>">
                        <i class="bi bi-collection-play"></i> All Content
                    </a>
                </li>



                <li class="nav-item">
                    <a href="categories.php" class="nav-link-admin <?php echo ($current_page == 'categories.php') ? 'active' : ''; ?>">
                        <i class="bi bi-tags"></i> Categories
                    </a>
                </li>

                <li class="menu-header">System</li>

                <li class="nav-item">
                    <a href="users.php" class="nav-link-admin <?php echo ($current_page == 'users.php') ? 'active' : ''; ?>">
                        <i class="bi bi-people"></i> Users
                    </a>
                </li>

                <li class="nav-item">
                    <a href="#" class="nav-link-admin">
                        <i class="bi bi-gear"></i> Settings
                    </a>
                </li>

                <li class="nav-item mt-4">
                    <a href="../logout.php" class="nav-link-admin text-danger">
                        <i class="bi bi-box-arrow-left"></i> Logout
                    </a>
                </li>
            </ul>
        </aside>