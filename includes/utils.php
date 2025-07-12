<?php
require_once 'config.php';

class Utils {
    
    /**
     * Sanitize input data
     */
    public static function sanitize($data) {
        if (is_array($data)) {
            return array_map([self::class, 'sanitize'], $data);
        }
        return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Validate email format
     */
    public static function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    /**
     * Validate password strength
     */
    public static function validatePassword($password) {
        // At least 6 characters
        if (strlen($password) < 6) {
            return false;
        }
        return true;
    }
    
    /**
     * Generate random string
     */
    public static function generateRandomString($length = 10) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $string = '';
        for ($i = 0; $i < $length; $i++) {
            $string .= $characters[rand(0, strlen($characters) - 1)];
        }
        return $string;
    }
    
    /**
     * Format date for display
     */
    public static function formatDate($date, $format = 'M j, Y g:i A') {
        return date($format, strtotime($date));
    }
    
    /**
     * Get time ago string
     */
    public static function timeAgo($datetime) {
        $time = strtotime($datetime);
        $now = time();
        $diff = $now - $time;
        
        if ($diff < 60) {
            return 'Just now';
        } elseif ($diff < 3600) {
            $minutes = floor($diff / 60);
            return $minutes . ' minute' . ($minutes > 1 ? 's' : '') . ' ago';
        } elseif ($diff < 86400) {
            $hours = floor($diff / 3600);
            return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
        } elseif ($diff < 2592000) {
            $days = floor($diff / 86400);
            return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
        } else {
            return self::formatDate($datetime, 'M j, Y');
        }
    }
    
    /**
     * Validate file upload
     */
    public static function validateFile($file, $allowedTypes = [], $maxSize = 5242880) {
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            return ['valid' => false, 'message' => 'No file uploaded'];
        }
        
        if ($file['size'] > $maxSize) {
            return ['valid' => false, 'message' => 'File too large'];
        }
        
        if (!empty($allowedTypes)) {
            $fileInfo = pathinfo($file['name']);
            $extension = strtolower($fileInfo['extension']);
            if (!in_array($extension, $allowedTypes)) {
                return ['valid' => false, 'message' => 'Invalid file type'];
            }
        }
        
        return ['valid' => true];
    }
    
    /**
     * Generate pagination links
     */
    public static function generatePagination($currentPage, $totalPages, $baseUrl) {
        $pagination = [];
        
        if ($totalPages <= 1) {
            return $pagination;
        }
        
        // Previous page
        if ($currentPage > 1) {
            $pagination[] = [
                'page' => $currentPage - 1,
                'text' => 'Previous',
                'url' => $baseUrl . '?page=' . ($currentPage - 1),
                'active' => false
            ];
        }
        
        // Page numbers
        $start = max(1, $currentPage - 2);
        $end = min($totalPages, $currentPage + 2);
        
        for ($i = $start; $i <= $end; $i++) {
            $pagination[] = [
                'page' => $i,
                'text' => $i,
                'url' => $baseUrl . '?page=' . $i,
                'active' => ($i == $currentPage)
            ];
        }
        
        // Next page
        if ($currentPage < $totalPages) {
            $pagination[] = [
                'page' => $currentPage + 1,
                'text' => 'Next',
                'url' => $baseUrl . '?page=' . ($currentPage + 1),
                'active' => false
            ];
        }
        
        return $pagination;
    }
    
    /**
     * Get user rating display
     */
    public static function getRatingDisplay($rating) {
        $stars = '';
        for ($i = 1; $i <= 5; $i++) {
            if ($i <= $rating) {
                $stars .= '★';
            } else {
                $stars .= '☆';
            }
        }
        return $stars;
    }
    
    /**
     * Truncate text
     */
    public static function truncateText($text, $length = 100) {
        if (strlen($text) <= $length) {
            return $text;
        }
        return substr($text, 0, $length) . '...';
    }
    
    /**
     * Get skill categories
     */
    public static function getSkillCategories() {
        return [
            'Technology' => ['Programming', 'Web Development', 'Mobile Development', 'Data Science', 'AI/ML', 'Cybersecurity'],
            'Creative' => ['Graphic Design', 'Video Editing', 'Photography', 'Music', 'Writing', 'Art'],
            'Business' => ['Marketing', 'Sales', 'Finance', 'Management', 'Consulting', 'Entrepreneurship'],
            'Education' => ['Teaching', 'Tutoring', 'Curriculum Development', 'Language Learning', 'Research'],
            'Health' => ['Fitness Training', 'Nutrition', 'Mental Health', 'Yoga', 'Meditation'],
            'Crafts' => ['Cooking', 'Baking', 'Sewing', 'Woodworking', 'DIY Projects', 'Gardening'],
            'Other' => ['Language Exchange', 'Travel Planning', 'Event Planning', 'Pet Care', 'Home Improvement']
        ];
    }
    
    /**
     * Get availability options
     */
    public static function getAvailabilityOptions() {
        return [
            'Weekdays' => 'Available on weekdays',
            'Weekends' => 'Available on weekends',
            'Evenings' => 'Available in evenings',
            'Flexible' => 'Flexible schedule',
            'By Appointment' => 'By appointment only'
        ];
    }
    
    /**
     * Log activity
     */
    public static function logActivity($userId, $action, $details = '') {
        $logFile = 'logs/activity.log';
        $logDir = dirname($logFile);
        
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        $logEntry = date('Y-m-d H:i:s') . " | User: $userId | Action: $action | Details: $details\n";
        file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Send JSON response
     */
    public static function jsonResponse($data, $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit();
    }
    
    /**
     * Redirect with message
     */
    public static function redirect($url, $message = '', $type = 'success') {
        if ($message) {
            $_SESSION['message'] = $message;
            $_SESSION['message_type'] = $type;
        }
        header('Location: ' . $url);
        exit();
    }
    
    /**
     * Get message from session
     */
    public static function getMessage() {
        if (isset($_SESSION['message'])) {
            $message = $_SESSION['message'];
            $type = $_SESSION['message_type'] ?? 'success';
            unset($_SESSION['message'], $_SESSION['message_type']);
            return ['message' => $message, 'type' => $type];
        }
        return null;
    }
}
?> 