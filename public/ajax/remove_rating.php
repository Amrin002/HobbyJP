<?php
session_start();
header('Content-Type: application/json');

// Include koneksi
include '../../include/koneksi.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'You must be logged in'
    ]);
    exit;
}

// Get JSON data
$json = file_get_contents('php://input');
$data = json_decode($json, true);

$id_content = isset($data['id_content']) ? intval($data['id_content']) : 0;
$id_user = $_SESSION['user_id'];

// Validate input
if ($id_content == 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid input'
    ]);
    exit;
}

// Delete rating
$query_delete = "DELETE FROM ratings WHERE id_user = ? AND id_content = ?";
$stmt_delete = mysqli_prepare($conn, $query_delete);
mysqli_stmt_bind_param($stmt_delete, "ii", $id_user, $id_content);

if (mysqli_stmt_execute($stmt_delete)) {
    // Update content_stats average rating
    $query_avg = "UPDATE content_stats 
                  SET average_rating = (
                      SELECT COALESCE(AVG(rating), 0) 
                      FROM ratings 
                      WHERE id_content = ?
                  ) 
                  WHERE id_content = ?";
    $stmt_avg = mysqli_prepare($conn, $query_avg);
    mysqli_stmt_bind_param($stmt_avg, "ii", $id_content, $id_content);
    mysqli_stmt_execute($stmt_avg);

    echo json_encode([
        'success' => true,
        'message' => 'Rating removed successfully'
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to remove rating'
    ]);
}
