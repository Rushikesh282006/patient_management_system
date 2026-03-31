<?php
require_once 'config.php'; 

$name = $_GET['name'];

$sql = "SELECT * FROM patients WHERE name LIKE '%$name%'";
$result = mysqli_query($conn, $sql);

$data = [];

while($row = mysqli_fetch_assoc($result)) {
    $data[] = $row;
}

echo json_encode($data);
?>