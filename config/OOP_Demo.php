<?php
session_start();
require_once 'User.php';
require_once 'Trip.php';

// Example of using OOP classes alongside existing functionality
class OOPDemo {
    private $user;
    private $trip;
    
    public function __construct() {
        $this->user = new User();
        $this->trip = new Trip();
    }
    
    // Example: Enhanced login with OOP
    public function enhancedLogin($email, $password) {
        if ($this->user->authenticate($email, $password)) {
            // Get user data from the User object
            $user_id = $this->user->getCurrentUserId();
            $username = $this->user->getCurrentUsername();
            
            // Use the OOP login method
            $this->user->login($user_id, $username);
            return true;
        }
        return false;
    }
    
    // Example: Enhanced registration with OOP
    public function enhancedRegister($username, $email, $password) {
        // Use OOP validation methods
        if (!$this->user->validateUsername($username)) {
            return ['success' => false, 'error' => 'Username must be at least 3 characters long.'];
        }
        
        if (!$this->user->validateEmail($email)) {
            return ['success' => false, 'error' => 'Invalid email format.'];
        }
        
        if (!$this->user->validatePassword($password)) {
            return ['success' => false, 'error' => 'Password must be at least 6 characters long.'];
        }
        
        // Use OOP registration method
        if ($this->user->register($username, $email, $password)) {
            return ['success' => true, 'message' => 'Registration successful!'];
        } else {
            return ['success' => false, 'error' => 'Username or email already exists.'];
        }
    }
    
    // Example: Enhanced trip creation with OOP
    public function enhancedCreateTrip($user_id, $trip_data) {
        // Use OOP validation
        $validation_errors = $this->trip->validateTripData(
            $trip_data['title'], 
            $trip_data['start_date'], 
            $trip_data['end_date'], 
            $trip_data['expenses'] ?? null
        );
        
        if (!empty($validation_errors)) {
            return ['success' => false, 'errors' => $validation_errors];
        }
        
        // Use OOP trip creation
        $trip_id = $this->trip->create(
            $user_id,
            $trip_data['title'],
            $trip_data['destination'] ?? '',
            $trip_data['location'] ?? '',
            $trip_data['start_date'],
            $trip_data['end_date'],
            $trip_data['notes'] ?? '',
            $trip_data['expenses'] ?? null,
            $trip_data['image'] ?? null,
            $trip_data['visibility'] ?? 'private'
        );
        
        if ($trip_id) {
            return ['success' => true, 'trip_id' => $trip_id];
        } else {
            return ['success' => false, 'error' => 'Failed to create trip.'];
        }
    }
    
    // Example: Enhanced trip retrieval with OOP
    public function enhancedGetUserTrips($user_id) {
        return $this->trip->getAllByUserId($user_id);
    }
    
    // Example: Enhanced trip deletion with OOP
    public function enhancedDeleteTrip($trip_id, $user_id) {
        return $this->trip->delete($trip_id, $user_id);
    }
    
    // Example: Check if user is logged in using OOP
    public function isUserLoggedIn() {
        return $this->user->isLoggedIn();
    }
    
    // Example: Get current user info using OOP
    public function getCurrentUserInfo() {
        if ($this->user->isLoggedIn()) {
            return [
                'user_id' => $this->user->getCurrentUserId(),
                'username' => $this->user->getCurrentUsername()
            ];
        }
        return null;
    }
}

// Usage example (this would be used in your existing files):
/*
// In login.php, you could add:
$demo = new OOPDemo();
if ($demo->enhancedLogin($email, $password)) {
    header("Location: dashboard.php");
    exit;
}

// In add_trip.php, you could add:
$demo = new OOPDemo();
$result = $demo->enhancedCreateTrip($_SESSION['user_id'], [
    'title' => $title,
    'destination' => $destination,
    'location' => $location,
    'start_date' => $start_date,
    'end_date' => $end_date,
    'notes' => $notes,
    'expenses' => $expenses,
    'image' => $image,
    'visibility' => $visibility
]);

if ($result['success']) {
    header("Location: dashboard.php");
    exit;
} else {
    $errors = $result['errors'];
}
*/
?> 