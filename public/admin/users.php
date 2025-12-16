<?php
session_start();
include '../../include/koneksi.php';
$current_page = basename($_SERVER['PHP_SELF']);

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// Handle Delete User
if (isset($_GET['delete'])) {
    $id_user = intval($_GET['delete']);

    // Pastikan tidak menghapus akun sendiri
    if ($id_user != $_SESSION['user_id']) {
        $delete_query = "DELETE FROM users WHERE id_user = $id_user AND role = 'user'";
        if (mysqli_query($conn, $delete_query)) {
            $_SESSION['success_message'] = "User berhasil dihapus!";
        } else {
            $_SESSION['error_message'] = "Gagal menghapus user!";
        }
    } else {
        $_SESSION['error_message'] = "Anda tidak bisa menghapus akun sendiri!";
    }
    header("Location: users.php");
    exit;
}

// Handle Search & Filter
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$filter_role = isset($_GET['role']) ? mysqli_real_escape_string($conn, $_GET['role']) : '';

// Query untuk mengambil data users dengan pagination
$limit = 10;
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($page - 1) * $limit;

$where_conditions = [];
if (!empty($search)) {
    $where_conditions[] = "(username LIKE '%$search%' OR email LIKE '%$search%')";
}
if (!empty($filter_role)) {
    $where_conditions[] = "role = '$filter_role'";
}

$where_sql = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Total users untuk pagination
$count_query = "SELECT COUNT(*) as total FROM users $where_sql";
$count_result = mysqli_query($conn, $count_query);
$total_users = mysqli_fetch_assoc($count_result)['total'];
$total_pages = ceil($total_users / $limit);

// Query utama
$query = "SELECT id_user, username, email, role, created_at, 
          (SELECT COUNT(*) FROM bookmarks WHERE id_user = users.id_user) as total_bookmarks,
          (SELECT COUNT(*) FROM history WHERE id_user = users.id_user) as total_history
          FROM users 
          $where_sql
          ORDER BY created_at DESC 
          LIMIT $limit OFFSET $offset";
$result = mysqli_query($conn, $query);

// Stats
$stats_query = "SELECT 
    COUNT(*) as total_users,
    SUM(CASE WHEN role = 'admin' THEN 1 ELSE 0 END) as total_admins,
    SUM(CASE WHEN role = 'user' THEN 1 ELSE 0 END) as total_regular_users,
    SUM(CASE WHEN DATE(created_at) = CURDATE() THEN 1 ELSE 0 END) as new_today
    FROM users";
$stats_result = mysqli_query($conn, $stats_query);
$stats = mysqli_fetch_assoc($stats_result);
?>
<?php include 'sidebar.php'; ?>
<main class="main-content">
    <header class="admin-header">
        <div>
            <h2 class="fw-bold m-0">User Management</h2>
            <p class="text-secondary m-0">Manage all registered users and their activities.</p>
        </div>
        <div class="d-flex align-items-center gap-3">
            <a href="user-add.php" class="btn btn-primary">
                <i class="bi bi-person-plus"></i> Add New User
            </a>
        </div>
    </header>

    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-2"></i>
            <?php
            echo $_SESSION['success_message'];
            unset($_SESSION['success_message']);
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle me-2"></i>
            <?php
            echo $_SESSION['error_message'];
            unset($_SESSION['error_message']);
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Stats Cards -->
    <div class="row g-4 mb-5">
        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="stat-label">Total Users</div>
                        <div class="stat-value"><?php echo number_format($stats['total_users']); ?></div>
                        <small class="text-secondary">All registered users</small>
                    </div>
                    <div class="stat-icon-wrapper bg-blue-soft">
                        <i class="bi bi-people-fill"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="stat-label">Regular Users</div>
                        <div class="stat-value"><?php echo number_format($stats['total_regular_users']); ?></div>
                        <small class="text-secondary">User role accounts</small>
                    </div>
                    <div class="stat-icon-wrapper bg-purple-soft">
                        <i class="bi bi-person-fill"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="stat-label">Administrators</div>
                        <div class="stat-value"><?php echo number_format($stats['total_admins']); ?></div>
                        <small class="text-secondary">Admin role accounts</small>
                    </div>
                    <div class="stat-icon-wrapper bg-orange-soft">
                        <i class="bi bi-shield-fill"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="stat-label">New Today</div>
                        <div class="stat-value"><?php echo number_format($stats['new_today']); ?></div>
                        <small class="text-success"><i class="bi bi-calendar-check"></i> Registered today</small>
                    </div>
                    <div class="stat-icon-wrapper bg-pink-soft">
                        <i class="bi bi-person-plus-fill"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Search & Filter -->
    <div class="card bg-transparent border-0">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h5 class="fw-bold m-0 text-muted">All Users</h5>
        </div>

        <form method="GET" class="mb-4">
            <div class="row g-3">
                <div class="col-md-6">
                    <input type="text" name="search" class="form-control"
                        placeholder="Search by username or email..."
                        value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="col-md-3">
                    <select name="role" class="form-select">
                        <option value="">All Roles</option>
                        <option value="user" <?php echo $filter_role == 'user' ? 'selected' : ''; ?>>User</option>
                        <option value="admin" <?php echo $filter_role == 'admin' ? 'selected' : ''; ?>>Admin</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary flex-fill">
                            <i class="bi bi-search"></i> Search
                        </button>
                        <a href="users.php" class="btn btn-outline-secondary">
                            <i class="bi bi-x-lg"></i>
                        </a>
                    </div>
                </div>
            </div>
        </form>

        <!-- Users Table -->
        <div class="table-responsive" style="background-color: var(--bg-card); border-radius: 16px; border: 1px solid var(--bg-element);">
            <table class="table-custom">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>User Info</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Activity</th>
                        <th>Joined</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $no = $offset + 1;
                    while ($row = mysqli_fetch_assoc($result)):
                    ?>
                        <tr>
                            <td class="text-secondary"><?php echo str_pad($no++, 3, '0', STR_PAD_LEFT); ?></td>
                            <td>
                                <div class="d-flex align-items-center gap-3">
                                    <div class="bg-primary bg-opacity-25 rounded-circle d-flex align-items-center justify-content-center text-primary"
                                        style="width: 40px; height: 40px; font-weight: 600;">
                                        <?php echo strtoupper(substr($row['username'], 0, 2)); ?>
                                    </div>
                                    <div>
                                        <div class="fw-bold"><?php echo htmlspecialchars($row['username']); ?></div>
                                        <small class="text-secondary">ID: <?php echo $row['id_user']; ?></small>
                                    </div>
                                </div>
                            </td>
                            <td class="text-secondary"><?php echo htmlspecialchars($row['email']); ?></td>
                            <td>
                                <?php if ($row['role'] == 'admin'): ?>
                                    <span class="badge bg-danger bg-opacity-75 text-white fw-normal">
                                        <i class="bi bi-shield-fill"></i> Admin
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-secondary bg-opacity-25 text-light fw-normal">
                                        <i class="bi bi-person"></i> User
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="small text-secondary">
                                    <i class="bi bi-bookmark-fill text-warning"></i> <?php echo $row['total_bookmarks']; ?> bookmarks
                                    <span class="mx-1">•</span>
                                    <i class="bi bi-clock-history text-info"></i> <?php echo $row['total_history']; ?> reads
                                </div>
                            </td>
                            <td class="text-secondary small">
                                <?php
                                $date = date_create($row['created_at']);
                                echo date_format($date, "d M Y");
                                ?>
                            </td>
                            <td>
                                <div class="d-flex justify-content-end gap-2">
                                    <a href="user-edit.php?id=<?php echo $row['id_user']; ?>"
                                        class="btn btn-icon" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <?php if ($row['id_user'] != $_SESSION['user_id']): ?>
                                        <a href="users.php?delete=<?php echo $row['id_user']; ?>"
                                            class="btn btn-icon text-danger"
                                            title="Delete"
                                            onclick="return confirm('Are you sure you want to delete user \'<?php echo htmlspecialchars($row['username']); ?>\'? This action cannot be undone.')">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>

                    <?php if (mysqli_num_rows($result) == 0): ?>
                        <tr>
                            <td colspan="7" class="text-center py-5">
                                <i class="bi bi-inbox display-4 text-secondary d-block mb-3"></i>
                                <p class="text-secondary">No users found.</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <div class="d-flex justify-content-between align-items-center mt-4 px-2">
                <small class="text-secondary">
                    Showing <?php echo $offset + 1; ?> to <?php echo min($offset + $limit, $total_users); ?> of <?php echo $total_users; ?> results
                </small>
                <nav>
                    <ul class="pagination pagination-sm mb-0">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link bg-transparent text-secondary border-secondary"
                                    href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&role=<?php echo urlencode($filter_role); ?>">
                                    Previous
                                </a>
                            </li>
                        <?php else: ?>
                            <li class="page-item disabled">
                                <a class="page-link bg-transparent text-secondary border-secondary" href="#">Previous</a>
                            </li>
                        <?php endif; ?>

                        <?php
                        $start_page = max(1, $page - 2);
                        $end_page = min($total_pages, $page + 2);

                        for ($i = $start_page; $i <= $end_page; $i++):
                        ?>
                            <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                <a class="page-link <?php echo $i == $page ? 'border-primary' : 'bg-transparent text-secondary border-secondary'; ?>"
                                    href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&role=<?php echo urlencode($filter_role); ?>">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                        <?php endfor; ?>

                        <?php if ($page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link bg-transparent text-secondary border-secondary"
                                    href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&role=<?php echo urlencode($filter_role); ?>">
                                    Next
                                </a>
                            </li>
                        <?php else: ?>
                            <li class="page-item disabled">
                                <a class="page-link bg-transparent text-secondary border-secondary" href="#">Next</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            </div>
        <?php endif; ?>
    </div>
</main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>