<?php
session_start();
include '../../include/koneksi.php';
$current_page = basename($_SERVER['PHP_SELF']);

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// Validasi ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error_message'] = "Invalid user ID.";
    header("Location: users.php");
    exit;
}

$id_user = intval($_GET['id']);

// Ambil data user
$query = "SELECT * FROM users WHERE id_user = $id_user";
$result = mysqli_query($conn, $query);

if (mysqli_num_rows($result) == 0) {
    $_SESSION['error_message'] = "User not found.";
    header("Location: users.php");
    exit;
}

$user = mysqli_fetch_assoc($result);
$errors = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    $role = $_POST['role'];

    // Validasi
    if (empty($username)) {
        $errors[] = "Username is required.";
    } elseif (strlen($username) < 3) {
        $errors[] = "Username must be at least 3 characters.";
    }

    if (empty($email)) {
        $errors[] = "Email is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    }

    // Validasi password jika diisi
    if (!empty($new_password)) {
        if (strlen($new_password) < 6) {
            $errors[] = "Password must be at least 6 characters.";
        }
        if ($new_password !== $confirm_password) {
            $errors[] = "Passwords do not match.";
        }
    }

    if (!in_array($role, ['user', 'admin'])) {
        $errors[] = "Invalid role selected.";
    }

    // Cek username sudah ada (kecuali username sendiri)
    if (empty($errors)) {
        $check_username = mysqli_query($conn, "SELECT id_user FROM users WHERE username = '" . mysqli_real_escape_string($conn, $username) . "' AND id_user != $id_user");
        if (mysqli_num_rows($check_username) > 0) {
            $errors[] = "Username already exists.";
        }
    }

    // Cek email sudah ada (kecuali email sendiri)
    if (empty($errors)) {
        $check_email = mysqli_query($conn, "SELECT id_user FROM users WHERE email = '" . mysqli_real_escape_string($conn, $email) . "' AND id_user != $id_user");
        if (mysqli_num_rows($check_email) > 0) {
            $errors[] = "Email already exists.";
        }
    }

    // Tidak bisa mengubah role admin sendiri
    if ($id_user == $_SESSION['user_id'] && $role != 'admin') {
        $errors[] = "You cannot change your own admin role.";
    }

    // Update jika tidak ada error
    if (empty($errors)) {
        $update_query = "UPDATE users SET 
                        username = '" . mysqli_real_escape_string($conn, $username) . "',
                        email = '" . mysqli_real_escape_string($conn, $email) . "',
                        role = '" . mysqli_real_escape_string($conn, $role) . "'";

        // Tambahkan password jika diisi
        if (!empty($new_password)) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $update_query .= ", password = '" . mysqli_real_escape_string($conn, $hashed_password) . "'";
        }

        $update_query .= " WHERE id_user = $id_user";

        if (mysqli_query($conn, $update_query)) {
            $_SESSION['success_message'] = "User '$username' successfully updated!";
            header("Location: users.php");
            exit;
        } else {
            $errors[] = "Failed to update user: " . mysqli_error($conn);
        }
    }
}

// Statistik user
$stats_query = "SELECT 
    (SELECT COUNT(*) FROM bookmarks WHERE id_user = $id_user) as total_bookmarks,
    (SELECT COUNT(*) FROM history WHERE id_user = $id_user) as total_history,
    (SELECT COUNT(*) FROM ratings WHERE id_user = $id_user) as total_ratings
    FROM users WHERE id_user = $id_user";
$stats = mysqli_fetch_assoc(mysqli_query($conn, $stats_query));
?>
<?php include 'sidebar.php'; ?>
<main class="main-content">
    <header class="admin-header mb-4">
        <div>
            <h2 class="fw-bold m-0">Edit User</h2>
            <p class="text-secondary m-0">Update user account information</p>
        </div>
        <div>
            <a href="users.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Back to Users
            </a>
        </div>
    </header>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle me-2"></i>
            <strong>Error!</strong>
            <ul class="mb-0 mt-2">
                <?php foreach ($errors as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row">
        <!-- User Stats -->
        <div class="col-lg-4 mb-4">
            <div class="card" style="background-color: var(--bg-card); border: 1px solid var(--bg-element); border-radius: 16px;">
                <div class="card-body p-4">
                    <div class="text-center mb-4">
                        <div class="bg-primary bg-opacity-25 rounded-circle d-inline-flex align-items-center justify-content-center text-primary mb-3"
                            style="width: 80px; height: 80px; font-size: 2rem; font-weight: 700;">
                            <?php echo strtoupper(substr($user['username'], 0, 2)); ?>
                        </div>
                        <h5 class="fw-bold mb-1"><?php echo htmlspecialchars($user['username']); ?></h5>
                        <p class="text-secondary mb-0"><?php echo htmlspecialchars($user['email']); ?></p>
                        <span class="badge <?php echo $user['role'] == 'admin' ? 'bg-danger' : 'bg-secondary'; ?> mt-2">
                            <?php echo ucfirst($user['role']); ?>
                        </span>
                    </div>

                    <hr style="border-color: var(--bg-element);">

                    <div class="mb-3">
                        <small class="text-secondary d-block mb-1">User ID</small>
                        <strong>#<?php echo $user['id_user']; ?></strong>
                    </div>

                    <div class="mb-3">
                        <small class="text-secondary d-block mb-1">Joined</small>
                        <strong><?php echo date('d F Y', strtotime($user['created_at'])); ?></strong>
                    </div>

                    <hr style="border-color: var(--bg-element);">

                    <h6 class="fw-bold mb-3">User Activity</h6>

                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-secondary"><i class="bi bi-bookmark-fill text-warning"></i> Bookmarks</span>
                        <strong><?php echo $stats['total_bookmarks']; ?></strong>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-secondary"><i class="bi bi-clock-history text-info"></i> Read History</span>
                        <strong><?php echo $stats['total_history']; ?></strong>
                    </div>

                    <div class="d-flex justify-content-between align-items-center">
                        <span class="text-secondary"><i class="bi bi-star-fill text-warning"></i> Ratings Given</span>
                        <strong><?php echo $stats['total_ratings']; ?></strong>
                    </div>
                </div>
            </div>
        </div>

        <!-- Edit Form -->
        <div class="col-lg-8">
            <div class="card" style="background-color: var(--bg-card); border: 1px solid var(--bg-element); border-radius: 16px;">
                <div class="card-body p-4">
                    <form method="POST" action="">
                        <div class="row g-4">
                            <!-- Username -->
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Username <span class="text-danger">*</span></label>
                                <input type="text" name="username" class="form-control"
                                    value="<?php echo htmlspecialchars($user['username']); ?>"
                                    placeholder="Enter username" required>
                                <small class="text-secondary">Minimum 3 characters</small>
                            </div>

                            <!-- Email -->
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Email <span class="text-danger">*</span></label>
                                <input type="email" name="email" class="form-control"
                                    value="<?php echo htmlspecialchars($user['email']); ?>"
                                    placeholder="user@example.com" required>
                                <small class="text-secondary">Valid email address</small>
                            </div>

                            <div class="col-12">
                                <hr style="border-color: var(--bg-element);">
                                <h6 class="fw-bold mb-3">Change Password (Optional)</h6>
                                <p class="text-secondary small">Leave blank if you don't want to change the password</p>
                            </div>

                            <!-- New Password -->
                            <div class="col-md-6">
                                <label class="form-label fw-bold">New Password</label>
                                <div class="input-group">
                                    <input type="password" name="new_password" id="new_password" class="form-control"
                                        placeholder="Enter new password">
                                    <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('new_password')">
                                        <i class="bi bi-eye" id="new_password-icon"></i>
                                    </button>
                                </div>
                                <small class="text-secondary">Minimum 6 characters</small>
                            </div>

                            <!-- Confirm Password -->
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Confirm New Password</label>
                                <div class="input-group">
                                    <input type="password" name="confirm_password" id="confirm_password" class="form-control"
                                        placeholder="Confirm new password">
                                    <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('confirm_password')">
                                        <i class="bi bi-eye" id="confirm_password-icon"></i>
                                    </button>
                                </div>
                                <small class="text-secondary">Must match new password</small>
                            </div>

                            <div class="col-12">
                                <hr style="border-color: var(--bg-element);">
                            </div>

                            <!-- Role -->
                            <div class="col-12">
                                <label class="form-label fw-bold">Role <span class="text-danger">*</span></label>
                                <?php if ($id_user == $_SESSION['user_id']): ?>
                                    <div class="alert alert-warning">
                                        <i class="bi bi-info-circle me-2"></i>
                                        You cannot change your own admin role.
                                    </div>
                                <?php endif; ?>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <div class="form-check p-3 border rounded" style="border-color: var(--bg-element) !important;">
                                            <input class="form-check-input" type="radio" name="role" id="role_user"
                                                value="user" <?php echo $user['role'] == 'user' ? 'checked' : ''; ?>
                                                <?php echo $id_user == $_SESSION['user_id'] ? 'disabled' : ''; ?>>
                                            <label class="form-check-label w-100" for="role_user">
                                                <div class="d-flex align-items-center">
                                                    <i class="bi bi-person fs-4 text-primary me-3"></i>
                                                    <div>
                                                        <div class="fw-bold">Regular User</div>
                                                        <small class="text-secondary">Can read content, bookmark, and rate</small>
                                                    </div>
                                                </div>
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-check p-3 border rounded" style="border-color: var(--bg-element) !important;">
                                            <input class="form-check-input" type="radio" name="role" id="role_admin"
                                                value="admin" <?php echo $user['role'] == 'admin' ? 'checked' : ''; ?>
                                                <?php echo $id_user == $_SESSION['user_id'] ? 'disabled' : ''; ?>>
                                            <label class="form-check-label w-100" for="role_admin">
                                                <div class="d-flex align-items-center">
                                                    <i class="bi bi-shield-fill fs-4 text-danger me-3"></i>
                                                    <div>
                                                        <div class="fw-bold">Administrator</div>
                                                        <small class="text-secondary">Full access to admin panel</small>
                                                    </div>
                                                </div>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <?php if ($id_user == $_SESSION['user_id']): ?>
                                    <input type="hidden" name="role" value="admin">
                                <?php endif; ?>
                            </div>

                            <!-- Submit Buttons -->
                            <div class="col-12">
                                <hr style="border-color: var(--bg-element);">
                                <div class="d-flex gap-2 justify-content-end">
                                    <a href="users.php" class="btn btn-outline-secondary px-4">
                                        <i class="bi bi-x-lg"></i> Cancel
                                    </a>
                                    <button type="submit" class="btn btn-primary px-4">
                                        <i class="bi bi-check-lg"></i> Save Changes
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    function togglePassword(fieldId) {
        const field = document.getElementById(fieldId);
        const icon = document.getElementById(fieldId + '-icon');

        if (field.type === 'password') {
            field.type = 'text';
            icon.classList.remove('bi-eye');
            icon.classList.add('bi-eye-slash');
        } else {
            field.type = 'password';
            icon.classList.remove('bi-eye-slash');
            icon.classList.add('bi-eye');
        }
    }
</script>
</body>

</html>