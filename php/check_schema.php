<?php
require_once 'php/config.php';
$result = mysqli_query($conn, "DESCRIBE appointments");
while($row = mysqli_fetch_assoc($result)) {
    echo "Field: " . $row['Field'] . " | Type: " . $row['Type'] . "\n";
}
?>
