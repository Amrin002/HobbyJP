<?php
session_start();
include '../../include/koneksi.php'; // Path naik satu level ke folder include

// 1. Cek Session: Jika sudah login
if (isset($_SESSION['user_id'])) {
    // Jika dia admin, lempar ke dashboard admin
    if (isset($_SESSION['role']) && $_SESSION['role'] == 'admin') {
        header('Location: index.php');
        exit;
    }
    // Jika dia user biasa, lempar ke homepage utama (keluar dari folder admin)
    else {
        header('Location: ../index.php');
        exit;
    }
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = mysqli_real_escape_string($conn, trim($_POST['email']));
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $error = 'Email dan password harus diisi!';
    } else {
        // Query cari user berdasarkan email
        $query = "SELECT * FROM users WHERE email = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if ($user = mysqli_fetch_assoc($result)) {
            // Verifikasi Password
            if (password_verify($password, $user['password'])) {

                // --- 2. LOGIKA UTAMA: CEK ROLE ADMIN ---
                if ($user['role'] === 'admin') {
                    // Jika Admin, set session
                    session_regenerate_id(true); // Security: Mencegah session fixation
                    $_SESSION['user_id'] = $user['id_user'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['role'] = $user['role']; // Penting: simpan role
                    $_SESSION['login_time'] = time();

                    // Redirect ke Dashboard Admin
                    header('Location: index.php');
                    exit;
                } else {
                    // Jika User biasa mencoba login di halaman Admin
                    $error = 'Akun Anda tidak memiliki akses Admin!';
                }
            } else {
                $error = 'Email atau password salah!';
            }
        } else {
            $error = 'Email atau password salah!';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - HobbyJP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../css/style.css">

    <style>
        /* Override sedikit style untuk membedakan halaman admin */
        .auth-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            /* Gradient lebih gelap untuk nuansa admin */
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
        }

        .auth-card {
            background: var(--bg-card);
            border: 1px solid var(--bg-element);
            border-radius: 16px;
            padding: 2.5rem;
            width: 100%;
            max-width: 400px;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.5);
        }

        .auth-logo i {
            font-size: 3rem;
            color: var(--accent-primary);
        }

        .form-control {
            background-color: var(--bg-body);
            border-color: var(--bg-element);
            color: var(--text-primary);
        }

        .form-control:focus {
            background-color: var(--bg-body);
            color: var(--text-primary);
        }
    </style>
</head>

<body>
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-logo text-center mb-4">
                <i class="bi bi-shield-lock-fill"></i>
                <h3 class="text-white mt-3 fw-bold">Admin Console</h3>
                <p class="text-secondary small">Masuk untuk mengelola konten</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger d-flex align-items-center small py-2" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    <div><?php echo htmlspecialchars($error); ?></div>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="mb-3">
                    <label for="email" class="form-label text-secondary small">Email Administrator</label>
                    <div class="input-group">
                        <span class="input-group-text bg-dark border-secondary text-secondary"><i class="bi bi-envelope"></i></span>
                        <input type="email" class="form-control" id="email" name="email"
                            placeholder="admin@hobbyjp.com" required
                            value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                    </div>
                </div>

                <div class="mb-4">
                    <label for="password" class="form-label text-secondary small">Password</label>
                    <div class="input-group">
                        <span class="input-group-text bg-dark border-secondary text-secondary"><i class="bi bi-key"></i></span>
                        <input type="password" class="form-control" id="password" name="password"
                            placeholder="••••••••" required>
                        <button class="btn btn-outline-secondary border-secondary" type="button" id="togglePassword">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary w-100 py-2 fw-bold">
                    Login Dashboard
                </button>
            </form>

            <div class="text-center mt-4 pt-3 border-top border-secondary border-opacity-25">
                <a href="../index.php" class="text-secondary text-decoration-none small">
                    <i class="bi bi-arrow-left me-1"></i> Kembali ke Website Utama
                </a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Script show/hide password
        document.getElementById('togglePassword').addEventListener('click', function() {
            const password = document.getElementById('password');
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
    </script>
</body>

</html>