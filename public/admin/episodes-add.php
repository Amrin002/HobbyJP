<?php
session_start();
include '../../include/koneksi.php';

// protect admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$content_id = isset($_GET['content_id']) ? intval($_GET['content_id']) : 0;
if ($content_id <= 0) {
    header('Location: contents.php');
    exit;
}

// get content title
$q = "SELECT id_content, title FROM contents WHERE id_content = ? LIMIT 1";
$s = mysqli_prepare($conn, $q);
mysqli_stmt_bind_param($s, 'i', $content_id);
mysqli_stmt_execute($s);
$r = mysqli_stmt_get_result($s);
$content = mysqli_fetch_assoc($r);
if (!$content) {
    header('Location: contents.php');
    exit;
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $episode_number = isset($_POST['episode_number']) ? trim($_POST['episode_number']) : '';
    $title = isset($_POST['title']) ? trim($_POST['title']) : '';
    $body = isset($_POST['content']) ? trim($_POST['content']) : '';

    if ($episode_number === '' || $title === '' || $body === '') {
        $errors[] = 'All fields are required.';
    }

    if (empty($errors)) {
        $ins = "INSERT INTO episodes (id_content, episode_number, title, content, created_at) VALUES (?, ?, ?, ?, NOW())";
        $si = mysqli_prepare($conn, $ins);
        mysqli_stmt_bind_param($si, 'isss', $content_id, $episode_number, $title, $body);
        if (mysqli_stmt_execute($si)) {
            // update total_episode
            $q_update = "INSERT INTO content_stats (id_content, total_episode) VALUES (?, 1)
                         ON DUPLICATE KEY UPDATE total_episode = (
                            SELECT COUNT(*) FROM episodes WHERE id_content = ?
                         )";
            $su = mysqli_prepare($conn, $q_update);
            mysqli_stmt_bind_param($su, 'ii', $content_id, $content_id);
            mysqli_stmt_execute($su);

            $_SESSION['flash'] = ['type' => 'success', 'message' => 'Episode added.'];
            header('Location: episodes.php?content_id=' . $content_id);
            exit;
        } else {
            $errors[] = 'Failed to insert episode.';
        }
    }
}

include 'sidebar.php';
?>

<main class="main-content">
    <header class="admin-header">
        <div>
            <h2 class="fw-bold m-0">Add Episode for: <?php echo htmlspecialchars($content['title']); ?></h2>
            <p class="text-secondary m-0">Tambahkan episode baru.</p>
        </div>
        <div class="d-flex align-items-center gap-3">
            <a href="episodes.php?content_id=<?php echo $content_id; ?>" class="btn btn-outline-light btn-sm">Back</a>
        </div>
    </header>

    <div style="background:var(--bg-card); padding:20px; border:1px solid var(--bg-element); border-radius:12px; max-width:900px;">
        <?php if (!empty($errors)): ?><div class="alert alert-danger"><?php foreach ($errors as $e) echo '<div>' . htmlspecialchars($e) . '</div>'; ?></div><?php endif; ?>

        <form method="post">
            <div class="mb-3">
                <label>Episode Number</label>
                <input type="text" name="episode_number" class="form-control" required>
            </div>
            <div class="mb-3">
                <label>Title</label>
                <input type="text" name="title" class="form-control" required>
            </div>
            <div class="mb-3">
                <label>Content</label>
                <textarea name="content" class="form-control" rows="12" required></textarea>
            </div>
            <div class="d-flex gap-2">
                <a href="episodes.php?content_id=<?php echo $content_id; ?>" class="btn btn-outline-light">Cancel</a>
                <button class="btn btn-primary" type="submit">Save Episode</button>
            </div>
        </form>
    </div>

</main>

<?php include 'footer.php'; ?>