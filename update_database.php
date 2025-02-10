
<?php
require_once 'config.php';

$sql = "ALTER TABLE users ADD remember_token VARCHAR(64) NULL";
if ($mysqli->query($sql)) {
    echo "Remember token column added successfully";
} else {
    echo "Error adding remember token column: " . $mysqli->error;
}

$mysqli->close();
?> 