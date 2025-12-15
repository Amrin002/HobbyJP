<?php
session_start();
include '../include/koneksi.php';

// Get content ID from URL
$id_content = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id_content == 0) {
    header('Location: index.php');
    exit;
}

// Get content details
$query_content = "SELECT c.*, cat.category_name, cs.average_rating, cs.view_count, cs.total_bookmark, cs.total_episode
                  FROM contents c 
                  LEFT JOIN categories cat ON c.id_category = cat.id_category 
                  LEFT JOIN content_stats cs ON c.id_content = cs.id_content 
                  WHERE c.id_content = ?";
$stmt = mysqli_prepare($conn, $query_content);
mysqli_stmt_bind_param($stmt, "i", $id_content);
mysqli_stmt_execute($stmt);
$result_content = mysqli_stmt_get_result($stmt);
$content = mysqli_fetch_assoc($result_content);

if (!$content) {
    header('Location: index.php');
    exit;
}

// Check if user already rated this content
$user_rating = 0;
if (isset($_SESSION['user_id'])) {
    $query_user_rating = "SELECT rating FROM ratings WHERE id_user = ? AND id_content = ?";
    $stmt_user_rating = mysqli_prepare($conn, $query_user_rating);
    mysqli_stmt_bind_param($stmt_user_rating, "ii", $_SESSION['user_id'], $id_content);
    mysqli_stmt_execute($stmt_user_rating);
    $result_user_rating = mysqli_stmt_get_result($stmt_user_rating);
    if ($row = mysqli_fetch_assoc($result_user_rating)) {
        $user_rating = $row['rating'];
    }
}

// Get episodes
$query_episodes = "SELECT * FROM episodes WHERE id_content = ? ORDER BY episode_number ASC";
$stmt_episodes = mysqli_prepare($conn, $query_episodes);
mysqli_stmt_bind_param($stmt_episodes, "i", $id_content);
mysqli_stmt_execute($stmt_episodes);
$result_episodes = mysqli_stmt_get_result($stmt_episodes);

// Get related content (same category)
$query_related = "SELECT c.*, cat.category_name, cs.average_rating 
                  FROM contents c 
                  LEFT JOIN categories cat ON c.id_category = cat.id_category 
                  LEFT JOIN content_stats cs ON c.id_content = cs.id_content 
                  WHERE c.id_category = ? AND c.id_content != ? 
                  ORDER BY cs.average_rating DESC 
                  LIMIT 6";
$stmt_related = mysqli_prepare($conn, $query_related);
mysqli_stmt_bind_param($stmt_related, "ii", $content['id_category'], $id_content);
mysqli_stmt_execute($stmt_related);
$result_related = mysqli_stmt_get_result($stmt_related);

// Update view count
$update_views = "UPDATE content_stats SET view_count = view_count + 1 WHERE id_content = ?";
$stmt_update = mysqli_prepare($conn, $update_views);
mysqli_stmt_bind_param($stmt_update, "i", $id_content);
mysqli_stmt_execute($stmt_update);
?>
<?php include '../page/header.php'; ?>

<style>
    .rating-container {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 15px;
        background-color: var(--bg-card);
        border-radius: 10px;
        margin-bottom: 20px;
    }

    .rating-stars {
        display: flex;
        gap: 5px;
    }

    .star {
        font-size: 1.5rem;
        color: #4a5568;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .star.active,
    .star:hover {
        color: var(--accent-gold);
        transform: scale(1.1);
    }

    .star.hover-effect {
        color: var(--accent-gold);
    }

    .rating-text {
        font-size: 0.9rem;
        color: var(--text-secondary);
    }

    .rating-login-prompt {
        font-size: 0.9rem;
        color: var(--text-secondary);
    }

    .rating-login-prompt a {
        color: var(--accent-blue);
        text-decoration: none;
    }

    .rating-login-prompt a:hover {
        text-decoration: underline;
    }

    .user-rating-display {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 8px 15px;
        background-color: rgba(59, 130, 246, 0.1);
        border-radius: 8px;
    }

    .user-rating-display i {
        color: var(--accent-gold);
    }
</style>

<div class="container mt-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php" class="text-secondary text-decoration-none">Home</a></li>
            <li class="breadcrumb-item">
                <a href="category.php?id=<?php echo $content['id_category']; ?>" class="text-secondary text-decoration-none">
                    <?php echo htmlspecialchars($content['category_name']); ?>
                </a>
            </li>
            <li class="breadcrumb-item active text-primary" aria-current="page"><?php echo htmlspecialchars($content['title']); ?></li>
        </ol>
    </nav>

    <!-- Content Header -->
    <div class="row g-4">
        <div class="col-lg-3">
            <div class="content-img" style="height: 400px; border-radius: 12px;">
                <i class="bi bi-image" style="font-size: 4rem;"></i>
            </div>
        </div>
        <div class="col-lg-9">
            <div class="d-flex align-items-center gap-3 mb-3">
                <span class="badge bg-primary bg-opacity-10 text-primary px-3 py-2 rounded-pill">
                    <?php echo htmlspecialchars($content['category_name']); ?>
                </span>
                <span class="badge <?php echo $content['status'] == 'ONGOING' ? 'bg-primary' : 'bg-success'; ?> px-3 py-2 rounded-pill">
                    <?php echo htmlspecialchars($content['status']); ?>
                </span>
            </div>

            <h1 class="hero-title mb-3"><?php echo htmlspecialchars($content['title']); ?></h1>

            <div class="d-flex align-items-center gap-4 mb-4">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-star-fill" style="color: var(--accent-gold);"></i>
                    <span class="fw-bold"><?php echo number_format($content['average_rating'], 1); ?></span>
                    <span class="text-secondary">/5.0</span>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-eye-fill text-secondary"></i>
                    <span class="text-secondary"><?php echo number_format($content['view_count']); ?> views</span>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-bookmark-fill text-secondary"></i>
                    <span class="text-secondary"><?php echo number_format($content['total_bookmark']); ?> bookmarks</span>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-layers-fill text-secondary"></i>
                    <span class="text-secondary"><?php echo number_format($content['total_episode']); ?> episodes</span>
                </div>
            </div>

            <!-- Rating Section -->
            <div class="rating-container">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <div>
                        <div class="mb-2 fw-semibold">Your Rating:</div>
                        <div class="rating-stars" id="rating-stars">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <i class="bi bi-star-fill star <?php echo $i <= $user_rating ? 'active' : ''; ?>"
                                    data-rating="<?php echo $i; ?>"></i>
                            <?php endfor; ?>
                        </div>
                        <div class="rating-text mt-2" id="rating-message">
                            <?php if ($user_rating > 0): ?>
                                You rated this <?php echo $user_rating; ?> out of 5 stars
                            <?php else: ?>
                                Click on stars to rate
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php if ($user_rating > 0): ?>
                        <div class="ms-auto">
                            <button class="btn btn-sm btn-outline-danger" id="remove-rating">
                                <i class="bi bi-trash"></i> Remove Rating
                            </button>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="rating-login-prompt">
                        <i class="bi bi-star text-warning"></i>
                        Please <a href="login.php?redirect=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>">login</a> to rate this content
                    </div>
                <?php endif; ?>
            </div>

            <div class="d-flex gap-3 mb-4">
                <button class="btn btn-primary">
                    <i class="bi bi-play-fill"></i> Read Now
                </button>
                <button class="btn btn-outline-light">
                    <i class="bi bi-bookmark"></i> Bookmark
                </button>
                <button class="btn btn-outline-light">
                    <i class="bi bi-share"></i> Share
                </button>
            </div>

            <div>
                <h5 class="mb-3" style="color: var(--text-primary);">Synopsis</h5>
                <p class="text-secondary" style="line-height: 1.8;">
                    <?php echo nl2br(htmlspecialchars($content['synopsis'])); ?>
                </p>
            </div>
        </div>
    </div>

    <!-- Episodes List -->
    <div class="mt-5">
        <div class="section-title mb-4">
            <i class="bi bi-list-ul"></i> Episode List
        </div>

        <div class="row g-3">
            <?php while ($episode = mysqli_fetch_assoc($result_episodes)): ?>
                <div class="col-md-6 col-lg-4">
                    <a href="read.php?id=<?php echo $episode['id_episode']; ?>" class="text-decoration-none">
                        <div class="latest-card">
                            <div class="latest-img" style="width: 50px; height: 50px;">
                                <i class="bi bi-file-earmark-text"></i>
                            </div>
                            <div class="latest-info">
                                <h6 class="latest-title mb-1">
                                    Episode <?php echo htmlspecialchars($episode['episode_number']); ?>
                                </h6>
                                <p class="text-secondary small mb-0" style="font-size: 0.85rem;">
                                    <?php echo htmlspecialchars($episode['title']); ?>
                                </p>
                            </div>
                        </div>
                    </a>
                </div>
            <?php endwhile; ?>
        </div>
    </div>

    <!-- Related Content -->
    <?php if (mysqli_num_rows($result_related) > 0): ?>
        <div class="mt-5">
            <div class="section-title mb-4">
                <i class="bi bi-collection"></i> Related Content
            </div>

            <div class="row g-4">
                <?php while ($related = mysqli_fetch_assoc($result_related)): ?>
                    <div class="col-lg-2 col-md-4 col-sm-6">
                        <a href="detail.php?id=<?php echo $related['id_content']; ?>" class="text-decoration-none">
                            <div class="content-card">
                                <div class="content-img" style="height: 240px;">
                                    <i class="bi bi-image"></i>
                                </div>
                                <div class="content-body">
                                    <span class="content-category">
                                        <?php echo htmlspecialchars($related['category_name']); ?>
                                    </span>
                                    <h5 class="content-title"><?php echo htmlspecialchars($related['title']); ?></h5>
                                    <div class="rating">
                                        <i class="bi bi-star-fill"></i>
                                        <span><?php echo number_format($related['average_rating'], 1); ?></span>
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php if (isset($_SESSION['user_id'])): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const stars = document.querySelectorAll('.star');
            const ratingMessage = document.getElementById('rating-message');
            const removeButton = document.getElementById('remove-rating');

            // Hover effect
            stars.forEach((star, index) => {
                star.addEventListener('mouseenter', function() {
                    stars.forEach((s, i) => {
                        if (i <= index) {
                            s.classList.add('hover-effect');
                        } else {
                            s.classList.remove('hover-effect');
                        }
                    });
                });

                star.addEventListener('mouseleave', function() {
                    stars.forEach(s => s.classList.remove('hover-effect'));
                });

                // Click to rate
                star.addEventListener('click', function() {
                    const rating = parseInt(this.getAttribute('data-rating'));
                    submitRating(rating);
                });
            });

            // Remove rating
            if (removeButton) {
                removeButton.addEventListener('click', function() {
                    if (confirm('Are you sure you want to remove your rating?')) {
                        removeRating();
                    }
                });
            }

            function submitRating(rating) {
                fetch('ajax/submit_rating.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            id_content: <?php echo $id_content; ?>,
                            rating: rating
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Update stars
                            stars.forEach((s, i) => {
                                if (i < rating) {
                                    s.classList.add('active');
                                } else {
                                    s.classList.remove('active');
                                }
                            });

                            // Update message
                            ratingMessage.textContent = `You rated this ${rating} out of 5 stars`;

                            // Reload page to update stats
                            setTimeout(() => {
                                location.reload();
                            }, 1000);
                        } else {
                            alert(data.message || 'Failed to submit rating');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('An error occurred while submitting your rating');
                    });
            }

            function removeRating() {
                fetch('ajax/remove_rating.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            id_content: <?php echo $id_content; ?>
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            location.reload();
                        } else {
                            alert(data.message || 'Failed to remove rating');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('An error occurred while removing your rating');
                    });
            }
        });
    </script>
<?php endif; ?>

<?php include '../page/footer.php'; ?>