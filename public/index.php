<?php
require_once __DIR__ . '/../include/koneksi.php';

// Check if search query exists
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$is_searching = !empty($search);

if ($is_searching) {
    // Search Mode - tampilkan hasil pencarian
    $query_search = "SELECT c.*, cat.category_name, cs.average_rating, cs.total_bookmark, cs.view_count,
                     (SELECT COUNT(*) FROM episodes e WHERE e.id_content = c.id_content) as total_episodes
                     FROM contents c 
                     LEFT JOIN categories cat ON c.id_category = cat.id_category 
                     LEFT JOIN content_stats cs ON c.id_content = cs.id_content 
                     WHERE (c.title LIKE ? OR c.synopsis LIKE ? OR cat.category_name LIKE ?)
                     ORDER BY cs.average_rating DESC, cs.view_count DESC";

    $stmt = mysqli_prepare($conn, $query_search);
    $search_param = "%{$search}%";
    mysqli_stmt_bind_param($stmt, "sss", $search_param, $search_param, $search_param);
    mysqli_stmt_execute($stmt);
    $result_search = mysqli_stmt_get_result($stmt);

    $search_results = [];
    while ($row = mysqli_fetch_assoc($result_search)) {
        $search_results[] = $row;
    }
    $total_results = count($search_results);
} else {
    // Normal Mode - tampilkan konten homepage seperti biasa
    // Get featured content
    $query_featured = "SELECT c.*, cat.category_name, cs.average_rating, cs.view_count 
                       FROM contents c 
                       LEFT JOIN categories cat ON c.id_category = cat.id_category 
                       LEFT JOIN content_stats cs ON c.id_content = cs.id_content 
                       ORDER BY c.created_at DESC 
                       LIMIT 1";
    $result_featured = mysqli_query($conn, $query_featured);
    $featured = mysqli_fetch_assoc($result_featured);

    // Get popular contents
    $query_popular = "SELECT c.*, cat.category_name, cs.average_rating, cs.total_bookmark 
                      FROM contents c 
                      LEFT JOIN categories cat ON c.id_category = cat.id_category 
                      LEFT JOIN content_stats cs ON c.id_content = cs.id_content 
                      ORDER BY cs.average_rating DESC, cs.view_count DESC 
                      LIMIT 5";
    $result_popular = mysqli_query($conn, $query_popular);

    // Get latest contents
    $query_latest = "SELECT c.*, cat.category_name, cs.average_rating, 
                     (SELECT COUNT(*) FROM episodes e WHERE e.id_content = c.id_content) as total_episodes
                     FROM contents c 
                     LEFT JOIN categories cat ON c.id_category = cat.id_category 
                     LEFT JOIN content_stats cs ON c.id_content = cs.id_content 
                     ORDER BY c.created_at DESC 
                     LIMIT 4";
    $result_latest = mysqli_query($conn, $query_latest);

    // Get categories
    $query_categories = "SELECT * FROM categories LIMIT 3";
    $result_categories = mysqli_query($conn, $query_categories);
}
?>
<?php include '../page/header.php'; ?>

<?php if ($is_searching): ?>
    <!-- Search Results Section -->
    <div class="container mt-4 mb-5">
        <div class="mb-4 d-flex justify-content-between align-items-center">
            <h5 class="text-secondary mb-0">
                <?php if ($total_results > 0): ?>
                    Ditemukan <span class="text-primary"><?php echo $total_results; ?></span> hasil untuk
                    "<span class="text-white"><?php echo htmlspecialchars($search); ?></span>"
                <?php else: ?>
                    Tidak ada hasil untuk "<span class="text-white"><?php echo htmlspecialchars($search); ?></span>"
                <?php endif; ?>
            </h5>
            <a href="index.php" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-x-lg me-2"></i>Reset
            </a>
        </div>

        <?php if ($total_results > 0): ?>
            <div class="row g-4">
                <?php foreach ($search_results as $content): ?>
                    <div class="col-lg-2 col-md-4 col-sm-6">
                        <a href="detail.php?id=<?php echo $content['id_content']; ?>" class="text-decoration-none">
                            <div class="content-card">
                                <div class="content-img">
                                    <?php if (!empty($content['cover'])): ?>
                                        <img src="<?php echo htmlspecialchars($content['cover']); ?>" alt="<?php echo htmlspecialchars($content['title']); ?>" style="width: 100%; height: 100%; object-fit: cover;">
                                    <?php else: ?>
                                        <i class="bi bi-image"></i>
                                    <?php endif; ?>
                                    <span class="position-absolute top-0 end-0 m-2">
                                        <span class="badge <?php echo $content['status'] == 'ONGOING' ? 'bg-primary' : 'bg-success'; ?>">
                                            <?php echo $content['status']; ?>
                                        </span>
                                    </span>
                                </div>
                                <div class="content-body">
                                    <span class="content-category">
                                        <?php echo htmlspecialchars($content['category_name']); ?>
                                    </span>
                                    <h5 class="content-title"><?php echo htmlspecialchars($content['title']); ?></h5>
                                    <div class="rating">
                                        <i class="bi bi-star-fill"></i>
                                        <span><?php echo number_format($content['average_rating'], 1); ?></span>
                                    </div>
                                    <div class="d-flex justify-content-between mt-2 text-secondary small">
                                        <span><i class="bi bi-layers"></i> <?php echo $content['total_episodes']; ?></span>
                                        <span><i class="bi bi-bookmark"></i> <?php echo $content['total_bookmark']; ?></span>
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <!-- No Results -->
            <div class="text-center py-5">
                <i class="bi bi-search" style="font-size: 4rem; color: var(--text-secondary);"></i>
                <h5 class="mt-3 text-secondary">Tidak ada hasil yang ditemukan</h5>
                <p class="text-secondary">Coba gunakan kata kunci yang berbeda</p>
                <a href="index.php" class="btn btn-primary mt-3">
                    <i class="bi bi-house me-2"></i>Kembali ke Home
                </a>
            </div>
        <?php endif; ?>
    </div>

<?php else: ?>
    <!-- Normal Homepage Content -->
    <?php if ($featured): ?>
        <div class="container">
            <div class="hero-section">
                <div class="row align-items-center">
                    <div class="col-lg-8 hero-content">
                        <span class="badge bg-primary bg-opacity-10 text-primary mb-3 px-3 py-2 rounded-pill">Featured Today</span>
                        <h1 class="hero-title"><?php echo htmlspecialchars($featured['title']); ?></h1>
                        <p class="hero-description">
                            <?php echo htmlspecialchars(substr($featured['synopsis'], 0, 200)); ?>...
                        </p>
                        <div class="d-flex gap-3">
                            <button class="btn btn-primary">
                                <i class="bi bi-play-fill"></i> Watch Trailer
                            </button>
                            <a href="detail.php?id=<?php echo $featured['id_content']; ?>" class="btn btn-outline-light">
                                <i class="bi bi-info-circle"></i> Details
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div class="container mt-5">
        <div class="d-flex justify-content-between align-items-end mb-4">
            <div class="section-title mb-0">
                <i class="bi bi-fire"></i> Popularitas Teratas
            </div>
            <div class="d-none d-md-block">
                <?php while ($cat = mysqli_fetch_assoc($result_categories)): ?>
                    <a href="category.php?id=<?php echo $cat['id_category']; ?>" class="cat-btn">
                        <?php echo htmlspecialchars($cat['category_name']); ?>
                    </a>
                <?php endwhile; ?>
            </div>
        </div>

        <div class="row g-4">
            <?php while ($content = mysqli_fetch_assoc($result_popular)): ?>
                <div class="col-lg-2 col-md-4 col-sm-6">
                    <a href="detail.php?id=<?php echo $content['id_content']; ?>" class="text-decoration-none">
                        <div class="content-card">
                            <div class="content-img">
                                <i class="bi bi-image"></i>
                            </div>
                            <div class="content-body">
                                <span class="content-category">
                                    <?php echo htmlspecialchars($content['category_name']); ?>
                                </span>
                                <h5 class="content-title"><?php echo htmlspecialchars($content['title']); ?></h5>
                                <div class="rating">
                                    <i class="bi bi-star-fill"></i>
                                    <span><?php echo number_format($content['average_rating'], 1); ?></span>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
            <?php endwhile; ?>
        </div>
    </div>

    <div class="container mt-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div class="section-title mb-0">
                <i class="bi bi-clock-history"></i> Update Terbaru
            </div>
            <a href="#" class="text-secondary text-decoration-none small">Lihat Semua <i class="bi bi-arrow-right"></i></a>
        </div>

        <div class="row">
            <?php while ($latest = mysqli_fetch_assoc($result_latest)): ?>
                <div class="col-md-6">
                    <a href="detail.php?id=<?php echo $latest['id_content']; ?>" class="text-decoration-none">
                        <div class="latest-card">
                            <div class="latest-img">
                                <i class="bi bi-book"></i>
                            </div>
                            <div class="latest-info">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <span class="content-category text-primary"><?php echo htmlspecialchars($latest['category_name']); ?></span>
                                    <span class="badge-status <?php echo $latest['status'] == 'ONGOING' ? 'text-bg-primary' : 'text-bg-success'; ?>">
                                        <?php echo $latest['status']; ?>
                                    </span>
                                </div>
                                <h6 class="latest-title"><?php echo htmlspecialchars($latest['title']); ?></h6>
                                <div class="latest-meta">
                                    <span class="me-3"><i class="bi bi-layers"></i> <?php echo $latest['total_episodes']; ?> Ep</span>
                                    <span><i class="bi bi-star-fill text-warning"></i> <?php echo number_format($latest['average_rating'], 1); ?></span>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
            <?php endwhile; ?>
        </div>
    </div>
<?php endif; ?>

<?php include '../page/footer.php'; ?>