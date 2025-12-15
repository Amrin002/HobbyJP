<?php
session_start();
include '../include/koneksi.php';

// Proteksi halaman - harus login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Get user data
$query_user = "SELECT * FROM users WHERE id_user = ?";
$stmt = mysqli_prepare($conn, $query_user);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result_user = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result_user);

// Get user statistics
$query_stats = "SELECT 
    (SELECT COUNT(*) FROM bookmarks WHERE id_user = ?) as total_bookmarks,
    (SELECT COUNT(*) FROM history WHERE id_user = ?) as total_read,
    (SELECT COUNT(*) FROM ratings WHERE id_user = ?) as total_ratings";
$stmt_stats = mysqli_prepare($conn, $query_stats);
mysqli_stmt_bind_param($stmt_stats, "iii", $user_id, $user_id, $user_id);
mysqli_stmt_execute($stmt_stats);
$result_stats = mysqli_stmt_get_result($stmt_stats);
$stats = mysqli_fetch_assoc($result_stats);

// Get recent bookmarks
$query_bookmarks = "SELECT c.*, cat.category_name, cs.average_rating, cs.view_count 
                    FROM bookmarks b
                    JOIN contents c ON b.id_content = c.id_content
                    LEFT JOIN categories cat ON c.id_category = cat.id_category
                    LEFT JOIN content_stats cs ON c.id_content = cs.id_content
                    WHERE b.id_user = ?
                    ORDER BY b.created_at DESC
                    LIMIT 6";
$stmt_bookmarks = mysqli_prepare($conn, $query_bookmarks);
mysqli_stmt_bind_param($stmt_bookmarks, "i", $user_id);
mysqli_stmt_execute($stmt_bookmarks);
$result_bookmarks = mysqli_stmt_get_result($stmt_bookmarks);

// Get recent history
$query_history = "SELECT c.*, e.episode_number, e.title as episode_title, h.last_read,
                  cat.category_name, cs.average_rating
                  FROM history h
                  JOIN episodes e ON h.id_episode = e.id_episode
                  JOIN contents c ON e.id_content = c.id_content
                  LEFT JOIN categories cat ON c.id_category = cat.id_category
                  LEFT JOIN content_stats cs ON c.id_content = cs.id_content
                  WHERE h.id_user = ?
                  ORDER BY h.last_read DESC
                  LIMIT 5";
$stmt_history = mysqli_prepare($conn, $query_history);
mysqli_stmt_bind_param($stmt_history, "i", $user_id);
mysqli_stmt_execute($stmt_history);
$result_history = mysqli_stmt_get_result($stmt_history);
?>
<?php include '../page/header.php'; ?>

<div class="container mt-4">
    <!-- Profile Header -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="hero-section">
                <div class="d-flex align-items-center gap-4">
                    <div style="width: 100px; height: 100px; background: var(--accent-primary); border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                        <i class="bi bi-person-fill" style="font-size: 3rem; color: white;"></i>
                    </div>
                    <div>
                        <h2 class="hero-title mb-2"><?php echo htmlspecialchars($user['username']); ?></h2>
                        <p class="text-secondary mb-0">
                            <i class="bi bi-envelope me-2"></i><?php echo htmlspecialchars($user['email']); ?>
                        </p>
                        <p class="text-secondary small mb-0">
                            <i class="bi bi-calendar me-2"></i>Bergabung sejak <?php echo date('F Y', strtotime($user['created_at'])); ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics -->
    <div class="row g-4 mb-5">
        <div class="col-md-4">
            <div class="content-card text-center p-4">
                <i class="bi bi-bookmark-fill" style="font-size: 2.5rem; color: var(--accent-primary);"></i>
                <h3 class="mt-3 mb-1" style="color: var(--text-primary);"><?php echo number_format($stats['total_bookmarks']); ?></h3>
                <p class="text-secondary mb-0">Bookmarks</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="content-card text-center p-4">
                <i class="bi bi-book-fill" style="font-size: 2.5rem; color: var(--accent-secondary);"></i>
                <h3 class="mt-3 mb-1" style="color: var(--text-primary);"><?php echo number_format($stats['total_read']); ?></h3>
                <p class="text-secondary mb-0">Dibaca</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="content-card text-center p-4">
                <i class="bi bi-star-fill" style="font-size: 2.5rem; color: var(--accent-rating);"></i>
                <h3 class="mt-3 mb-1" style="color: var(--text-primary);"><?php echo number_format($stats['total_ratings']); ?></h3>
                <p class="text-secondary mb-0">Rating Diberikan</p>
            </div>
        </div>
    </div>

    <!-- Recent History -->
    <div class="mb-5">
        <div class="section-title mb-4">
            <i class="bi bi-clock-history"></i> Riwayat Bacaan Terakhir
        </div>
        <?php if (mysqli_num_rows($result_history) > 0): ?>
            <div class="row g-3">
                <?php while ($history = mysqli_fetch_assoc($result_history)): ?>
                    <div class="col-md-12">
                        <div class="latest-card">
                            <div class="latest-img" style="width: 60px; height: 60px;">
                                <i class="bi bi-book"></i>
                            </div>
                            <div class="latest-info flex-grow-1">
                                <h6 class="latest-title mb-1">
                                    <a href="detail.php?id=<?php echo $history['id_content']; ?>" class="text-decoration-none" style="color: var(--text-primary);">
                                        <?php echo htmlspecialchars($history['title']); ?>
                                    </a>
                                </h6>
                                <p class="text-secondary small mb-1">
                                    Episode <?php echo htmlspecialchars($history['episode_number']); ?>: <?php echo htmlspecialchars($history['episode_title']); ?>
                                </p>
                                <span class="content-category">
                                    <i class="bi bi-clock me-1"></i>
                                    <?php
                                    $time_diff = time() - strtotime($history['last_read']);
                                    if ($time_diff < 3600) {
                                        echo floor($time_diff / 60) . ' menit yang lalu';
                                    } elseif ($time_diff < 86400) {
                                        echo floor($time_diff / 3600) . ' jam yang lalu';
                                    } else {
                                        echo floor($time_diff / 86400) . ' hari yang lalu';
                                    }
                                    ?>
                                </span>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="text-center py-5">
                <i class="bi bi-inbox" style="font-size: 3rem; color: var(--text-secondary);"></i>
                <p class="text-secondary mt-3">Belum ada riwayat bacaan</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Bookmarks -->
    <div class="mb-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div class="section-title mb-0">
                <i class="bi bi-bookmark"></i> Bookmark Saya
            </div>
            <a href="bookmarks.php" class="text-secondary text-decoration-none small">
                Lihat Semua <i class="bi bi-arrow-right"></i>
            </a>
        </div>

        <?php if (mysqli_num_rows($result_bookmarks) > 0): ?>
            <div class="row g-4">
                <?php while ($bookmark = mysqli_fetch_assoc($result_bookmarks)): ?>
                    <div class="col-lg-2 col-md-4 col-sm-6">
                        <a href="detail.php?id=<?php echo $bookmark['id_content']; ?>" class="text-decoration-none">
                            <div class="content-card">
                                <div class="content-img" style="height: 240px;">
                                    <i class="bi bi-image"></i>
                                </div>
                                <div class="content-body">
                                    <span class="content-category">
                                        <?php echo htmlspecialchars($bookmark['category_name']); ?>
                                    </span>
                                    <h5 class="content-title"><?php echo htmlspecialchars($bookmark['title']); ?></h5>
                                    <div class="rating">
                                        <i class="bi bi-star-fill"></i>
                                        <span><?php echo number_format($bookmark['average_rating'], 1); ?></span>
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="text-center py-5">
                <i class="bi bi-bookmark" style="font-size: 3rem; color: var(--text-secondary);"></i>
                <p class="text-secondary mt-3">Belum ada bookmark</p>
                <a href="index.php" class="btn btn-primary mt-2">
                    <i class="bi bi-search me-2"></i>Jelajahi Konten
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include '../page/footer.php'; ?>