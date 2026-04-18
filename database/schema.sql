-- Database: electric_management_system

-- Table: users
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'staff', 'customer') NOT NULL,
    consumer_id INT DEFAULT NULL,
    FOREIGN KEY (consumer_id) REFERENCES consumers(id) ON DELETE SET NULL
);

-- Table: categories (for consumer types and their rates)
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    rate_per_kwh DECIMAL(10, 4) NOT NULL
);

-- Table: consumers
CREATE TABLE consumers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    address VARCHAR(255) NOT NULL,
    contact VARCHAR(20),
    meter_no VARCHAR(50) NOT NULL UNIQUE,
    category_id INT,
    FOREIGN KEY (category_id) REFERENCES categories(id)
);

-- Table: readings
CREATE TABLE readings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    consumer_id INT NOT NULL,
    prev_reading DECIMAL(10, 2) NOT NULL,
    curr_reading DECIMAL(10, 2) NOT NULL,
    reading_date DATE NOT NULL,
    FOREIGN KEY (consumer_id) REFERENCES consumers(id)
);

-- Table: bills
CREATE TABLE bills (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reading_id INT NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    due_date DATE NOT NULL,
    status ENUM('paid', 'unpaid') DEFAULT 'unpaid',
    FOREIGN KEY (reading_id) REFERENCES readings(id)
);

-- Table: payments
CREATE TABLE payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    bill_id INT NOT NULL,
    amount_paid DECIMAL(10, 2) NOT NULL,
    payment_date DATE NOT NULL,
    FOREIGN KEY (bill_id) REFERENCES bills(id)
);

-- Sample Data

-- Insert default categories
INSERT INTO categories (name, rate_per_kwh) VALUES ('Residential', 0.1200);
INSERT INTO categories (name, rate_per_kwh) VALUES ('Commercial', 0.1800);

-- Insert a default admin user (password: admin123)
-- The password hash is generated using password_hash('admin123', PASSWORD_DEFAULT)
INSERT INTO users (username, password, role) VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'); -- password: password

-- Insert sample consumers
INSERT INTO consumers (name, address, contact, meter_no, category_id) VALUES ('John Doe', '123 Main St', '555-0101', 'MTR-001', 1);
INSERT INTO consumers (name, address, contact, meter_no, category_id) VALUES ('Jane Smith', '456 Oak Ave', '555-0102', 'MTR-002', 2);
