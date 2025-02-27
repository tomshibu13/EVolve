<?php
session_start();
require_once '../config.php';

// Check if user is logged in and is a station owner
if (!isset($_SESSION['user_id'])) {
    header("Location: stationlogin.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';

// Initialize user_data with default values
$user_data = [
    'name' => '',
    'email' => '',
    'owner_name' => '',
    'business_name' => '',
    'phone' => '',
    'address' => '',
    'city' => '',
    'state' => '',
    'postal_code' => '',
    'business_registration' => ''
];

// Fetch user and owner data
try {
    // First check if the user exists
    $stmt = $mysqli->prepare("SELECT * FROM tbl_users WHERE user_id = ?");
    if ($stmt === false) {
        throw new Exception($mysqli->error);
    }
    
    $stmt->bind_param("i", $user_id);
    if (!$stmt->execute()) {
        throw new Exception($stmt->error);
    }
    $user_result = $stmt->get_result();
    
    if ($user_result->num_rows === 0) {
        header("Location: stationlogin.php");
        exit();
    }
    
    // Now fetch the combined data
    $query = "
        SELECT u.*, sor.owner_name, sor.business_name, sor.email as owner_email, 
               sor.phone, sor.address, sor.city, sor.state, sor.postal_code, 
               sor.business_registration
        FROM tbl_users u
        LEFT JOIN station_owner_requests sor ON u.user_id = sor.user_id
        WHERE u.user_id = ? AND sor.status = 'approved'
    ";
    
    $stmt = $mysqli->prepare($query);
    if ($stmt === false) {
        throw new Exception($mysqli->error);
    }
    
    if (!$stmt->bind_param("i", $user_id)) {
        throw new Exception($stmt->error);
    }
    
    if (!$stmt->execute()) {
        throw new Exception($stmt->error);
    }
    
    $result = $stmt->get_result();
    $temp_data = $result->fetch_assoc();

    if ($temp_data) {
        $user_data = $temp_data;
    } else {
        $error_message = "No approved station owner profile found. Please wait for admin approval.";
    }
} catch (Exception $e) {
    $error_message = "Error fetching profile: " . $e->getMessage();
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $business_name = $_POST['business_name'];
    $address = $_POST['address'];
    $city = $_POST['city'];
    $state = $_POST['state'];
    $postal_code = $_POST['postal_code'];
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    
    try {
        // Start transaction
        $mysqli->begin_transaction();
        
        // Verify current password if trying to change password
        if (!empty($new_password)) {
            $stmt = $mysqli->prepare("SELECT password FROM tbl_users WHERE user_id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
            
            if (!password_verify($current_password, $user['password'])) {
                throw new Exception("Current password is incorrect");
            }
            
            // Update password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $mysqli->prepare("UPDATE tbl_users SET password = ? WHERE user_id = ?");
            $stmt->bind_param("si", $hashed_password, $user_id);
            $stmt->execute();
        }
        
        // Update user details
        $stmt = $mysqli->prepare("
            UPDATE tbl_users 
            SET name = ?, email = ?
            WHERE user_id = ?
        ");
        $stmt->bind_param("ssi", $name, $email, $user_id);
        $stmt->execute();
        
        // Update owner details
        $stmt = $mysqli->prepare("
            UPDATE station_owner_requests 
            SET phone = ?, business_name = ?, address = ?, city = ?, state = ?, postal_code = ?, business_registration = ?
            WHERE user_id = ?
        ");
        $stmt->bind_param("sssssssi", $phone, $business_name, $address, $city, $state, $postal_code, $business_registration, $user_id);
        $stmt->execute();
        
        $mysqli->commit();
        $success_message = "Profile updated successfully!";
        
        // Refresh user data
        header("Location: so_profile.php");
        exit();
    } catch (Exception $e) {
        $mysqli->rollback();
        $error_message = "Error updating profile: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - Station Owner Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/boxicons@2.0.7/css/boxicons.min.css" rel="stylesheet">
    <style>
        /* Reuse your existing styles from station-owner-dashboard.php */
        .profile-card {
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            border: none;
        }
        
        .profile-header {
            background: linear-gradient(45deg, #4e73df, #224abe);
            color: white;
            padding: 2rem;
            border-radius: 15px 15px 0 0;
        }
        
        .profile-img {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            border: 5px solid white;
            background-color: #e9ecef;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            color: #4e73df;
        }
    </style>
    <link rel="stylesheet" href="../header.css">
</head>
<body>
<?php
include '../header.php';
?>

    <div class="main-content">
        <div class="container">
            <?php if ($success_message): ?>
                <div class="alert alert-success"><?php echo $success_message; ?></div>
            <?php endif; ?>
            
            <?php if ($error_message): ?>
                <div class="alert alert-danger"><?php echo $error_message; ?></div>
            <?php endif; ?>

            <div class="mb-3">
                <a href="../station-owner-dashboard.php" class="btn btn-secondary">
                    <i class='bx bx-arrow-back'></i> Back to Dashboard
                </a>
            </div>

            <div class="card profile-card">
                <div class="profile-header text-center">
                    <div class="d-flex justify-content-center mb-3">
                        <div class="profile-img">
                            <i class='bx bx-user'></i>
                        </div>
                    </div>
                    <h3><?php echo htmlspecialchars($user_data['owner_name']); ?></h3>
                    <p class="mb-0">Station Owner</p>
                </div>
                
                <div class="card-body">
                    <form method="POST" action="">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Name</label>
                                <input type="text" class="form-control" name="name" 
                                       value="<?php echo htmlspecialchars($user_data['name']); ?>" required>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" name="email" 
                                       value="<?php echo htmlspecialchars($user_data['owner_email'] ?? $user_data['email']); ?>" required>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Phone Number</label>
                                <input type="tel" class="form-control" name="phone" 
                                       value="<?php echo htmlspecialchars($user_data['phone']); ?>" required>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Business Name</label>
                                <input type="text" class="form-control" name="business_name"
                                       value="<?php echo htmlspecialchars($user_data['business_name']); ?>" required>
                            </div>

                            <div class="col-md-12 mb-3">
                                <label class="form-label">Address</label>
                                <textarea class="form-control" name="address" required><?php echo htmlspecialchars($user_data['address']); ?></textarea>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label class="form-label">City</label>
                                <input type="text" class="form-control" name="city"
                                       value="<?php echo htmlspecialchars($user_data['city']); ?>" required>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label class="form-label">State</label>
                                <input type="text" class="form-control" name="state"
                                       value="<?php echo htmlspecialchars($user_data['state']); ?>" required>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label class="form-label">Postal Code</label>
                                <input type="text" class="form-control" name="postal_code"
                                       value="<?php echo htmlspecialchars($user_data['postal_code']); ?>" required>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Business Registration</label>
                                <input type="text" class="form-control" 
                                       value="<?php echo htmlspecialchars($user_data['business_registration']); ?>" readonly>
                            </div>
                            
      
                        
                        <div class="text-end">
                            <button type="submit" class="btn btn-primary">
                                <i class='bx bx-save'></i> Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>