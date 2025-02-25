<?php
// Database connection credentials
$servername = "localhost";
$username = "root"; 
$password = "";    
$dbname = "evolve1";

// Create a connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get the form data
$username = $_POST['username'] ?? '';
$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';

// Validate input
$errors = [];
if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
    $errors[] = "All fields are required.";
}
if ($password !== $confirm_password) {
    $errors[] = "Passwords do not match.";
}

// Check if the email already exists
if (empty($errors)) {
    $stmt = $conn->prepare("SELECT * FROM tbl_users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $errors[] = "Email is already registered.";
    }
    $stmt->close();
}

// If there are no errors, proceed to insert the user
if (empty($errors)) {
    $passwordhash = password_hash($password, PASSWORD_DEFAULT);
    
    $stmt = $conn->prepare("INSERT INTO tbl_users (email, passwordhash, username) VALUES (?, ?, ?)");
    if (!$stmt) {
        error_log("Prepare failed: " . $conn->error);
        echo json_encode(['success' => false, 'message' => 'Registration failed. Please try again later.']);
        exit;
    }
    
    $stmt->bind_param("sss", $email, $passwordhash, $username);
    
    $result = $stmt->execute();
    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Registration successful!']);
    } else {
        error_log("Execute failed: " . $stmt->error);
        echo json_encode(['success' => false, 'message' => 'Registration failed. Please try again later.']);
    }
    
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => implode(", ", $errors)]);
}

// Log the input data for debugging
error_log("Registration attempt: Username: $username, Email: $email");

$conn->close();
?> 