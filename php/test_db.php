<?php
require_once "config.php";

echo "Attempting to insert a test user...<br>";

// Test data
$full_name = "Test User";
$username = "testuser_" . time();
$email = "test" . time() . "@example.com";
$phone = "1234567890";
$password = password_hash("password", PASSWORD_DEFAULT);
$role = "patient";
$specialization = "";

$insert_sql = "INSERT INTO users (full_name, username, email, phone, password, role, specialization) VALUES (?, ?, ?, ?, ?, ?, ?)";
    
if ($stmt = $conn->prepare($insert_sql)) {
    $stmt->bind_param("sssssss", $full_name, $username, $email, $phone, $password, $role, $specialization);
    
    if ($stmt->execute()) {
        echo "Success! Inserted user ID: " . $stmt->insert_id;
    } else {
        echo "Execute Failed: " . $stmt->error . "<br>";
        echo "Error No: " . $stmt->errno . "<br>";
    }
    $stmt->close();
} else {
    echo "Prepare Failed: " . $conn->error . "<br>";
}

echo "<h3>Table Structure:</h3>";
$result = $conn->query("DESCRIBE users");
if ($result) {
    echo "<table border='1'><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['Field'] . "</td>";
        echo "<td>" . $row['Type'] . "</td>";
        echo "<td>" . $row['Null'] . "</td>";
        echo "<td>" . $row['Key'] . "</td>";
        echo "<td>" . ($row['Default'] !== null ? $row['Default'] : 'NULL') . "</td>";
        echo "<td>" . $row['Extra'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "Could not describe table: " . $conn->error;
}

$conn->close();
?>
