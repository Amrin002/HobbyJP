<?php
session_start();
header('Content-Type: application/json');

// Include koneksi
include '../../include/koneksi.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'You must be logged in to rate'
    ]);
    exit;
}

// Get JSON data
$json = file_get_contents('php://input');
$data = json_decode($json, true);

$id_content = isset($data['id_content']) ? intval($data['id_content']) : 0;
$rating = isset($data['rating']) ? intval($data['rating']) : 0;
$id_user = $_SESSION['user_id'];

// Validate input
if ($id_content == 0 || $rating < 1 || $rating > 5) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid input'
    ]);
    exit;
}

// Check if user already rated this content
$query_check = "SELECT id_rating FROM ratings WHERE id_user = ? AND id_content = ?";
$stmt_check = mysqli_prepare($conn, $query_check);
mysqli_stmt_bind_param($stmt_check, "ii", $id_user, $id_content);
mysqli_stmt_execute($stmt_check);
$result_check = mysqli_stmt_get_result($stmt_check);

if (mysqli_num_rows($result_check) > 0) {
    // Update existing rating
    $query_update = "UPDATE ratings SET rating = ?, created_at = NOW() WHERE id_user = ? AND id_content = ?";
    $stmt_update = mysqli_prepare($conn, $query_update);
    mysqli_stmt_bind_param($stmt_update, "iii", $rating, $id_user, $id_content);

    if (mysqli_stmt_execute($stmt_update)) {
        // Update content_stats average rating
        updateAverageRating($conn, $id_content);

        echo json_encode([
            'success' => true,
            'message' => 'Rating updated successfully',
            'rating' => $rating
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to update rating'
        ]);
    }
} else {
    // Insert new rating
    $query_insert = "INSERT INTO ratings (id_user, id_content, rating, created_at) VALUES (?, ?, ?, NOW())";
    $stmt_insert = mysqli_prepare($conn, $query_insert);
    mysqli_stmt_bind_param($stmt_insert, "iii", $id_user, $id_content, $rating);

    if (mysqli_stmt_execute($stmt_insert)) {
        // Update content_stats average rating
        updateAverageRating($conn, $id_content);

        echo json_encode([
            'success' => true,
            'message' => 'Rating submitted successfully',
            'rating' => $rating
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to submit rating'
        ]);
    }
}

// Function to update average rating
function updateAverageRating($conn, $id_content)
{
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
}
