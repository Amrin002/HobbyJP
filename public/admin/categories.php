<?php
session_start();
include '../../include/koneksi.php';
$current_page = basename($_SERVER['PHP_SELF']);
// protect admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

// Handle flash messages
if (!isset($_SESSION['flash'])) {
    $_SESSION['flash'] = [];
}

// Handle POST actions: add, edit, delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'add') {
        $name = isset($_POST['category_name']) ? trim($_POST['category_name']) : '';
        if ($name === '') {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Category name cannot be empty.'];
        } else {
            // check duplicate
            $q = "SELECT id_category FROM categories WHERE category_name = ? LIMIT 1";
            $s = mysqli_prepare($conn, $q);
            mysqli_stmt_bind_param($s, 's', $name);
            mysqli_stmt_execute($s);
            $r = mysqli_stmt_get_result($s);
            if (mysqli_num_rows($r) > 0) {
                $_SESSION['flash'] = ['type' => 'error', 'message' => 'Category already exists.'];
            } else {
                $ins = "INSERT INTO categories (category_name) VALUES (?)";
                $si = mysqli_prepare($conn, $ins);
                mysqli_stmt_bind_param($si, 's', $name);
                if (mysqli_stmt_execute($si)) {
                    $_SESSION['flash'] = ['type' => 'success', 'message' => 'Category added successfully.'];
                } else {
                    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Failed to add category.'];
                }
            }
        }
        header('Location: categories.php');
        exit;
    }

    if ($action === 'edit') {
        $id = isset($_POST['id_category']) ? intval($_POST['id_category']) : 0;
        $name = isset($_POST['category_name']) ? trim($_POST['category_name']) : '';
        if ($id <= 0 || $name === '') {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Invalid input.'];
        } else {
            // check duplicate on other id
            $q = "SELECT id_category FROM categories WHERE category_name = ? AND id_category != ? LIMIT 1";
            $s = mysqli_prepare($conn, $q);
            mysqli_stmt_bind_param($s, 'si', $name, $id);
            mysqli_stmt_execute($s);
            $r = mysqli_stmt_get_result($s);
            if (mysqli_num_rows($r) > 0) {
                $_SESSION['flash'] = ['type' => 'error', 'message' => 'Another category with same name exists.'];
            } else {
                $upd = "UPDATE categories SET category_name = ? WHERE id_category = ?";
                $su = mysqli_prepare($conn, $upd);
                mysqli_stmt_bind_param($su, 'si', $name, $id);
                if (mysqli_stmt_execute($su)) {
                    $_SESSION['flash'] = ['type' => 'success', 'message' => 'Category updated successfully.'];
                } else {
                    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Failed to update category.'];
                }
            }
        }
        header('Location: categories.php');
        exit;
    }

    if ($action === 'delete') {
        $id = isset($_POST['id_category']) ? intval($_POST['id_category']) : 0;
        if ($id <= 0) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Invalid category selected.'];
        } else {
            $del = "DELETE FROM categories WHERE id_category = ?";
            $sd = mysqli_prepare($conn, $del);
            mysqli_stmt_bind_param($sd, 'i', $id);
            if (mysqli_stmt_execute($sd)) {
                $_SESSION['flash'] = ['type' => 'success', 'message' => 'Category deleted.'];
            } else {
                $_SESSION['flash'] = ['type' => 'error', 'message' => 'Failed to delete category.'];
            }
        }
        header('Location: categories.php');
        exit;
    }
}

// Fetch categories
$q = "SELECT * FROM categories ORDER BY category_name ASC";
$res = mysqli_query($conn, $q);

include 'sidebar.php';
?>

<main class="main-content">
    <header class="admin-header">
        <div>
            <h2 class="fw-bold m-0">Categories</h2>
            <p class="text-secondary m-0">Kelola kategori untuk konten.</p>
        </div>
        <div class="d-flex align-items-center gap-3">
            <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addModal">
                <i class="bi bi-plus-lg"></i> Add Category
            </button>
        </div>
    </header>

    <div class="card bg-transparent border-0">
        <div style="background-color: var(--bg-card); border-radius: 16px; padding: 20px; border: 1px solid var(--bg-element);">
            <?php if (!empty($_SESSION['flash']['message'])): ?>
                <?php $f = $_SESSION['flash'];
                $_SESSION['flash'] = []; ?>
                <div class="alert <?php echo ($f['type'] === 'success') ? 'alert-success' : 'alert-danger'; ?>">
                    <?php echo htmlspecialchars($f['message']); ?>
                </div>
            <?php endif; ?>

            <div class="table-responsive">
                <table class="table-custom" style="width:100%">
                    <thead>
                        <tr>
                            <th style="width:60px">#</th>
                            <th>Category Name</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $no = 1;
                        while ($row = mysqli_fetch_assoc($res)): ?>
                            <tr>
                                <td class="text-secondary"><?php echo $no++; ?></td>
                                <td><?php echo htmlspecialchars($row['category_name']); ?></td>
                                <td>
                                    <div class="d-flex justify-content-end gap-2">
                                        <button class="btn btn-outline-light btn-sm btn-edit" data-id="<?php echo $row['id_category']; ?>" data-name="<?php echo htmlspecialchars($row['category_name'], ENT_QUOTES); ?>">
                                            <i class="bi bi-pencil"></i> Edit
                                        </button>
                                        <form method="post" class="d-inline-block form-delete" style="margin:0">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id_category" value="<?php echo $row['id_category']; ?>">
                                            <button type="button" class="btn btn-danger btn-sm btn-delete">
                                                <i class="bi bi-trash"></i> Delete
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Add Modal -->
    <div class="modal fade" id="addModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content" style="background-color: var(--bg-card); border:1px solid var(--bg-element); color:var(--text-primary)">
                <form method="post">
                    <div class="modal-header">
                        <h5 class="modal-title">Add Category</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add">
                        <div class="mb-3">
                            <label>Category Name</label>
                            <input type="text" name="category_name" class="form-control" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Modal -->
    <div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content" style="background-color: var(--bg-card); border:1px solid var(--bg-element); color:var(--text-primary)">
                <form method="post">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Category</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="id_category" id="edit-id">
                        <div class="mb-3">
                            <label>Category Name</label>
                            <input type="text" name="category_name" id="edit-name" class="form-control" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Edit button
            document.querySelectorAll('.btn-edit').forEach(btn => {
                btn.addEventListener('click', function() {
                    const id = this.getAttribute('data-id');
                    const name = this.getAttribute('data-name');
                    document.getElementById('edit-id').value = id;
                    document.getElementById('edit-name').value = name;
                    var editModal = new bootstrap.Modal(document.getElementById('editModal'));
                    editModal.show();
                });
            });

            // Delete confirmation
            document.querySelectorAll('.btn-delete').forEach(btn => {
                btn.addEventListener('click', function() {
                    if (!confirm('Delete this category? This action cannot be undone.')) return;
                    const form = this.closest('.form-delete');
                    form.submit();
                });
            });
        });
    </script>

</main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>