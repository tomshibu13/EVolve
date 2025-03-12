<?php
session_start();
require_once '../config.php';  // Adjust path as needed

// Check if station owner is logged in
if (!isset($_SESSION['owner_name'])) {
    header("Location: ../stationlogin.php");
    exit();
}

$owner_name = $_SESSION['owner_name'];
$error = null;
$success = null;
$owner = null;

try {
    // Fetch station owner details
    $stmt = $pdo->prepare("
        SELECT 
            r.*
        FROM station_owner_requests r
        WHERE r.owner_name = ? AND r.status = 'approved'
        LIMIT 1
    ");
    $stmt->execute([$owner_name]);
    $owner = $stmt->fetch();

    if (!$owner) {
        header("Location: ../stationlogin.php");
        exit();
    }

    // Handle profile update
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $business_name = trim($_POST['business_name']);
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone']);
        $address = trim($_POST['address']);
        $city = trim($_POST['city']);
        $state = trim($_POST['state']);
        $postal_code = trim($_POST['postal_code']);

        // Update profile
        $updateStmt = $pdo->prepare("
            UPDATE station_owner_requests 
            SET 
                business_name = ?,
                email = ?,
                phone = ?,
                address = ?,
                city = ?,
                state = ?,
                postal_code = ?
            WHERE owner_name = ?
        ");

        $updateStmt->execute([
            $business_name,
            $email,
            $phone,
            $address,
            $city,
            $state,
            $postal_code,
            $owner_name
        ]);

        $success = "Profile updated successfully!";
        
        // Refresh owner data
        $stmt->execute([$owner_name]);
        $owner = $stmt->fetch();
    }

} catch (PDOException $e) {
    $error = "An error occurred: " . $e->getMessage();
    error_log($error);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Station Owner Profile</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/boxicons@2.0.7/css/boxicons.min.css" rel="stylesheet">
    <style>
        .profile-card {
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }
        .main-content {
            margin-left: 250px;
            padding: 20px;
        }
        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <?php include '../header.php'; ?>

        <div class="main-content">
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>

            <div class="card profile-card">
                <div class="card-body">
                    <h3 class="card-title mb-4">
                        <i class='bx bx-user-circle'></i> Profile Settings
                    </h3>

                    <form id="profileForm" method="POST" action="">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Owner Name</label>
                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($owner['owner_name']); ?>" readonly>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Business Name</label>
                                <input type="text" class="form-control" name="business_name" value="<?php echo htmlspecialchars($owner['business_name']); ?>">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($owner['email']); ?>">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Phone</label>
                                <input type="tel" class="form-control" name="phone" value="<?php echo htmlspecialchars($owner['phone']); ?>">
                            </div>

                            <div class="col-12 mb-3">
                                <label class="form-label">Address</label>
                                <textarea class="form-control" name="address" rows="3"><?php echo htmlspecialchars($owner['address']); ?></textarea>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label class="form-label">City</label>
                                <input type="text" class="form-control" name="city" value="<?php echo htmlspecialchars($owner['city']); ?>">
                            </div>

                            <div class="col-md-4 mb-3">
                                <label class="form-label">State</label>
                                <input type="text" class="form-control" name="state" value="<?php echo htmlspecialchars($owner['state']); ?>">
                            </div>

                            <div class="col-md-4 mb-3">
                                <label class="form-label">Postal Code</label>
                                <input type="text" class="form-control" name="postal_code" value="<?php echo htmlspecialchars($owner['postal_code']); ?>">
                            </div>
                        </div>

                        <div class="text-end mt-3">
                            <button type="submit" class="btn btn-primary" id="saveButton" disabled>
                                <i class='bx bx-save'></i> Save Changes
                            </button>
                            <button type="button" class="btn btn-secondary" onclick="window.history.back();">
                                <i class='bx bx-arrow-back'></i> Back
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('profileForm');
            const saveButton = document.getElementById('saveButton');
            const initialData = new FormData(form);

            form.addEventListener('input', function() {
                const currentData = new FormData(form);
                let isChanged = false;

                for (let [key, value] of currentData.entries()) {
                    if (value !== initialData.get(key)) {
                        isChanged = true;
                        break;
                    }
                }

                saveButton.disabled = !isChanged;
            });
        });
    </script>
</body>
</html>