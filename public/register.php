<?php
session_start();
include '../include/koneksi.php';

// Redirect jika sudah login
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = mysqli_real_escape_string($conn, trim($_POST['username']));
    $email = mysqli_real_escape_string($conn, trim($_POST['email']));
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Validasi input
    if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = 'Semua field harus diisi!';
    } elseif (strlen($username) < 3) {
        $error = 'Username minimal 3 karakter!';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid!';
    } elseif (strlen($password) < 6) {
        $error = 'Password minimal 6 karakter!';
    } elseif ($password !== $confirm_password) {
        $error = 'Password tidak cocok!';
    } else {
        // Cek apakah username sudah ada
        $check_username = "SELECT id_user FROM users WHERE username = ?";
        $stmt = mysqli_prepare($conn, $check_username);
        mysqli_stmt_bind_param($stmt, "s", $username);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if (mysqli_num_rows($result) > 0) {
            $error = 'Username sudah digunakan!';
        } else {
            // Cek apakah email sudah ada
            $check_email = "SELECT id_user FROM users WHERE email = ?";
            $stmt = mysqli_prepare($conn, $check_email);
            mysqli_stmt_bind_param($stmt, "s", $email);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);

            if (mysqli_num_rows($result) > 0) {
                $error = 'Email sudah terdaftar!';
            } else {
                // Hash password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                // Insert user baru
                $insert_query = "INSERT INTO users (username, email, password) VALUES (?, ?, ?)";
                $stmt = mysqli_prepare($conn, $insert_query);
                mysqli_stmt_bind_param($stmt, "sss", $username, $email, $hashed_password);

                if (mysqli_stmt_execute($stmt)) {
                    $success = 'Registrasi berhasil! Silakan login.';
                    // Redirect ke login setelah 2 detik
                    header("refresh:2;url=login.php");
                } else {
                    $error = 'Terjadi kesalahan saat registrasi. Silakan coba lagi.';
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - HobbyJP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="asset/css/style.css">
    <style>
        .auth-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, var(--bg-primary) 0%, var(--bg-secondary) 100%);
            padding: 2rem 0;
        }

        .auth-card {
            background: var(--bg-card);
            border-radius: 16px;
            padding: 2.5rem;
            width: 100%;
            max-width: 450px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
        }

        .auth-logo {
            text-align: center;
            margin-bottom: 2rem;
        }

        .auth-logo i {
            font-size: 3rem;
            color: var(--accent-primary);
        }

        .auth-logo h2 {
            color: var(--text-primary);
            margin-top: 1rem;
            font-weight: 700;
        }

        .form-control {
            background: var(--bg-element);
            border: 1px solid var(--bg-element);
            color: var(--text-primary);
            padding: 0.75rem 1rem;
            border-radius: 8px;
        }

        .form-control:focus {
            background: var(--bg-element);
            border-color: var(--accent-primary);
            color: var(--text-primary);
            box-shadow: 0 0 0 0.25rem rgba(99, 102, 241, 0.15);
        }

        .form-label {
            color: var(--text-secondary);
            font-weight: 500;
            margin-bottom: 0.5rem;
        }

        .btn-register {
            width: 100%;
            padding: 0.75rem;
            font-weight: 600;
            border-radius: 8px;
            margin-top: 1rem;
        }

        .password-strength {
            height: 4px;
            border-radius: 2px;
            margin-top: 0.5rem;
            background: var(--bg-element);
            transition: all 0.3s;
        }

        .password-strength.weak {
            background: #ef4444;
            width: 33%;
        }

        .password-strength.medium {
            background: #f59e0b;
            width: 66%;
        }

        .password-strength.strong {
            background: #10b981;
            width: 100%;
        }

        .alert {
            border-radius: 8px;
            border: none;
        }
    </style>
</head>

<body>
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-logo">
                <i class="bi bi-book-half"></i>
                <h2>Join HobbyJP</h2>
                <p class="text-secondary mb-0">Buat akun dan mulai petualangan</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger d-flex align-items-center" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    <div><?php echo htmlspecialchars($error); ?></div>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success d-flex align-items-center" role="alert">
                    <i class="bi bi-check-circle-fill me-2"></i>
                    <div><?php echo htmlspecialchars($success); ?></div>
                </div>
            <?php endif; ?>

            <form method="POST" action="" id="registerForm">
                <div class="mb-3">
                    <label for="username" class="form-label">Username</label>
                    <input type="text" class="form-control" id="username" name="username"
                        placeholder="Masukkan username" required minlength="3"
                        value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                    <small class="text-secondary">Minimal 3 karakter</small>
                </div>

                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email" name="email"
                        placeholder="nama@example.com" required
                        value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <div class="input-group">
                        <input type="password" class="form-control" id="password" name="password"
                            placeholder="Masukkan password" required minlength="6">
                        <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                    <div class="password-strength" id="passwordStrength"></div>
                    <small class="text-secondary">Minimal 6 karakter</small>
                </div>

                <div class="mb-3">
                    <label for="confirm_password" class="form-label">Konfirmasi Password</label>
                    <div class="input-group">
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password"
                            placeholder="Masukkan ulang password" required minlength="6">
                        <button class="btn btn-outline-secondary" type="button" id="toggleConfirmPassword">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                </div>

                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" id="terms" required>
                    <label class="form-check-label text-secondary" for="terms">
                        Saya setuju dengan <a href="#" style="color: var(--accent-primary);">Terms & Conditions</a>
                    </label>
                </div>

                <button type="submit" class="btn btn-primary btn-register">
                    <i class="bi bi-person-plus me-2"></i>Daftar Sekarang
                </button>
            </form>

            <div class="text-center mt-4">
                <p class="text-secondary mb-0">
                    Sudah punya akun?
                    <a href="login.php" class="text-decoration-none fw-bold" style="color: var(--accent-primary);">
                        Login di sini
                    </a>
                </p>
            </div>

            <div class="text-center mt-3">
                <a href="index.php" class="text-secondary text-decoration-none small">
                    <i class="bi bi-arrow-left me-1"></i> Kembali ke beranda
                </a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle password visibility
        function togglePasswordVisibility(inputId, buttonId) {
            document.getElementById(buttonId).addEventListener('click', function() {
                const password = document.getElementById(inputId);
                const icon = this.querySelector('i');

                if (password.type === 'password') {
                    password.type = 'text';
                    icon.classList.remove('bi-eye');
                    icon.classList.add('bi-eye-slash');
                } else {
                    password.type = 'password';
                    icon.classList.remove('bi-eye-slash');
                    icon.classList.add('bi-eye');
                }
            });
        }

        togglePasswordVisibility('password', 'togglePassword');
        togglePasswordVisibility('confirm_password', 'toggleConfirmPassword');

        // Password strength indicator
        document.getElementById('password').addEventListener('input', function() {
            const password = this.value;
            const strength = document.getElementById('passwordStrength');

            if (password.length === 0) {
                strength.style.width = '0';
                strength.className = 'password-strength';
            } else if (password.length < 6) {
                strength.className = 'password-strength weak';
            } else if (password.length < 10) {
                strength.className = 'password-strength medium';
            } else {
                strength.className = 'password-strength strong';
            }
        });

        // Confirm password validation
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;

            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Password tidak cocok!');
            }
        });
    </script>
</body>

</html>