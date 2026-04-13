<?php
require_once 'Database.php';

class Trip {
    private $db;
    
    public function __construct() {
        $this->db = new Database();
    }
    
    public function create($user_id, $title, $destination, $location, $start_date, $end_date, $notes, $expenses, $image, $visibility) {
        try {
            $this->db->beginTransaction();
            
            $stmt = $this->db->prepare("INSERT INTO trips (user_id, title, destination, location, start_date, end_date, notes, expenses, image, visibility) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $success = $this->db->execute($stmt, [$user_id, $title, $destination, $location, $start_date, $end_date, $notes, $expenses, $image, $visibility]);
            
            if ($success) {
                $trip_id = $this->db->lastInsertId();
                $this->db->commit();
                return $trip_id;
            } else {
                $this->db->rollBack();
                return false;
            }
        } catch (Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }
    
    public function getById($trip_id, $user_id) {
        $stmt = $this->db->prepare("SELECT * FROM trips WHERE trip_id = ? AND user_id = ?");
        $this->db->execute($stmt, [$trip_id, $user_id]);
        return $this->db->fetch($stmt);
    }
    
    public function getAllByUserId($user_id) {
        $stmt = $this->db->prepare("SELECT * FROM trips WHERE user_id = ? ORDER BY created_at DESC");
        $this->db->execute($stmt, [$user_id]);
        return $this->db->fetchAll($stmt);
    }
    
    public function update($trip_id, $user_id, $title, $destination, $location, $start_date, $end_date, $notes, $expenses, $image, $visibility) {
        try {
            $this->db->beginTransaction();
            
            $stmt = $this->db->prepare("UPDATE trips SET title = ?, destination = ?, location = ?, start_date = ?, end_date = ?, notes = ?, expenses = ?, image = ?, visibility = ? WHERE trip_id = ? AND user_id = ?");
            $success = $this->db->execute($stmt, [$title, $destination, $location, $start_date, $end_date, $notes, $expenses, $image, $visibility, $trip_id, $user_id]);
            
            if ($success) {
                $this->db->commit();
                return true;
            } else {
                $this->db->rollBack();
                return false;
            }
        } catch (Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }
    
    public function delete($trip_id, $user_id) {
        try {
            $this->db->beginTransaction();
            
            // Get trip info to delete associated files
            $trip = $this->getById($trip_id, $user_id);
            if (!$trip) {
                $this->db->rollBack();
                return false;
            }
            
            // Get additional images to delete files
            $stmt = $this->db->prepare("SELECT image_filename FROM trip_images WHERE trip_id = ?");
            $this->db->execute($stmt, [$trip_id]);
            $additional_images = $this->db->fetchAll($stmt);
            
            // Delete the trip (cascade will handle trip_images)
            $stmt = $this->db->prepare("DELETE FROM trips WHERE trip_id = ? AND user_id = ?");
            $success = $this->db->execute($stmt, [$trip_id, $user_id]);
            
            if ($success) {
                // Delete main image file
                if (!empty($trip['image']) && file_exists('uploads/' . $trip['image'])) {
                    unlink('uploads/' . $trip['image']);
                }
                
                // Delete additional image files
                foreach ($additional_images as $image_data) {
                    if (file_exists('uploads/' . $image_data['image_filename'])) {
                        unlink('uploads/' . $image_data['image_filename']);
                    }
                }
                
                $this->db->commit();
                return true;
            } else {
                $this->db->rollBack();
                return false;
            }
        } catch (Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }
    
    public function addImage($trip_id, $image_filename) {
        $stmt = $this->db->prepare("INSERT INTO trip_images (trip_id, image_filename) VALUES (?, ?)");
        return $this->db->execute($stmt, [$trip_id, $image_filename]);
    }
    
    public function deleteImage($image_id, $trip_id) {
        $stmt = $this->db->prepare("DELETE FROM trip_images WHERE image_id = ? AND trip_id = ?");
        return $this->db->execute($stmt, [$image_id, $trip_id]);
    }
    
    public function getImages($trip_id) {
        $stmt = $this->db->prepare("SELECT image_id, image_filename FROM trip_images WHERE trip_id = ? ORDER BY upload_date ASC");
        $this->db->execute($stmt, [$trip_id]);
        return $this->db->fetchAll($stmt);
    }
    
    public function validateTripData($title, $start_date, $end_date, $expenses = null) {
        $errors = [];
        
        if (empty($title) || strlen($title) < 3) {
            $errors[] = "Title must be at least 3 characters long.";
        }
        if (empty($start_date) || empty($end_date)) {
            $errors[] = "Start and end dates are required.";
        }
        if ($expenses !== null && $expenses !== '' && is_numeric($expenses) && floatval($expenses) < 0) {
            $errors[] = "Expenses cannot be negative.";
        }
        
        return $errors;
    }
}
?> 