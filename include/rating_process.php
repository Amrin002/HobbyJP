<?php
// include/rating_process.php
error_reporting(E_ALL);
ini_set('display_errors', 0);

include 'koneksi.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

// Function untuk logging (opsional, bisa dimatikan di production)
function debug_log($message)
{
    $log_file = __DIR__ . '/debug.log';
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($log_file, "[$timestamp] $message\n", FILE_APPEND);
}

try {
    debug_log("debug logging");
    debug_log("POST: " . json_encode($_POST));
    debug_log("SESSION: " . json_encode($_SESSION));

    // Cek apakah user sudah login
    if (!isset($_SESSION['user_id'])) {
        debug_log("ERROR: User not logged in");
        echo json_encode([
            'success' => false,
            'message' => 'Anda harus login terlebih dahulu'
        ]);
        exit;
    }

    // Cek apakah request method POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        debug_log("ERROR: Invalid request method");
        echo json_encode([
            'success' => false,
            'message' => 'Invalid request method'
        ]);
        exit;
    }

    $id_user = $_SESSION['user_id'];
    $id_content = isset($_POST['id_content']) ? intval($_POST['id_content']) : 0;
    $rating = isset($_POST['rating']) ? intval($_POST['rating']) : 0;

    debug_log("Parsed data - User: $id_user, Content: $id_content, Rating: $rating");

    // Validasi input
    if ($id_content <= 0 || $rating < 1 || $rating > 5) {
        debug_log("ERROR: Invalid input data");
        echo json_encode([
            'success' => false,
            'message' => 'Data tidak valid (Content: ' . $id_content . ', Rating: ' . $rating . ')'
        ]);
        exit;
    }

    // Cek koneksi database
    if (!$conn) {
        debug_log("ERROR: Database connection failed - " . mysqli_connect_error());
        echo json_encode([
            'success' => false,
            'message' => 'Koneksi database gagal'
        ]);
        exit;
    }

    // Cek apakah content exists
    $check_content = "SELECT id_content FROM contents WHERE id_content = ?";
    $stmt_check = mysqli_prepare($conn, $check_content);

    if (!$stmt_check) {
        debug_log("ERROR: Prepare check_content failed - " . mysqli_error($conn));
        echo json_encode([
            'success' => false,
            'message' => 'Database error pada check content'
        ]);
        exit;
    }

    mysqli_stmt_bind_param($stmt_check, "i", $id_content);
    mysqli_stmt_execute($stmt_check);
    $result_check = mysqli_stmt_get_result($stmt_check);

    if (mysqli_num_rows($result_check) == 0) {
        debug_log("ERROR: Content not found - ID: $id_content");
        echo json_encode([
            'success' => false,
            'message' => 'Content tidak ditemukan'
        ]);
        mysqli_stmt_close($stmt_check);
        exit;
    }
    mysqli_stmt_close($stmt_check);

    debug_log("Content found, proceeding to insert/update rating");

    // Insert atau update rating menggunakan ON DUPLICATE KEY UPDATE
    $query = "INSERT INTO ratings (id_user, id_content, rating, created_at) 
              VALUES (?, ?, ?, NOW()) 
              ON DUPLICATE KEY UPDATE rating = ?, created_at = NOW()";

    $stmt = mysqli_prepare($conn, $query);

    if (!$stmt) {
        debug_log("ERROR: Prepare rating query failed - " . mysqli_error($conn));
        echo json_encode([
            'success' => false,
            'message' => 'Database error: ' . mysqli_error($conn)
        ]);
        exit;
    }

    mysqli_stmt_bind_param($stmt, "iiii", $id_user, $id_content, $rating, $rating);

    if (!mysqli_stmt_execute($stmt)) {
        $error = mysqli_stmt_error($stmt);
        debug_log("ERROR: Execute rating query failed - $error");
        echo json_encode([
            'success' => false,
            'message' => 'Gagal menyimpan rating: ' . $error
        ]);
        mysqli_stmt_close($stmt);
        exit;
    }

    debug_log("Rating saved successfully");
    mysqli_stmt_close($stmt);

    // Update average rating di content_stats
    $update_stats = "UPDATE content_stats cs
                     SET cs.average_rating = (
                         SELECT COALESCE(AVG(r.rating), 0)
                         FROM ratings r
                         WHERE r.id_content = ?
                     )
                     WHERE cs.id_content = ?";

    $stmt_update = mysqli_prepare($conn, $update_stats);

    if (!$stmt_update) {
        debug_log("WARNING: Prepare update_stats failed - " . mysqli_error($conn));
        // Tetap return success karena rating sudah tersimpan
        echo json_encode([
            'success' => true,
            'message' => 'Rating tersimpan tapi gagal update stats',
            'average_rating' => number_format($rating, 1)
        ]);
        exit;
    }

    mysqli_stmt_bind_param($stmt_update, "ii", $id_content, $id_content);
    mysqli_stmt_execute($stmt_update);
    mysqli_stmt_close($stmt_update);

    debug_log("Stats updated successfully");

    // Get new average rating
    $get_avg = "SELECT average_rating FROM content_stats WHERE id_content = ?";
    $stmt_avg = mysqli_prepare($conn, $get_avg);

    if ($stmt_avg) {
        mysqli_stmt_bind_param($stmt_avg, "i", $id_content);
        mysqli_stmt_execute($stmt_avg);
        $result_avg = mysqli_stmt_get_result($stmt_avg);
        $avg_data = mysqli_fetch_assoc($result_avg);
        mysqli_stmt_close($stmt_avg);

        $new_avg = $avg_data ? $avg_data['average_rating'] : $rating;
    } else {
        $new_avg = $rating;
    }

    debug_log("New average rating: $new_avg");
    debug_log("=== Request completed successfully ===\n");

    echo json_encode([
        'success' => true,
        'message' => 'Rating berhasil disimpan',
        'average_rating' => number_format($new_avg, 1)
    ]);
} catch (Exception $e) {
    debug_log("EXCEPTION: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Terjadi kesalahan: ' . $e->getMessage()
    ]);
} finally {
    if (isset($conn)) {
        mysqli_close($conn);
    }
}
