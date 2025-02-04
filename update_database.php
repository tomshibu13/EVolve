<<<<<<< HEAD
<?php
require_once 'config.php';

$sql = "ALTER TABLE users ADD remember_token VARCHAR(64) NULL";
if ($mysqli->query($sql)) {
    echo "Remember token column added successfully";
} else {
    echo "Error adding remember token column: " . $mysqli->error;
}

$mysqli->close();
=======
<?php
require_once 'config.php';

$sql = "ALTER TABLE users ADD remember_token VARCHAR(64) NULL";
if ($mysqli->query($sql)) {
    echo "Remember token column added successfully";
} else {
    echo "Error adding remember token column: " . $mysqli->error;
}

$mysqli->close();
>>>>>>> 0c499f78677a34c0d64e35d5565441573d6c2b38
?> 