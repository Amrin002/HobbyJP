<?php
include '../include/koneksi.php';
// Get featured content (random atau terbaru)
$query_featured = "SELECT c.*, cat.category_name, cs.average_rating, cs.view_count 
                   FROM contents c 
                   LEFT JOIN categories cat ON c.id_category = cat.id_category 
                   LEFT JOIN content_stats cs ON c.id_content = cs.id_content 
                   ORDER BY c.created_at DESC 
                   LIMIT 1";
$result_featured = mysqli_query($conn, $query_featured);
$featured = mysqli_fetch_assoc($result_featured);

// Get popular contents (berdasarkan rating dan views)
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
?>
<?php include '../page/header.php'; ?>
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
<?php include '../page/footer.php'; ?>