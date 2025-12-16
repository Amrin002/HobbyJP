<?php
session_start();
include '../../include/koneksi.php';
$current_page = basename($_SERVER['PHP_SELF']);

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    // Lempar ke login khusus admin
    header("Location: login.php");
    exit;
}
// Total Views (Sum dari content_stats)
$q_views = mysqli_query($conn, "SELECT SUM(view_count) as total FROM content_stats");
$total_views = mysqli_fetch_assoc($q_views)['total'] ?? 0;

// Active Users (Total user di tabel users)
$q_users = mysqli_query($conn, "SELECT COUNT(*) as total FROM users WHERE role = 'user'");
$total_users = mysqli_fetch_assoc($q_users)['total'] ?? 0;

// Total Titles (Total konten)
$q_contents = mysqli_query($conn, "SELECT COUNT(*) as total FROM contents");
$total_contents = mysqli_fetch_assoc($q_contents)['total'] ?? 0;

// Top Genre (Kategori dengan total view terbanyak)
$q_top_genre = mysqli_query($conn, "
    SELECT cat.category_name, SUM(cs.view_count) as total_views 
    FROM categories cat
    JOIN contents c ON cat.id_category = c.id_category
    JOIN content_stats cs ON c.id_content = cs.id_content
    GROUP BY cat.id_category
    ORDER BY total_views DESC
    LIMIT 1
");
$top_genre_data = mysqli_fetch_assoc($q_top_genre);
$top_genre = $top_genre_data['category_name'] ?? '-';


// --- 3. Logic: Content Management Table ---
// Mengambil 5 konten terbaru untuk tabel
$query_table = "SELECT c.*, cat.category_name, cs.view_count, cs.average_rating 
                FROM contents c 
                LEFT JOIN categories cat ON c.id_category = cat.id_category 
                LEFT JOIN content_stats cs ON c.id_content = cs.id_content 
                ORDER BY c.created_at DESC LIMIT 5";
$result_table = mysqli_query($conn, $query_table);

?>
<?php include 'sidebar.php'; ?>
<main class="main-content">

    <header class="admin-header">
        <div>
            <h2 class="fw-bold m-0">Dashboard Overview</h2>
            <p class="text-secondary m-0">Welcome back, here's what's happening today.</p>
        </div>
        <div class="d-flex align-items-center gap-3">
            <input type="text" class="search-bar" placeholder="Search titles, authors, or genres...">
            <button class="btn btn-icon position-relative">
                <i class="bi bi-bell"></i>
                <span class="position-absolute top-0 start-100 translate-middle p-1 bg-danger border border-dark rounded-circle"></span>
            </button>
        </div>
    </header>

    <div class="row g-4 mb-5">
        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="stat-label">Total Views</div>
                        <div class="stat-value"><?php echo number_format($total_views); ?></div>
                        <small class="text-success"><i class="bi bi-graph-up-arrow"></i> +12.5% this week</small>
                    </div>
                    <div class="stat-icon-wrapper bg-blue-soft">
                        <i class="bi bi-eye-fill"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="stat-label">Active Users</div>
                        <div class="stat-value"><?php echo number_format($total_users); ?></div>
                        <small class="text-success"><i class="bi bi-graph-up-arrow"></i> +5.2% from yesterday</small>
                    </div>
                    <div class="stat-icon-wrapper bg-purple-soft">
                        <i class="bi bi-people-fill"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="stat-label">Total Titles</div>
                        <div class="stat-value"><?php echo number_format($total_contents); ?></div>
                        <small class="text-secondary">All categories included</small>
                    </div>
                    <div class="stat-icon-wrapper bg-orange-soft">
                        <i class="bi bi-book-fill"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="stat-label">Top Genre</div>
                        <div class="stat-value fs-3"><?php echo htmlspecialchars($top_genre); ?></div>
                        <small class="text-secondary">Most viewed category</small>
                    </div>
                    <div class="stat-icon-wrapper bg-pink-soft">
                        <i class="bi bi-heart-fill"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card bg-transparent border-0">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h5 class="fw-bold m-0 text-muted">Content Management</h5>
            <div class="d-flex gap-2">
                <button class="btn btn-outline-light btn-sm">
                    <i class="bi bi-filter"></i> Filter
                </button>
                <a href="content-add.php" class="btn btn-primary btn-sm">
                    <i class="bi bi-plus-lg"></i> Add Title
                </a>
            </div>
        </div>

        <div class="table-responsive" style="background-color: var(--bg-card); border-radius: 16px; border: 1px solid var(--bg-element);">
            <table class="table-custom">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Title Info</th>
                        <th>Category</th>
                        <th>Status</th>
                        <th>Stats</th>
                        <th>Updated</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $no = 1;
                    while ($row = mysqli_fetch_assoc($result_table)):
                    ?>
                        <tr>
                            <td class="text-secondary"><?php echo str_pad($no++, 2, '0', STR_PAD_LEFT); ?></td>
                            <td>
                                <div class="d-flex align-items-center gap-3">
                                    <?php if (!empty($row['cover'])): ?>
                                        <img src="../uploads/<?php echo $row['cover']; ?>" class="content-thumb-sm" alt="cover">
                                    <?php else: ?>
                                        <div class="content-thumb-sm d-flex align-items-center justify-content-center text-secondary">
                                            <i class="bi bi-image"></i>
                                        </div>
                                    <?php endif; ?>
                                    <div>
                                        <div class="fw-bold"><?php echo htmlspecialchars($row['title']); ?></div>
                                        <small class="text-secondary" style="font-size: 0.75rem;">
                                            <i class="bi bi-star-fill text-warning"></i> <?php echo number_format($row['average_rating'], 1); ?>
                                        </small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-secondary bg-opacity-25 text-light fw-normal">
                                    <?php echo htmlspecialchars($row['category_name']); ?>
                                </span>
                            </td>
                            <td>
                                <span class="status-badge <?php echo $row['status'] == 'ONGOING' ? 'status-ongoing' : 'status-completed'; ?>">
                                    <?php echo $row['status']; ?>
                                </span>
                            </td>
                            <td>
                                <div class="text-secondary small">
                                    <i class="bi bi-eye"></i> <?php echo number_format($row['view_count']); ?>
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
                                    <a href="content-edit.php?id=<?php echo $row['id_content']; ?>" class="btn btn-icon" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <a href="#" class="btn btn-icon text-danger" title="Delete" onclick="return confirm('Hapus konten ini?')">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>

                    <?php if (mysqli_num_rows($result_table) == 0): ?>
                        <tr>
                            <td colspan="7" class="text-center py-4 text-secondary">Belum ada konten yang ditambahkan.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="d-flex justify-content-between align-items-center mt-3 px-2">
            <small class="text-secondary">Showing latest 5 results</small>
            <nav>
                <ul class="pagination pagination-sm mb-0">
                    <li class="page-item disabled"><a class="page-link bg-transparent text-secondary border-secondary" href="#">Previous</a></li>
                    <li class="page-item active"><a class="page-link border-primary" href="#">1</a></li>
                    <li class="page-item"><a class="page-link bg-transparent text-secondary border-secondary" href="#">Next</a></li>
                </ul>
            </nav>
        </div>
    </div>


</main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>