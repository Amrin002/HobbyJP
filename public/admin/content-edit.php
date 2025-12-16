<?php
session_start();
include '../../include/koneksi.php';
$current_page = basename($_SERVER['PHP_SELF']);

// protect admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) {
    header('Location: contents.php');
    exit;
}

// fetch existing content
$q = "SELECT * FROM contents WHERE id_content = ? LIMIT 1";
$s = mysqli_prepare($conn, $q);
mysqli_stmt_bind_param($s, 'i', $id);
mysqli_stmt_execute($s);
$r = mysqli_stmt_get_result($s);
$content = mysqli_fetch_assoc($r);
if (!$content) {
    header('Location: contents.php');
    exit;
}

// categories
$qc = "SELECT * FROM categories ORDER BY category_name ASC";
$rc = mysqli_query($conn, $qc);

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = isset($_POST['title']) ? trim($_POST['title']) : '';
    $id_category = isset($_POST['id_category']) ? intval($_POST['id_category']) : 0;
    $synopsis = isset($_POST['synopsis']) ? trim($_POST['synopsis']) : '';
    $status = isset($_POST['status']) && in_array($_POST['status'], ['ONGOING', 'COMPLETED']) ? $_POST['status'] : 'ONGOING';

    if ($title === '' || $id_category <= 0) {
        $errors[] = 'Title and category are required.';
    }

    // handle cover replacement
    $cover_name = $content['cover'];
    if (isset($_FILES['cover']) && $_FILES['cover']['error'] !== UPLOAD_ERR_NO_FILE) {
        $file = $_FILES['cover'];
        $allowed = ['image/jpeg', 'image/png', 'image/gif'];
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'Error uploading cover.';
        } elseif (!in_array(mime_content_type($file['tmp_name']), $allowed)) {
            $errors[] = 'Cover must be an image (jpg, png, gif).';
        } elseif ($file['size'] > 2 * 1024 * 1024) {
            $errors[] = 'Cover must be smaller than 2MB.';
        } else {
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $cover_name = time() . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
            $dest = __DIR__ . '/../uploads/' . $cover_name;
            if (!move_uploaded_file($file['tmp_name'], $dest)) {
                $errors[] = 'Failed to move uploaded cover.';
            } else {
                // optionally delete old cover file
                if (!empty($content['cover']) && file_exists(__DIR__ . '/../uploads/' . $content['cover'])) {
                    @unlink(__DIR__ . '/../uploads/' . $content['cover']);
                }
            }
        }
    }

    if (empty($errors)) {
        $upd = "UPDATE contents SET id_category = ?, title = ?, cover = ?, synopsis = ?, status = ? WHERE id_content = ?";
        $su = mysqli_prepare($conn, $upd);
        mysqli_stmt_bind_param($su, 'issssi', $id_category, $title, $cover_name, $synopsis, $status, $id);
        if (mysqli_stmt_execute($su)) {
            // update total_episode based on episodes table
            $qcnt = "UPDATE content_stats SET total_episode = (
                        SELECT COUNT(*) FROM episodes WHERE id_content = ?
                     ) WHERE id_content = ?";
            $scnt = mysqli_prepare($conn, $qcnt);
            mysqli_stmt_bind_param($scnt, 'ii', $id, $id);
            mysqli_stmt_execute($scnt);

            $_SESSION['flash'] = ['type' => 'success', 'message' => 'Content updated successfully.'];
            header('Location: contents.php');
            exit;
        } else {
            $errors[] = 'Failed to update content.';
        }
    }
}

include 'sidebar.php';
?>

<main class="main-content">
    <header class="admin-header">
        <div>
            <h2 class="fw-bold m-0">Edit Content</h2>
            <p class="text-secondary m-0">Ubah data judul.</p>
        </div>
        <div class="d-flex align-items-center gap-3">
            <a href="contents.php" class="btn btn-outline-light btn-sm">Back to List</a>
        </div>
    </header>

    <div style="background-color: var(--bg-card); border-radius: 12px; padding:20px; border:1px solid var(--bg-element); max-width:900px;">
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <?php foreach ($errors as $e) echo '<div>' . htmlspecialchars($e) . '</div>'; ?>
            </div>
        <?php endif; ?>

        <form method="post" enctype="multipart/form-data">
            <div class="mb-3">
                <label class="form-label">Title</label>
                <input type="text" name="title" class="form-control" value="<?php echo htmlspecialchars($content['title']); ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Category</label>
                <select name="id_category" class="form-select" required>
                    <option value="">-- Select Category --</option>
                    <?php while ($c = mysqli_fetch_assoc($rc)): ?>
                        <option value="<?php echo $c['id_category']; ?>" <?php echo ($c['id_category'] == $content['id_category']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($c['category_name']); ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Synopsis</label>
                <textarea name="synopsis" class="form-control" rows="6"><?php echo htmlspecialchars($content['synopsis']); ?></textarea>
            </div>
            <div class="mb-3">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="ONGOING" <?php echo ($content['status'] == 'ONGOING') ? 'selected' : ''; ?>>ONGOING</option>
                    <option value="COMPLETED" <?php echo ($content['status'] == 'COMPLETED') ? 'selected' : ''; ?>>COMPLETED</option>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Current Cover</label>
                <div class="mb-2">
                    <?php if (!empty($content['cover'])): ?>
                        <img src="../uploads/<?php echo htmlspecialchars($content['cover']); ?>" style="height:120px; border-radius:8px;" alt="cover">
                    <?php else: ?>
                        <div class="content-thumb-sm d-flex align-items-center justify-content-center text-secondary">
                            <i class="bi bi-image"></i>
                        </div>
                    <?php endif; ?>
                </div>
                <label class="form-label">Replace Cover (optional)</label>
                <input type="file" name="cover" accept="image/*" class="form-control">
            </div>
            <div class="d-flex gap-2">
                <a href="contents.php" class="btn btn-outline-light">Cancel</a>
                <button type="submit" class="btn btn-primary">Update Content</button>
            </div>
        </form>
    </div>

</main>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>