<?php
session_start();
include '../include/koneksi.php';

if (!isset($_SESSION['user_id'])) {
    // Redirect to login with return
    header('Location: login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

$user_id = $_SESSION['user_id'];

// Get bookmarks
$query_bookmarks = "SELECT b.*, c.title, c.cover, cat.category_name, cs.average_rating
                    FROM bookmarks b
                    LEFT JOIN contents c ON b.id_content = c.id_content
                    LEFT JOIN categories cat ON c.id_category = cat.id_category
                    LEFT JOIN content_stats cs ON c.id_content = cs.id_content
                    WHERE b.id_user = ?
                    ORDER BY b.created_at DESC";
$stmt = mysqli_prepare($conn, $query_bookmarks);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result_bookmarks = mysqli_stmt_get_result($stmt);

include '../page/header.php';
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0"><i class="bi bi-bookmark"></i> Bookmarks Saya</h4>
        <a href="profile.php" class="text-secondary small">Back to Profile</a>
    </div>

    <?php if (mysqli_num_rows($result_bookmarks) == 0): ?>
        <div class="card p-4 text-center">
            <i class="bi bi-bookmark" style="font-size: 3rem; color: var(--accent-primary);"></i>
            <p class="mt-3 mb-0 text-secondary">Belum ada bookmark. Telusuri koleksi kami dan tambahkan favoritmu.</p>
        </div>
    <?php else: ?>
        <div class="row g-4">
            <?php while ($b = mysqli_fetch_assoc($result_bookmarks)): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="content-card p-3 d-flex gap-3">
                        <div style="width: 80px; height: 100px; background: var(--bg-element); border-radius: 8px; display:flex; align-items:center; justify-content:center;">
                            <i class="bi bi-image"></i>
                        </div>
                        <div class="flex-fill">
                            <h6 class="mb-1"><a href="detail.php?id=<?php echo $b['id_content']; ?>" class="text-decoration-none"><?php echo htmlspecialchars($b['title']); ?></a></h6>
                            <div class="text-secondary small mb-2"><?php echo htmlspecialchars($b['category_name']); ?> • <i class="bi bi-star-fill text-warning"></i> <?php echo number_format($b['average_rating'], 1); ?></div>
                            <div class="d-flex gap-2">
                                <a href="detail.php?id=<?php echo $b['id_content']; ?>" class="btn btn-sm btn-primary">Open</a>
                                <button class="btn btn-sm btn-outline-danger remove-bookmark" data-id="<?php echo $b['id_bookmark']; ?>" data-content="<?php echo $b['id_content']; ?>">Remove</button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php endif; ?>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.remove-bookmark').forEach(btn => {
            btn.addEventListener('click', function() {
                if (!confirm('Remove this bookmark?')) return;
                const id_content = this.getAttribute('data-content');
                fetch('ajax/remove_bookmark.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        id_content: id_content
                    })
                }).then(r => r.json()).then(data => {
                    if (data.success) location.reload();
                    else alert(data.message || 'Failed to remove');
                }).catch(e => {
                    console.error(e);
                    alert('Error');
                });
            });
        });
    });
</script>

<?php include '../page/footer.php'; ?>