<?php
// Database connection credentials
$servername = "localhost";
$username = "root"; 
$password = "";    
$dbname = "evolve1";

// Create a connection with error handling using try-catch
try {
    $conn = new mysqli($servername, $username, $password);
    
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    // Create database with proper escaping
    $dbname = $conn->real_escape_string($dbname);
    $sql = "CREATE DATABASE IF NOT EXISTS `$dbname`";
    
    if (!$conn->query($sql)) {
        throw new Exception("Error creating database: " . $conn->error);
    }
    echo "Database created successfully\n";

    // Select the created database
    if (!$conn->select_db($dbname)) {
        throw new Exception("Error selecting database: " . $conn->error);
    }

    // SQL to create tbl_users table
    $tableSql = "CREATE TABLE IF NOT EXISTS tbl_users (
        user_id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(255) UNIQUE NOT NULL,
        passwordhash VARCHAR(255) NOT NULL,
        name VARCHAR(100) NOT NULL,
        username VARCHAR(100) UNIQUE NOT NULL,
        phone_number VARCHAR(20),
        profile_picture VARCHAR(255),
        verification_token VARCHAR(64) NULL,
        token_expiry DATETIME NULL,
        is_admin BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        status ENUM('pending', 'active', 'inactive') DEFAULT 'pending',
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_email (email),
        INDEX idx_username (username)
    )";

    if ($conn->query($tableSql) === TRUE) {
        echo "Table tbl_users created successfully\n";
        
        // Create station_owner_requests table
        $ownerRequestsSql = "CREATE TABLE IF NOT EXISTS station_owner_requests (
            request_id INT PRIMARY KEY AUTO_INCREMENT,
            user_id INT NOT NULL,
            owner_name VARCHAR(100) NOT NULL,
            business_name VARCHAR(100),
            email VARCHAR(100) NOT NULL,
            phone VARCHAR(20) NOT NULL,
            address TEXT NOT NULL,
            city VARCHAR(50) NOT NULL,
            state VARCHAR(50) NOT NULL,
            postal_code VARCHAR(10) NOT NULL,
            business_registration VARCHAR(50),
            password_hash VARCHAR(255) NOT NULL,
            status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES tbl_users(user_id)
        )";

        if ($conn->query($ownerRequestsSql) === TRUE) {
            echo "Table station_owner_requests created successfully\n";
            
            // Create charging_stations table
            $stationsSql = "CREATE TABLE IF NOT EXISTS charging_stations (
                station_id INT AUTO_INCREMENT PRIMARY KEY,
                owner_name VARCHAR(100) NOT NULL,
                name VARCHAR(100) NOT NULL,
                location POINT NOT NULL,
                address VARCHAR(255) NOT NULL,
                operator_id INT,
                owner_request_id INT,
                price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                charger_types JSON NOT NULL,
                total_slots INT NOT NULL,
                available_slots INT NOT NULL DEFAULT 0,
                status ENUM('active', 'inactive', 'maintenance') DEFAULT 'active',
                image VARCHAR(255),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (operator_id) REFERENCES tbl_users(user_id),
                FOREIGN KEY (owner_request_id) REFERENCES station_owner_requests(request_id),
                SPATIAL INDEX(location)
            )";

            if ($conn->query($stationsSql) === TRUE) {
                echo "Table charging_stations created successfully\n";
                
                // Add to the existing bookings table creation SQL
                $bookingsSql = "CREATE TABLE IF NOT EXISTS bookings (
                    booking_id INT PRIMARY KEY AUTO_INCREMENT,
                    user_id INT NOT NULL,
                    station_id INT NOT NULL,
                    booking_date DATE NOT NULL,
                    booking_time TIME NOT NULL,
                    duration INT NOT NULL,
                    amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                    payment_status ENUM('pending', 'completed', 'failed') DEFAULT 'pending',
                    razorpay_order_id VARCHAR(255),
                    razorpay_payment_id VARCHAR(255),
                    status ENUM('pending', 'confirmed', 'cancelled', 'in_progress', 'completed') NOT NULL DEFAULT 'pending',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    FOREIGN KEY (user_id) REFERENCES tbl_users(user_id),
                    FOREIGN KEY (station_id) REFERENCES charging_stations(station_id),
                    INDEX idx_user_booking (user_id, booking_date),
                    INDEX idx_station_booking (station_id, booking_date),
                    INDEX idx_razorpay_order (razorpay_order_id)
                )";

                if ($conn->query($bookingsSql) === TRUE) {
                    echo "Table bookings created successfully\n";
                    
                    // Create booking_logs table
                    $bookingLogsSql = "CREATE TABLE IF NOT EXISTS booking_logs (
                        log_id INT PRIMARY KEY AUTO_INCREMENT,
                        booking_id INT NOT NULL,
                        user_id INT NOT NULL,
                        action_type ENUM('check_in', 'check_out') NOT NULL,
                        action_time DATETIME NOT NULL,
                        status VARCHAR(50) NOT NULL,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        FOREIGN KEY (booking_id) REFERENCES bookings(booking_id),
                        FOREIGN KEY (user_id) REFERENCES tbl_users(user_id)
                    )";
                    
                    if ($conn->query($bookingLogsSql) === TRUE) {
                        echo "Table booking_logs created successfully\n";
                    } else {
                        throw new Exception("Error creating booking_logs table: " . $conn->error);
                    }
                    
                    // Add admin user
                    $adminEmail = "tomshibu666@gmail.com";
                    $adminUsername = "tomshibu1829";
                    $adminName = "Tom Shibu";
                    $adminPassword = password_hash("Admin@123", PASSWORD_DEFAULT);
                    $isAdmin = true;  // Set admin flag
                    
                    $insertAdminSql = "INSERT INTO tbl_users (email, passwordhash, name, username, is_admin) 
                                      VALUES (?, ?, ?, ?, ?)
                                      ON DUPLICATE KEY UPDATE 
                                      passwordhash = VALUES(passwordhash),
                                      is_admin = VALUES(is_admin)";
                    
                    $stmt = $conn->prepare($insertAdminSql);
                    if ($stmt === false) {
                        throw new Exception("Error preparing statement: " . $conn->error);
                    }
                    
                    if (!$stmt->bind_param("ssssi", $adminEmail, $adminPassword, $adminName, $adminUsername, $isAdmin)) {
                        throw new Exception("Error binding parameters: " . $stmt->error);
                    }
                    
                    if (!$stmt->execute()) {
                        throw new Exception("Error creating admin user: " . $stmt->error);
                    }
                    echo "Admin user created successfully\n";
                    $stmt->close();
                } else {
                    throw new Exception("Error creating bookings table: " . $conn->error);
                }
            } else {
                throw new Exception("Error creating charging_stations table: " . $conn->error);
            }
        } else {
            throw new Exception("Error creating station_owner_requests table: " . $conn->error);
        }
    } else {
        throw new Exception("Error creating table: " . $conn->error);
    }

    // Create remember_tokens table
    $tokensSql = "CREATE TABLE IF NOT EXISTS remember_tokens (
        id INT PRIMARY KEY AUTO_INCREMENT,
        user_id INT NOT NULL,
        token VARCHAR(64) NOT NULL,
        expires DATETIME NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES tbl_users(user_id),
        INDEX (token),
        INDEX (expires)
    )";

    if ($conn->query($tokensSql) === TRUE) {
        echo "Table remember_tokens created successfully\n";
    } else {
        throw new Exception("Error creating remember_tokens table: " . $conn->error);
    }

    // Create notifications table
    $notificationsSql = "CREATE TABLE IF NOT EXISTS notifications (
        notification_id INT PRIMARY KEY AUTO_INCREMENT,
        user_id INT NOT NULL,
        title VARCHAR(255) NOT NULL,
        message TEXT NOT NULL,
        type ENUM('booking', 'system', 'alert') NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        is_read BOOLEAN DEFAULT FALSE,
        FOREIGN KEY (user_id) REFERENCES tbl_users(user_id)
    )";

    if ($conn->query($notificationsSql) === TRUE) {
        echo "Table notifications created successfully\n";
    } else {
        throw new Exception("Error creating notifications table: " . $conn->error);
    }

    // Create payment_details table
    $paymentDetailsSql = "CREATE TABLE IF NOT EXISTS payment_details (
        payment_id INT PRIMARY KEY AUTO_INCREMENT,
        booking_id INT NOT NULL,
        user_id INT NOT NULL,
        station_id INT NOT NULL,
        amount DECIMAL(10,2) NOT NULL,
        payment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        payment_method VARCHAR(50),
        transaction_id VARCHAR(255),
        status ENUM('completed', 'failed', 'refunded') DEFAULT 'completed',
        FOREIGN KEY (booking_id) REFERENCES bookings(booking_id),
        FOREIGN KEY (user_id) REFERENCES tbl_users(user_id),
        FOREIGN KEY (station_id) REFERENCES charging_stations(station_id),
        INDEX idx_payment_date (payment_date),
        INDEX idx_user_payment (user_id),
        INDEX idx_station_payment (station_id)
    )";

    if ($conn->query($paymentDetailsSql) === TRUE) {
        echo "Table payment_details created successfully\n";
    } else {
        throw new Exception("Error creating payment_details table: " . $conn->error);
    }

} catch (Exception $e) {
    die("Error: " . $e->getMessage());
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?>
