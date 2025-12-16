<?php
session_start();
header('Content-Type: application/json');

// Include koneksi
include '../../include/koneksi.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'You must be logged in to bookmark'
    ]);
    exit;
}

// Get JSON data
$json = file_get_contents('php://input');
$data = json_decode($json, true);

$id_content = isset($data['id_content']) ? intval($data['id_content']) : 0;
$id_user = $_SESSION['user_id'];

if ($id_content == 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid input'
    ]);
    exit;
}

// Check if already bookmarked
$query_check = "SELECT id_bookmark FROM bookmarks WHERE id_user = ? AND id_content = ?";
$stmt_check = mysqli_prepare($conn, $query_check);
mysqli_stmt_bind_param($stmt_check, "ii", $id_user, $id_content);
mysqli_stmt_execute($stmt_check);
$result_check = mysqli_stmt_get_result($stmt_check);

if (mysqli_num_rows($result_check) > 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Already bookmarked'
    ]);
    exit;
}

// Insert bookmark
$query_insert = "INSERT INTO bookmarks (id_user, id_content, created_at) VALUES (?, ?, NOW())";
$stmt_insert = mysqli_prepare($conn, $query_insert);
mysqli_stmt_bind_param($stmt_insert, "ii", $id_user, $id_content);

if (mysqli_stmt_execute($stmt_insert)) {
    // Recalculate total_bookmark to keep consistency
    $query_update = "UPDATE content_stats SET total_bookmark = (
        SELECT COUNT(*) FROM bookmarks WHERE id_content = ?
    ) WHERE id_content = ?";
    $stmt_update = mysqli_prepare($conn, $query_update);
    mysqli_stmt_bind_param($stmt_update, "ii", $id_content, $id_content);
    mysqli_stmt_execute($stmt_update);

    // Get updated count
    $query_count = "SELECT total_bookmark FROM content_stats WHERE id_content = ?";
    $stmt_count = mysqli_prepare($conn, $query_count);
    mysqli_stmt_bind_param($stmt_count, "i", $id_content);
    mysqli_stmt_execute($stmt_count);
    $res_count = mysqli_stmt_get_result($stmt_count);
    $row = mysqli_fetch_assoc($res_count);

    echo json_encode([
        'success' => true,
        'message' => 'Bookmarked successfully',
        'total_bookmark' => isset($row['total_bookmark']) ? intval($row['total_bookmark']) : 0
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to add bookmark'
    ]);
}
