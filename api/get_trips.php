<?php
session_start();
require_once '../config/db_connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'delete') {
    $trip_id = filter_input(INPUT_POST, 'trip_id', FILTER_SANITIZE_NUMBER_INT);
    
    try {
        $pdo->beginTransaction();
        
        // Get trip info to delete associated files
        $stmt = $pdo->prepare("SELECT image FROM trips WHERE trip_id = ? AND user_id = ?");
        $stmt->execute([$trip_id, $_SESSION['user_id']]);
        $trip = $stmt->fetch();
        
        if ($trip) {
            // Get additional images to delete files
            $stmt = $pdo->prepare("SELECT image_filename FROM trip_images WHERE trip_id = ?");
            $stmt->execute([$trip_id]);
            $additional_images = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            // Delete the trip (cascade will handle trip_images)
            $stmt = $pdo->prepare("DELETE FROM trips WHERE trip_id = ? AND user_id = ?");
            $success = $stmt->execute([$trip_id, $_SESSION['user_id']]);
            
            if ($success) {
                // Delete main image file
                if (!empty($trip['image']) && file_exists('uploads/' . $trip['image'])) {
                    unlink('uploads/' . $trip['image']);
                }
                
                // Delete additional image files
                foreach ($additional_images as $image_filename) {
                    if (file_exists('uploads/' . $image_filename)) {
                        unlink('uploads/' . $image_filename);
                    }
                }
                
                $pdo->commit();
                echo json_encode(['success' => true]);
            } else {
                $pdo->rollBack();
                echo json_encode(['success' => false]);
            }
        } else {
            $pdo->rollBack();
            echo json_encode(['success' => false]);
        }
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM trips WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$_SESSION['user_id']]);
$trips = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get additional images for each trip
foreach ($trips as &$trip) {
    $stmt = $pdo->prepare("SELECT image_filename FROM trip_images WHERE trip_id = ? ORDER BY upload_date ASC");
    $stmt->execute([$trip['trip_id']]);
    $additional_images = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $trip['additional_images'] = $additional_images;
}

echo json_encode($trips);
?>