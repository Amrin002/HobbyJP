<?php
include '../include/koneksi.php';

// Get category ID from URL
$id_category = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id_category == 0) {
    header('Location: index.php');
    exit;
}

// Get category details
$query_category = "SELECT * FROM categories WHERE id_category = ?";
$stmt_cat = mysqli_prepare($conn, $query_category);
mysqli_stmt_bind_param($stmt_cat, "i", $id_category);
mysqli_stmt_execute($stmt_cat);
$result_category = mysqli_stmt_get_result($stmt_cat);
$category = mysqli_fetch_assoc($result_category);

if (!$category) {
    header('Location: index.php');
    exit;
}

// Pagination setup
$limit = 12;
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($page - 1) * $limit;

// Filter and sorting
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$sort_by = isset($_GET['sort']) ? $_GET['sort'] : 'latest';

// Build query based on filters
$where_clause = "c.id_category = ?";
$params = [$id_category];
$param_types = "i";

if ($status_filter && in_array($status_filter, ['ONGOING', 'COMPLETED'])) {
    $where_clause .= " AND c.status = ?";
    $params[] = $status_filter;
    $param_types .= "s";
}

// Sort options
switch ($sort_by) {
    case 'rating':
        $order_by = "cs.average_rating DESC, c.created_at DESC";
        break;
    case 'popular':
        $order_by = "cs.view_count DESC, c.created_at DESC";
        break;
    case 'title':
        $order_by = "c.title ASC";
        break;
    default: // latest
        $order_by = "c.created_at DESC";
}

// Get total count for pagination
$count_query = "SELECT COUNT(*) as total FROM contents c WHERE $where_clause";
$stmt_count = mysqli_prepare($conn, $count_query);
mysqli_stmt_bind_param($stmt_count, $param_types, ...$params);
mysqli_stmt_execute($stmt_count);
$result_count = mysqli_stmt_get_result($stmt_count);
$total_row = mysqli_fetch_assoc($result_count);
$total_contents = $total_row['total'];
$total_pages = ceil($total_contents / $limit);

// Get contents
$query_contents = "SELECT c.*, cs.average_rating, cs.view_count, cs.total_bookmark, cs.total_episode
                   FROM contents c 
                   LEFT JOIN content_stats cs ON c.id_content = cs.id_content 
                   WHERE $where_clause
                   ORDER BY $order_by
                   LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;
$param_types .= "ii";

$stmt = mysqli_prepare($conn, $query_contents);
mysqli_stmt_bind_param($stmt, $param_types, ...$params);
mysqli_stmt_execute($stmt);
$result_contents = mysqli_stmt_get_result($stmt);

// Get all categories for menu
$query_all_categories = "SELECT * FROM categories ORDER BY category_name ASC";
$result_all_categories = mysqli_query($conn, $query_all_categories);
?>
<?php include '../page/header.php'; ?>

<div class="container mt-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php" class="text-secondary text-decoration-none">Home</a></li>
            <li class="breadcrumb-item active text-primary" aria-current="page"><?php echo htmlspecialchars($category['category_name']); ?></li>
        </ol>
    </nav>

    <!-- Category Header -->
    <div class="hero-section mb-4">
        <h1 class="hero-title mb-2"><?php echo htmlspecialchars($category['category_name']); ?></h1>
        <p class="text-secondary">
            Menampilkan <?php echo number_format($total_contents); ?> konten
        </p>
    </div>

    <!-- Filter & Sort -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
        <div class="d-flex gap-2">
            <a href="category.php?id=<?php echo $id_category; ?>&sort=<?php echo $sort_by; ?>"
                class="cat-btn <?php echo !$status_filter ? 'active' : ''; ?>"
                style="<?php echo !$status_filter ? 'background-color: var(--accent-primary); color: white;' : ''; ?>">
                All
            </a>
            <a href="category.php?id=<?php echo $id_category; ?>&status=ONGOING&sort=<?php echo $sort_by; ?>"
                class="cat-btn <?php echo $status_filter == 'ONGOING' ? 'active' : ''; ?>"
                style="<?php echo $status_filter == 'ONGOING' ? 'background-color: var(--accent-primary); color: white;' : ''; ?>">
                Ongoing
            </a>
            <a href="category.php?id=<?php echo $id_category; ?>&status=COMPLETED&sort=<?php echo $sort_by; ?>"
                class="cat-btn <?php echo $status_filter == 'COMPLETED' ? 'active' : ''; ?>"
                style="<?php echo $status_filter == 'COMPLETED' ? 'background-color: var(--accent-primary); color: white;' : ''; ?>">
                Completed
            </a>
        </div>

        <div class="dropdown">
            <button class="cat-btn dropdown-toggle" type="button" data-bs-toggle="dropdown">
                <i class="bi bi-sort-down"></i> Sort by
            </button>
            <ul class="dropdown-menu dropdown-menu-end" style="background-color: var(--bg-card); border: 1px solid var(--bg-element);">
                <li>
                    <a class="dropdown-item <?php echo $sort_by == 'latest' ? 'active' : ''; ?>"
                        href="category.php?id=<?php echo $id_category; ?><?php echo $status_filter ? '&status=' . $status_filter : ''; ?>&sort=latest"
                        style="color: var(--text-secondary);">
                        Latest
                    </a>
                </li>
                <li>
                    <a class="dropdown-item <?php echo $sort_by == 'rating' ? 'active' : ''; ?>"
                        href="category.php?id=<?php echo $id_category; ?><?php echo $status_filter ? '&status=' . $status_filter : ''; ?>&sort=rating"
                        style="color: var(--text-secondary);">
                        Highest Rating
                    </a>
                </li>
                <li>
                    <a class="dropdown-item <?php echo $sort_by == 'popular' ? 'active' : ''; ?>"
                        href="category.php?id=<?php echo $id_category; ?><?php echo $status_filter ? '&status=' . $status_filter : ''; ?>&sort=popular"
                        style="color: var(--text-secondary);">
                        Most Popular
                    </a>
                </li>
                <li>
                    <a class="dropdown-item <?php echo $sort_by == 'title' ? 'active' : ''; ?>"
                        href="category.php?id=<?php echo $id_category; ?><?php echo $status_filter ? '&status=' . $status_filter : ''; ?>&sort=title"
                        style="color: var(--text-secondary);">
                        Title (A-Z)
                    </a>
                </li>
            </ul>
        </div>
    </div>

    <!-- Contents Grid -->
    <?php if (mysqli_num_rows($result_contents) > 0): ?>
        <div class="row g-4">
            <?php while ($content = mysqli_fetch_assoc($result_contents)): ?>
                <div class="col-lg-2 col-md-3 col-sm-4 col-6">
                    <a href="detail.php?id=<?php echo $content['id_content']; ?>" class="text-decoration-none">
                        <div class="content-card">
                            <div class="content-img" style="height: 280px;">
                                <i class="bi bi-image"></i>
                                <?php if ($content['status']): ?>
                                    <span class="badge position-absolute top-0 start-0 m-2 <?php echo $content['status'] == 'ONGOING' ? 'bg-primary' : 'bg-success'; ?>">
                                        <?php echo htmlspecialchars($content['status']); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <div class="content-body">
                                <h5 class="content-title"><?php echo htmlspecialchars($content['title']); ?></h5>
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="rating">
                                        <i class="bi bi-star-fill"></i>
                                        <span><?php echo number_format($content['average_rating'], 1); ?></span>
                                    </div>
                                    <span class="content-category">
                                        <i class="bi bi-eye"></i> <?php echo number_format($content['view_count']); ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
            <?php endwhile; ?>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <nav class="mt-5">
                <ul class="pagination justify-content-center">
                    <?php if ($page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="category.php?id=<?php echo $id_category; ?><?php echo $status_filter ? '&status=' . $status_filter : ''; ?>&sort=<?php echo $sort_by; ?>&page=<?php echo ($page - 1); ?>"
                                style="background-color: var(--bg-card); color: var(--text-secondary); border-color: var(--bg-element);">
                                Previous
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php
                    $start = max(1, $page - 2);
                    $end = min($total_pages, $page + 2);

                    for ($i = $start; $i <= $end; $i++):
                    ?>
                        <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                            <a class="page-link" href="category.php?id=<?php echo $id_category; ?><?php echo $status_filter ? '&status=' . $status_filter : ''; ?>&sort=<?php echo $sort_by; ?>&page=<?php echo $i; ?>"
                                style="<?php echo $i == $page ? 'background-color: var(--accent-primary); border-color: var(--accent-primary);' : 'background-color: var(--bg-card); color: var(--text-secondary); border-color: var(--bg-element);'; ?>">
                                <?php echo $i; ?>
                            </a>
                        </li>
                    <?php endfor; ?>

                    <?php if ($page < $total_pages): ?>
                        <li class="page-item">
                            <a class="page-link" href="category.php?id=<?php echo $id_category; ?><?php echo $status_filter ? '&status=' . $status_filter : ''; ?>&sort=<?php echo $sort_by; ?>&page=<?php echo ($page + 1); ?>"
                                style="background-color: var(--bg-card); color: var(--text-secondary); border-color: var(--bg-element);">
                                Next
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
        <?php endif; ?>

    <?php else: ?>
        <div class="text-center py-5">
            <i class="bi bi-inbox" style="font-size: 4rem; color: var(--text-secondary);"></i>
            <h4 class="mt-3 text-secondary">Tidak ada konten ditemukan</h4>
            <p class="text-secondary">Coba filter atau kategori lain</p>
        </div>
    <?php endif; ?>
</div>

<?php include '../page/footer.php'; ?>