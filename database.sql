CREATE DATABASE IF NOT EXISTS skillswap;
USE skillswap;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    location VARCHAR(100),
    profile_photo VARCHAR(255),
    skills_offered TEXT,
    skills_wanted TEXT,
    availability VARCHAR(100),
    privacy ENUM('public', 'private') DEFAULT 'public',
    role ENUM('user', 'admin') DEFAULT 'user',
    status ENUM('active', 'suspended') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE swaps (
    id INT AUTO_INCREMENT PRIMARY KEY,
    requester_id INT NOT NULL,
    receiver_id INT NOT NULL,
    skill_requested VARCHAR(100) NOT NULL,
    skill_offered VARCHAR(100) NOT NULL,
    status ENUM('pending', 'accepted', 'rejected', 'completed') DEFAULT 'pending',
    message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (requester_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_requester (requester_id),
    INDEX idx_receiver (receiver_id),
    INDEX idx_status (status),
    INDEX idx_created (created_at)
);

CREATE TABLE feedback (
    id INT AUTO_INCREMENT PRIMARY KEY,
    swap_id INT NOT NULL,
    from_user INT NOT NULL,
    to_user INT NOT NULL,
    rating INT NOT NULL CHECK (rating BETWEEN 1 AND 5),
    comments TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (swap_id) REFERENCES swaps(id) ON DELETE CASCADE,
    FOREIGN KEY (from_user) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (to_user) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_feedback (swap_id, from_user, to_user),
    INDEX idx_swap (swap_id),
    INDEX idx_from_user (from_user),
    INDEX idx_to_user (to_user),
    INDEX idx_rating (rating)
);

CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type VARCHAR(50) NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    data JSON,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_type (type),
    INDEX idx_read (is_read),
    INDEX idx_created (created_at)
);

-- Insert default admin user (password: admin123)
INSERT INTO users (name, email, password, role, created_at) VALUES 
('Admin User', 'admin@skillswap.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', NOW());

-- Create indexes for better performance
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_users_role ON users(role);
CREATE INDEX idx_users_status ON users(status);
CREATE INDEX idx_swaps_status_created ON swaps(status, created_at);
CREATE INDEX idx_feedback_rating ON feedback(rating);
CREATE INDEX idx_notifications_user_read ON notifications(user_id, is_read);