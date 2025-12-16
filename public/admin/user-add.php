<?php
session_start();
include '../../include/koneksi.php';
$current_page = basename($_SERVER['PHP_SELF']);

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
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

    if (empty($password)) {
        $errors[] = "Password is required.";
    } elseif (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters.";
    }

    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match.";
    }

    if (!in_array($role, ['user', 'admin'])) {
        $errors[] = "Invalid role selected.";
    }

    // Cek username sudah ada
    if (empty($errors)) {
        $check_username = mysqli_query($conn, "SELECT id_user FROM users WHERE username = '" . mysqli_real_escape_string($conn, $username) . "'");
        if (mysqli_num_rows($check_username) > 0) {
            $errors[] = "Username already exists.";
        }
    }

    // Cek email sudah ada
    if (empty($errors)) {
        $check_email = mysqli_query($conn, "SELECT id_user FROM users WHERE email = '" . mysqli_real_escape_string($conn, $email) . "'");
        if (mysqli_num_rows($check_email) > 0) {
            $errors[] = "Email already exists.";
        }
    }

    // Insert jika tidak ada error
    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        $insert_query = "INSERT INTO users (username, email, password, role, created_at) 
                        VALUES (
                            '" . mysqli_real_escape_string($conn, $username) . "',
                            '" . mysqli_real_escape_string($conn, $email) . "',
                            '" . mysqli_real_escape_string($conn, $hashed_password) . "',
                            '" . mysqli_real_escape_string($conn, $role) . "',
                            NOW()
                        )";

        if (mysqli_query($conn, $insert_query)) {
            $_SESSION['success_message'] = "User '$username' successfully created!";
            header("Location: users.php");
            exit;
        } else {
            $errors[] = "Failed to create user: " . mysqli_error($conn);
        }
    }
}
?>
<?php include 'sidebar.php'; ?>
<main class="main-content">
    <header class="admin-header mb-4">
        <div>
            <h2 class="fw-bold m-0">Add New User</h2>
            <p class="text-secondary m-0">Create a new user account</p>
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

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card" style="background-color: var(--bg-card); border: 1px solid var(--bg-element); border-radius: 16px;">
                <div class="card-body p-4">
                    <form method="POST" action="">
                        <div class="row g-4">
                            <!-- Username -->
                            <div class="col-md-6">
                                <label class="form-label fw-bold text-white">Username <span class="text-danger">*</span></label>
                                <input type="text" name="username" class="form-control bg-dark text-white border-secondary"
                                    value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
                                    placeholder="Enter username" required style="background-color: #1e293b !important; color: #f8fafc !important;">
                                <small class="text-secondary">Minimum 3 characters</small>
                            </div>

                            <!-- Email -->
                            <div class="col-md-6">
                                <label class="form-label fw-bold text-white">Email <span class="text-danger">*</span></label>
                                <input type="email" name="email" class="form-control bg-dark text-white border-secondary"
                                    value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                                    placeholder="user@example.com" required style="background-color: #1e293b !important; color: #f8fafc !important;">
                                <small class="text-secondary">Valid email address</small>
                            </div>

                            <!-- Password -->
                            <div class="col-md-6">
                                <label class="form-label fw-bold text-white">Password <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="password" name="password" id="password" class="form-control bg-dark text-white border-secondary"
                                        placeholder="Enter password" required style="background-color: #1e293b !important; color: #f8fafc !important;">
                                    <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('password')">
                                        <i class="bi bi-eye" id="password-icon"></i>
                                    </button>
                                </div>
                                <small class="text-secondary">Minimum 6 characters</small>
                            </div>

                            <!-- Confirm Password -->
                            <div class="col-md-6">
                                <label class="form-label fw-bold text-white">Confirm Password <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="password" name="confirm_password" id="confirm_password" class="form-control bg-dark text-white border-secondary"
                                        placeholder="Confirm password" required style="background-color: #1e293b !important; color: #f8fafc !important;">
                                    <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('confirm_password')">
                                        <i class="bi bi-eye" id="confirm_password-icon"></i>
                                    </button>
                                </div>
                                <small class="text-secondary">Must match password</small>
                            </div>

                            <!-- Role -->
                            <div class="col-12">
                                <label class="form-label fw-bold">Role <span class="text-danger">*</span></label>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <div class="form-check p-3 border rounded" style="border-color: var(--bg-element) !important;">
                                            <input class="form-check-input" type="radio" name="role" id="role_user"
                                                value="user" <?php echo (!isset($_POST['role']) || $_POST['role'] == 'user') ? 'checked' : ''; ?>>
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
                                                value="admin" <?php echo (isset($_POST['role']) && $_POST['role'] == 'admin') ? 'checked' : ''; ?>>
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
                            </div>

                            <!-- Submit Buttons -->
                            <div class="col-12">
                                <hr style="border-color: var(--bg-element);">
                                <div class="d-flex gap-2 justify-content-end">
                                    <a href="users.php" class="btn btn-outline-secondary px-4">
                                        <i class="bi bi-x-lg"></i> Cancel
                                    </a>
                                    <button type="submit" class="btn btn-primary px-4">
                                        <i class="bi bi-person-plus"></i> Create User
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