<?php
session_start();
require_once 'config.php';

// Check if the form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        // Create database connection
        $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Prepare SQL statement
        $stmt = $pdo->prepare("INSERT INTO support_messages (name, email, subject, message, created_at) VALUES (?, ?, ?, ?, NOW())");

        // Get form data
        $name = $_POST['name'];
        $email = $_POST['email'];
        $subject = $_POST['subject'];
        $message = $_POST['message'];

        // Execute the statement
        $stmt->execute([$name, $email, $subject, $message]);

        // Set success message
        $_SESSION['support_success'] = "Thank you for your message. We'll get back to you soon!";

        // Send email notification (optional)
        $to = "evolve1829@gmail.com"; // Your support email
        $headers = "From: $email\r\n";
        $headers .= "Reply-To: $email\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        
        $emailBody = "
            <h3>New Support Message</h3>
            <p><strong>Name:</strong> $name</p>
            <p><strong>Email:</strong> $email</p>
            <p><strong>Subject:</strong> $subject</p>
            <p><strong>Message:</strong></p>
            <p>$message</p>
        ";

        mail($to, "Support Request: $subject", $emailBody, $headers);

    } catch(PDOException $e) {
        // Log error and set error message
        error_log("Support form error: " . $e->getMessage());
        $_SESSION['support_error'] = "Sorry, there was an error processing your request. Please try again later.";
    }

    // Redirect back to the form
    header("Location: index.php#support");
    exit();
}

// If someone tries to access this file directly without POST data
header("Location: index.php");
exit();
?> 