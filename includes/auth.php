<?php
require_once 'config.php';
require_once 'database.php';

session_start();

class Auth {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function isLoggedIn() {
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }
    
    public function requireLogin() {
        if (!$this->isLoggedIn()) {
            header('Location: login.php');
            exit();
        }
    }
    
    public function requireAdmin() {
        $this->requireLogin();
        if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
            header('Location: dashboard.php');
            exit();
        }
    }
    
    public function login($email, $password) {
        $user = $this->db->fetchOne(
            "SELECT * FROM users WHERE email = ?",
            [$email]
        );
        
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];
            
            // Update last login
            $this->db->update('users', 
                ['last_login' => date('Y-m-d H:i:s')], 
                'id = ?', 
                [$user['id']]
            );
            
            return true;
        }
        
        return false;
    }
    
    public function register($name, $email, $password, $location = '') {
        // Check if email already exists
        $existing = $this->db->fetchOne(
            "SELECT id FROM users WHERE email = ?",
            [$email]
        );
        
        if ($existing) {
            return ['success' => false, 'message' => 'Email already registered'];
        }
        
        // Hash password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        // Insert new user
        $userId = $this->db->insert('users', [
            'name' => $name,
            'email' => $email,
            'password' => $hashedPassword,
            'location' => $location,
            'created_at' => date('Y-m-d H:i:s')
        ]);
        
        if ($userId) {
            return ['success' => true, 'user_id' => $userId];
        }
        
        return ['success' => false, 'message' => 'Registration failed'];
    }
    
    public function logout() {
        session_destroy();
        header('Location: login.php');
        exit();
    }
    
    public function getCurrentUser() {
        if (!$this->isLoggedIn()) {
            return null;
        }
        
        return $this->db->fetchOne(
            "SELECT * FROM users WHERE id = ?",
            [$_SESSION['user_id']]
        );
    }
    
    public function updateProfile($userId, $data) {
        $allowedFields = ['name', 'location', 'skills_offered', 'skills_wanted', 'availability', 'privacy'];
        $updateData = array_intersect_key($data, array_flip($allowedFields));
        
        if (empty($updateData)) {
            return false;
        }
        
        return $this->db->update('users', $updateData, 'id = ?', [$userId]);
    }
    
    public function changePassword($userId, $currentPassword, $newPassword) {
        $user = $this->db->fetchOne(
            "SELECT password FROM users WHERE id = ?",
            [$userId]
        );
        
        if (!$user || !password_verify($currentPassword, $user['password'])) {
            return false;
        }
        
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        return $this->db->update('users', ['password' => $hashedPassword], 'id = ?', [$userId]);
    }
    
    public function uploadProfilePhoto($userId, $file) {
        $uploadDir = UPLOAD_PATH;
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $fileInfo = pathinfo($file['name']);
        $extension = strtolower($fileInfo['extension']);
        
        if (!in_array($extension, ALLOWED_IMAGE_TYPES)) {
            return ['success' => false, 'message' => 'Invalid file type'];
        }
        
        if ($file['size'] > MAX_FILE_SIZE) {
            return ['success' => false, 'message' => 'File too large'];
        }
        
        $filename = 'profile_' . $userId . '_' . time() . '.' . $extension;
        $filepath = $uploadDir . $filename;
        
        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            $this->db->update('users', ['profile_photo' => $filename], 'id = ?', [$userId]);
            return ['success' => true, 'filename' => $filename];
        }
        
        return ['success' => false, 'message' => 'Upload failed'];
    }
}

// Global auth instance
$auth = new Auth();

// Redirect if not logged in (for protected pages)
if (basename($_SERVER['PHP_SELF']) !== 'login.php' && 
    basename($_SERVER['PHP_SELF']) !== 'register.php' && 
    basename($_SERVER['PHP_SELF']) !== 'index.php' &&
    !$auth->isLoggedIn()) {
    header('Location: login.php');
    exit();
}
?>