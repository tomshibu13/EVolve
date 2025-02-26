<?php
session_start();
require_once 'config.php';

header('Content-Type: application/json');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

error_log("Database connection status: " . ($mysqli->connect_error ? 'Failed: ' . $mysqli->connect_error : 'Connected'));
error_log("Request data: " . file_get_contents('php://input'));

// Check database connection
if ($mysqli->connect_error) {
    echo json_encode([
        'success' => false,
        'message' => 'Database connection failed: ' . $mysqli->connect_error
    ]);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

// Validate incoming data
if (!$data || !isset($data['action'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request data'
    ]);
    exit;
}

if ($data['action'] === 'register') {
    try {
        $mysqli->begin_transaction();

        // Validate required fields
        if (empty($data['username']) || empty($data['email']) || empty($data['password']) || empty($data['fullName'])) {
            throw new Exception('All required fields must be filled');
        }

        // Log the incoming data for debugging
        error_log("Registration attempt - Username: " . $data['username'] . ", Email: " . $data['email']);

        // First, check if username or email already exists
        $stmt = $mysqli->prepare("SELECT user_id FROM tbl_users WHERE username = ? OR email = ?");
        if (!$stmt) {
            throw new Exception('Error preparing statement: ' . $mysqli->error);
        }

        $stmt->bind_param("ss", $data['username'], $data['email']);
        
        if (!$stmt->execute()) {
            throw new Exception('Error executing query: ' . $stmt->error);
        }
        
        $result = $stmt->get_result();
        
        // Log the query result
        error_log("Duplicate check result rows: " . $result->num_rows);

        if ($result->num_rows > 0) {
            // Log the duplicate entry details
            $row = $result->fetch_assoc();
            error_log("Duplicate entry found - User ID: " . $row['user_id']);
            throw new Exception('Username or email already exists');
        }

        // Create the user account
        $stmt = $mysqli->prepare("INSERT INTO tbl_users (username, email, passwordhash, name) VALUES (?, ?, ?, ?)");
        if (!$stmt) {
            throw new Exception('Error preparing insert statement: ' . $mysqli->error);
        }

        $password_hash = password_hash($data['password'], PASSWORD_DEFAULT);
        $stmt->bind_param("ssss", $data['username'], $data['email'], $password_hash, $data['fullName']);
        
        // Log the insert attempt
        error_log("Attempting to insert new user - Username: " . $data['username']);
        
        if (!$stmt->execute()) {
            throw new Exception('Error creating user account: ' . $stmt->error);
        }
        
        $user_id = $mysqli->insert_id;

        // If registering as station owner, create owner request
        if ($data['isStationOwner']) {
            if (empty($data['stationOwner'])) {
                throw new Exception('Station owner details are required');
            }

            $stmt = $mysqli->prepare("INSERT INTO station_owner_requests (
                user_id, owner_name, business_name, email, phone, 
                address, city, state, postal_code, business_registration, 
                password_hash, status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')");
            
            if (!$stmt) {
                throw new Exception('Error preparing station owner request: ' . $mysqli->error);
            }
            
            $stmt->bind_param("issssssssss",
                $user_id,
                $data['stationOwner']['owner_name'],
                $data['stationOwner']['business_name'],
                $data['stationOwner']['email'],
                $data['stationOwner']['phone'],
                $data['stationOwner']['address'],
                $data['stationOwner']['city'],
                $data['stationOwner']['state'],
                $data['stationOwner']['postal_code'],
                $data['stationOwner']['business_registration'],
                $password_hash
            );
            
            if (!$stmt->execute()) {
                throw new Exception('Error creating station owner request: ' . $stmt->error);
            }
        }

        $mysqli->commit();
        
        echo json_encode([
            'success' => true,
            'message' => $data['isStationOwner'] ? 
                'Registration submitted successfully. Pending approval.' : 
                'Registration successful!'
        ]);
    } catch (Exception $e) {
        $mysqli->rollback();
        error_log('Registration error: ' . $e->getMessage());
        error_log('MySQL Error: ' . $mysqli->error);
        echo json_encode([
            'success' => false,
            'message' => 'Registration failed: ' . $e->getMessage()
        ]);
    } finally {
        if (isset($stmt)) {
            $stmt->close();
        }
    }
} elseif ($data['action'] === 'login') {
    $email = $data['email'];
    $password = $data['password'];
    $remember = $data['remember'] ?? false;

    // Validate email and password
    if (empty($email) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'Email and password are required']);
        exit;
    }

    // Prepare SQL statement to find user by email
    $stmt = $mysqli->prepare("SELECT * FROM tbl_users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user && password_verify($password, $user['passwordhash'])) {
        // Password is correct
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['is_admin'] ? 'admin' : 'user';

        // Handle remember me functionality
        if ($remember) {
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+30 days'));
            
            // Store remember me token in database
            $stmt = $mysqli->prepare("INSERT INTO remember_tokens (user_id, token, expires) VALUES (?, ?, ?)");
            $stmt->bind_param("iss", $user['user_id'], $token, $expires);
            $stmt->execute();
            
            // Set remember me cookie
            setcookie('remember_token', $token, strtotime('+30 days'), '/', '', true, true);
        }

        // Check if user is a station owner
        $stmt = $mysqli->prepare("SELECT request_id, status FROM station_owner_requests WHERE user_id = ? ORDER BY created_at DESC LIMIT 1");
        $stmt->bind_param("i", $user['user_id']);
        $stmt->execute();
        $owner_result = $stmt->get_result();
        $is_station_owner = false;
        $owner_status = null;

        if ($owner_result->num_rows > 0) {
            $owner_data = $owner_result->fetch_assoc();
            if ($owner_data['status'] === 'approved') {
                $is_station_owner = true;
            }
            $owner_status = $owner_data['status'];
        }

        $_SESSION['is_station_owner'] = $is_station_owner;
        
        echo json_encode([
            'success' => true,
            'user' => [
                'id' => $user['user_id'],
                'email' => $user['email'],
                'username' => $user['username'],
                'role' => $user['is_admin'] ? 'admin' : 'user',
                'status' => $user['is_admin'] ? 'active' : ($is_station_owner ? 'pending' : 'active'),
                'is_station_owner' => $is_station_owner,
                'owner_status' => $owner_status
            ]
        ]);
    } else {
        // Invalid credentials
        echo json_encode(['success' => false, 'message' => 'Invalid email or password']);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid action specified'
    ]);
}

$mysqli->close();
?> 