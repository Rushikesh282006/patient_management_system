<?php
// Include config file
require_once "config.php";
header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Collect and sanitize input
    $full_name = trim($_POST["full_name"]);
    $username = trim($_POST["username"]);
    $email = trim($_POST["email"]);
    $phone = trim($_POST["phone"]);
    $password = $_POST["password"];
    $confirm_password = $_POST["confirm_password"];
    $role = trim($_POST["role"]);
    $specialization = isset($_POST["specialization"]) ? trim($_POST["specialization"]) : "";
    $gender = isset($_POST["gender"]) ? trim($_POST["gender"]) : "";
    $dob = isset($_POST["dob"]) ? trim($_POST["dob"]) : "";
    $address = isset($_POST["address"]) ? trim($_POST["address"]) : "";
    $blood_group = isset($_POST["blood_group"]) ? trim($_POST["blood_group"]) : "";
    
    // Validate required fields
    if (empty($full_name) || empty($username) || empty($email) || empty($phone) || empty($password) || empty($confirm_password) || empty($role) || empty($gender) || empty($dob)) {
        echo json_encode(["success" => false, "message" => "Please fill in all required fields."]);
        exit();
    }
    
    // Check if passwords match
    if ($password !== $confirm_password) {
        echo json_encode(["success" => false, "message" => "Passwords do not match."]);
        exit();
    }
    
    // Check if username or email already exists
    $check_sql = "SELECT id FROM users WHERE username = ? OR email = ?";
    if ($stmt = $conn->prepare($check_sql)) {
        $stmt->bind_param("ss", $username, $email);
        $stmt->execute();
        $stmt->store_result();
        
        if ($stmt->num_rows > 0) {
            echo json_encode(["success" => false, "message" => "Username or email already exists."]);
            $stmt->close();
            exit();
        }
        $stmt->close();
    }
    
    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // Insert into database
    $insert_sql = "INSERT INTO users (full_name, username, email, phone, password, role, specialization, gender, dob, address, blood_group) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    if ($stmt = $conn->prepare($insert_sql)) {
        $stmt->bind_param("sssssssssss", $full_name, $username, $email, $phone, $hashed_password, $role, $specialization, $gender, $dob, $address, $blood_group);
        
        if ($stmt->execute()) {
            echo json_encode(["success" => true, "message" => "Registration successful! Please login."]);
        } else {
            echo json_encode(["success" => false, "message" => "Something went wrong. Please try again."]);
        }
        $stmt->close();
    } else {
        echo json_encode(["success" => false, "message" => "Database error: " . $conn->error]);
    }
    
    $conn->close();
} else {
    // If accessed via GET
    echo json_encode(["success" => false, "message" => "Invalid request method."]);
    exit();
}
?>
