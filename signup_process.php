<?php
// Include database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "evolve1"; // Replace with your database name

$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Process the form data
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $phone = isset($_POST['phone']) ? $_POST['phone'] : ''; // Get phone if provided

    // Basic validations
    if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        die("All fields are required.");
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        die("Invalid email format.");
    }

    if ($password !== $confirm_password) {
        die("Passwords do not match.");
    }

    // Validate phone number (if provided)
    if (!empty($phone) && !preg_match("/^[0-9]{10}$/", $phone)) {
        die("Invalid phone number. Please enter a valid 10-digit phone number.");
    }

    // Process profile picture upload
    $profile_picture = null; // Default value
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == 0) {
        $targetDir = "uploads/profile_pictures/";
        $fileName = basename($_FILES["profile_picture"]["name"]);
        $targetFilePath = $targetDir . $fileName;

        // Ensure the directory exists
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }

        // Move the uploaded file
        if (move_uploaded_file($_FILES["profile_picture"]["tmp_name"], $targetFilePath)) {
            $profile_picture = $targetFilePath; // Store file path in the database
        } else {
            echo "Error uploading profile picture.";
        }
    }

    // Hash the password
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    // Check if email or username already exists
    $checkQuery = "SELECT * FROM tbl_users WHERE email = ? OR username = ?";
    $stmt = $conn->prepare($checkQuery);
    $stmt->bind_param("ss", $email, $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        die("Email or Username already exists.");
    }

    // Insert into the database
    $insertQuery = "INSERT INTO tbl_users (email, passwordhash, name, username, phone_number, profile_picture) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($insertQuery);
    $stmt->bind_param("ssssss", $email, $passwordHash, $username, $username, $phone, $profile_picture);

    if ($stmt->execute()) {
        // Redirect to index.php after successful sign-up
        header("Location: index.php");
        exit(); // Always use exit() after a header redirect to stop further script execution
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
}

$conn->close();
?>
