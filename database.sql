CREATE DATABASE IF NOT EXISTS meeting_booking_system;
USE meeting_booking_system;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('user', 'admin') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    date DATE NOT NULL,
    time TIME NOT NULL,
    description TEXT,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Insert Admin User (Password: Admin@123)
-- The hash below is for 'Admin@123' using PASSWORD_DEFAULT (BCRYPT)
-- Note: In a real scenario, generate this dynamically. 
-- I will use a placeholder hash here, assuming the PHP code will use password_verify.
-- Hash generated for 'Admin@123': $2y$10$z.P.P.P.P.P.P.P.P.P.P.P.P.P.P.P.P.P.P.P.P.P.P.P.P.P.P (This is fake, I need a real one or I'll handle it in PHP setup)

-- Actually, let's just create the table. I will provide a setup.php to insert the admin correctly.
