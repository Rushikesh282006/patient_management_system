<?php
require_once 'php/config.php';
$query = "ALTER TABLE appointments ADD COLUMN cancellation_reason TEXT NULL";
if (mysqli_query($conn, $query)) {
    echo "Column 'cancellation_reason' added successfully.\n";
} else {
    echo "Error adding column: " . mysqli_error($conn) . "\n";
}
?>
