<<<<<<< HEAD
<<<<<<< HEAD
<?php
session_start();

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Include database connection (update with your actual database file)
// include 'db_connection.php'; 

// Validate form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $username = $_POST['username'];
    $email = $_POST['email'];
    $phone_number = $_POST['phone_number'] ?? '';
    $address = $_POST['address'] ?? '';
    $profile_photo = $_FILES['profile_photo'] ?? null;

    // Process profile picture upload
    if ($profile_photo && $profile_photo['tmp_name']) {
        $photo_name = time() . '_' . basename($profile_photo['name']);
        $upload_dir = 'uploads/';
        $upload_path = $upload_dir . $photo_name;

        // Create the upload directory if it doesn't exist
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        if (move_uploaded_file($profile_photo['tmp_name'], $upload_path)) {
            $_SESSION['profile_photo'] = $upload_path; // Save the path in the session
        }
    }

    // Update user data in the database
    // Example query (update with your actual table/fields)
    /*
    $stmt = $conn->prepare("UPDATE users SET username = ?, email = ?, phone_number = ?, address = ?, profile_photo = ? WHERE id = ?");
    $stmt->bind_param("sssssi", $username, $email, $phone_number, $address, $upload_path, $user_id);
    $stmt->execute();
    */

    // Update session variables
    $_SESSION['username'] = $username;
    $_SESSION['email'] = $email;
    $_SESSION['phone_number'] = $phone_number;
    $_SESSION['address'] = $address;

    // Redirect back to profile page
    header("Location: profile.php");
    exit();
}
?>
=======
<?php
session_start();

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Include database connection (update with your actual database file)
// include 'db_connection.php'; 

// Validate form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $username = $_POST['username'];
    $email = $_POST['email'];
    $phone_number = $_POST['phone_number'] ?? '';
    $address = $_POST['address'] ?? '';
    $profile_photo = $_FILES['profile_photo'] ?? null;

    // Process profile picture upload
    if ($profile_photo && $profile_photo['tmp_name']) {
        $photo_name = time() . '_' . basename($profile_photo['name']);
        $upload_dir = 'uploads/';
        $upload_path = $upload_dir . $photo_name;

        // Create the upload directory if it doesn't exist
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        if (move_uploaded_file($profile_photo['tmp_name'], $upload_path)) {
            $_SESSION['profile_photo'] = $upload_path; // Save the path in the session
        }
    }

    // Update user data in the database
    // Example query (update with your actual table/fields)
    /*
    $stmt = $conn->prepare("UPDATE users SET username = ?, email = ?, phone_number = ?, address = ?, profile_photo = ? WHERE id = ?");
    $stmt->bind_param("sssssi", $username, $email, $phone_number, $address, $upload_path, $user_id);
    $stmt->execute();
    */

    // Update session variables
    $_SESSION['username'] = $username;
    $_SESSION['email'] = $email;
    $_SESSION['phone_number'] = $phone_number;
    $_SESSION['address'] = $address;

    // Redirect back to profile page
    header("Location: profile.php");
    exit();
}
?>
>>>>>>> 0c499f78677a34c0d64e35d5565441573d6c2b38
=======
<?php
session_start();

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Include database connection (update with your actual database file)
// include 'db_connection.php'; 

// Validate form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $username = $_POST['username'];
    $email = $_POST['email'];
    $phone_number = $_POST['phone_number'] ?? '';
    $address = $_POST['address'] ?? '';
    $profile_photo = $_FILES['profile_photo'] ?? null;

    // Process profile picture upload
    if ($profile_photo && $profile_photo['tmp_name']) {
        $photo_name = time() . '_' . basename($profile_photo['name']);
        $upload_dir = 'uploads/';
        $upload_path = $upload_dir . $photo_name;

        // Create the upload directory if it doesn't exist
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        if (move_uploaded_file($profile_photo['tmp_name'], $upload_path)) {
            $_SESSION['profile_photo'] = $upload_path; // Save the path in the session
        }
    }

    // Update user data in the database
    // Example query (update with your actual table/fields)
    /*
    $stmt = $conn->prepare("UPDATE users SET username = ?, email = ?, phone_number = ?, address = ?, profile_photo = ? WHERE id = ?");
    $stmt->bind_param("sssssi", $username, $email, $phone_number, $address, $upload_path, $user_id);
    $stmt->execute();
    */

    // Update session variables
    $_SESSION['username'] = $username;
    $_SESSION['email'] = $email;
    $_SESSION['phone_number'] = $phone_number;
    $_SESSION['address'] = $address;

    // Redirect back to profile page
    header("Location: profile.php");
    exit();
}
?>
>>>>>>> 0c499f78677a34c0d64e35d5565441573d6c2b38
