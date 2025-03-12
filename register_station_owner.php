<?php
// Add these at the very top of the file
ini_set('display_errors', 1);
ini_set('log_errors', 1);
error_reporting(E_ALL);
error_log("Starting station owner registration process");

session_start();
header('Content-Type: application/json');

// Add at the very top, after <?php
error_log("POST Data: " . file_get_contents('php://input'));

// Add near the top after session_start()
error_log("Session data: " . print_r($_SESSION, true));
error_log("Session status: " . session_status());
error_log("Session ID: " . session_id());

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "evolve1";

try {
    // Get and validate POST data
    $input = file_get_contents('php://input');
    error_log("Raw input received: " . $input);
    
    $data = json_decode($input, true);
    error_log("Decoded data: " . print_r($data, true));
    
    if (!$data) {
        error_log("JSON decode error: " . json_last_error() . " - " . json_last_error_msg());
        throw new Exception("Invalid JSON data received: " . json_last_error_msg());
    }

    // Validate user session
    if (!isset($_SESSION['user_id'])) {
        error_log("Session contents: " . print_r($_SESSION, true));
        error_log("No user_id in session");
        throw new Exception("User not logged in");
    }
    error_log("User ID from session: " . $_SESSION['user_id']);

    // Validate required fields
    $required_fields = ['phone', 'address', 'city', 'state', 'postalCode', 'businessRegistration'];
    foreach ($required_fields as $field) {
        if (!isset($data[$field]) || trim($data[$field]) === '') {
            error_log("Missing required field: " . $field);
            throw new Exception("Missing required field: " . $field);
        }
    }

    // Database connection
    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) {
        error_log("Database connection failed: " . $conn->connect_error);
        throw new Exception("Database connection failed");
    }
    error_log("Database connection successful");

    // Start transaction
    $conn->begin_transaction();
    error_log("Transaction started");

    try {
        // Check for existing request
        $check_sql = "SELECT request_id FROM station_owner_requests WHERE user_id = ? AND status = 'pending'";
        $check_stmt = $conn->prepare($check_sql);
        if (!$check_stmt) {
            throw new Exception("Failed to prepare check statement: " . $conn->error);
        }
        
        $check_stmt->bind_param("i", $_SESSION['user_id']);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        
        if ($result->num_rows > 0) {
            throw new Exception("You already have a pending station owner request");
        }
        $check_stmt->close();
        error_log("No existing pending request found");

        // Get user data
        $user_sql = "SELECT name, email, passwordhash FROM tbl_users WHERE user_id = ?";
        $user_stmt = $conn->prepare($user_sql);
        if (!$user_stmt) {
            throw new Exception("Failed to prepare user statement: " . $conn->error);
        }
        
        $user_stmt->bind_param("i", $_SESSION['user_id']);
        $user_stmt->execute();
        $user_data = $user_stmt->get_result()->fetch_assoc();
        $user_stmt->close();

        if (!$user_data) {
            throw new Exception("User data not found");
        }
        error_log("User data retrieved: " . print_r($user_data, true));

        // Insert request
        $insert_sql = "INSERT INTO station_owner_requests (
            user_id, owner_name, business_name, email, phone, address, 
            city, state, postal_code, business_registration, password_hash, status
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')";
        
        error_log("SQL Query: " . $insert_sql);
        
        $insert_stmt = $conn->prepare($insert_sql);
        if (!$insert_stmt) {
            error_log("MySQL Error: " . $conn->errno . ": " . $conn->error);
            throw new Exception("Failed to prepare insert statement: " . $conn->error);
        }

        $businessName = $data['businessName'] ?? '';
        
        error_log("Binding parameters for insert");
        error_log("Parameters: " . print_r([
            'user_id' => $_SESSION['user_id'],
            'owner_name' => $user_data['name'],
            'business_name' => $businessName,
            'email' => $user_data['email'],
            'phone' => $data['phone'],
            'address' => $data['address'],
            'city' => $data['city'],
            'state' => $data['state'],
            'postal_code' => $data['postalCode'],
            'business_registration' => $data['businessRegistration'],
            'password_hash' => $user_data['passwordhash']
        ], true));

        $insert_stmt->bind_param("issssssssss",
            $_SESSION['user_id'],
            $user_data['name'],
            $businessName,
            $user_data['email'],
            $data['phone'],
            $data['address'],
            $data['city'],
            $data['state'],
            $data['postalCode'],
            $data['businessRegistration'],
            $user_data['passwordhash']
        );

        if (!$insert_stmt->execute()) {
            error_log("MySQL Error during execute: " . $insert_stmt->errno . ": " . $insert_stmt->error);
            error_log("SQL State: " . $insert_stmt->sqlstate);
            throw new Exception("Failed to submit request: " . $insert_stmt->error);
        }
        
        error_log("Insert successful. Affected rows: " . $insert_stmt->affected_rows);
        $insert_stmt->close();

        // Commit transaction
        $conn->commit();
        error_log("Transaction committed");

        echo json_encode([
            'success' => true,
            'message' => 'Station owner registration request submitted successfully'
        ]);

    } catch (Exception $e) {
        $conn->rollback();
        error_log("Transaction rolled back: " . $e->getMessage());
        throw $e;
    }

} catch (Exception $e) {
    error_log("Error in station owner registration: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} finally {
    if (isset($conn)) {
        $conn->close();
        error_log("Database connection closed");
    }
    error_log("=== End Station Owner Registration Process ===");
}
?> 