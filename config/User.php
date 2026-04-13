<?php
require_once 'Database.php';

class User {
    private $db;
    private $user_id;
    private $username;
    private $email;
    
    public function __construct() {
        $this->db = new Database();
    }
    
    public function authenticate($email, $password) {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE email = ?");
        $this->db->execute($stmt, [$email]);
        $user = $this->db->fetch($stmt);
        
        if ($user && password_verify($password, $user['password'])) {
            $this->user_id = $user['user_id'];
            $this->username = $user['username'];
            $this->email = $user['email'];
            return true;
        }
        return false;
    }
    
    public function register($username, $email, $password) {
        // Check if user already exists
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM users WHERE username = ? OR email = ?");
        $this->db->execute($stmt, [$username, $email]);
        if ($stmt->fetchColumn() > 0) {
            return false; // User already exists
        }
        
        // Hash password and insert new user
        $hashed_password = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $this->db->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
        return $this->db->execute($stmt, [$username, $email, $hashed_password]);
    }
    
    public function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }
    
    public function getCurrentUserId() {
        return $_SESSION['user_id'] ?? null;
    }
    
    public function getCurrentUsername() {
        return $_SESSION['username'] ?? null;
    }
    
    public function login($user_id, $username) {
        $_SESSION['user_id'] = $user_id;
        $_SESSION['username'] = $username;
        setcookie('user_id', $user_id, time() + (86400 * 30), "/"); // 30-day cookie
    }
    
    public function logout() {
        session_destroy();
        setcookie('user_id', '', time() - 3600, "/");
    }
    
    public function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL);
    }
    
    public function validatePassword($password) {
        return strlen($password) >= 6;
    }
    
    public function validateUsername($username) {
        return strlen($username) >= 3;
    }
}
?> 