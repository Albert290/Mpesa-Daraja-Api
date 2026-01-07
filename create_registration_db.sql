-- Add registration table to existing database
USE mpesa_transactions;

CREATE TABLE registrations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id VARCHAR(50) UNIQUE NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    phone VARCHAR(15) NOT NULL,
    gender ENUM('Male', 'Female', 'Prefer not to say') NOT NULL,
    race_category ENUM('10 km', '21 km', '32 km', '42 km') NOT NULL,
    first_time ENUM('Yes', 'No') NOT NULL,
    emergency_name VARCHAR(100) NOT NULL,
    emergency_phone VARCHAR(15) NOT NULL,
    relationship ENUM('Parent', 'Partner', 'Friend') NOT NULL,
    medical_conditions TEXT,
    allergies TEXT,
    finisher_medal ENUM('Yes', 'No') DEFAULT 'No',
    transport ENUM('Nairobi group transport', 'Self-arranged transport') NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    payment_status ENUM('pending', 'paid', 'failed') DEFAULT 'pending',
    checkout_request_id VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
