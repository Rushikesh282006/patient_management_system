<?php
// Include config file
require_once "config.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Collect and sanitize input
    $full_name = trim($_POST["full_name"]);
    $username = trim($_POST["username"]);
    $email = trim($_POST["email"]);
    $phone = trim($_POST["phone"]);
    $password = $_POST["password"];
    $confirm_password = $_POST["confirm_password"];
    $role = trim($_POST["role"]);
    $access_code = isset($_POST["access_code"]) ? trim($_POST["access_code"]) : "";
    $specialization = isset($_POST["specialization"]) ? trim($_POST["specialization"]) : "";
    $gender = isset($_POST["gender"]) ? trim($_POST["gender"]) : "";
    $dob = isset($_POST["dob"]) ? trim($_POST["dob"]) : "";
    $address = isset($_POST["address"]) ? trim($_POST["address"]) : "";
    $blood_group = isset($_POST["blood_group"]) ? trim($_POST["blood_group"]) : "";
    
    // Validate required fields
    if (empty($full_name) || empty($username) || empty($email) || empty($phone) || empty($password) || empty($confirm_password) || empty($role) || empty($gender) || empty($dob)) {
        header("Location: ../register.html?error=" . urlencode("Please fill in all required fields."));
        exit();
    }
    
    // Check if passwords match
    if ($password !== $confirm_password) {
        header("Location: ../register.html?error=" . urlencode("Passwords do not match."));
        exit();
    }
    
    // Verify Access Codes for Staff Roles
    if ($role === "doctor") {
        if ($access_code !== "DOC_VERIFY_2026") {
            header("Location: ../register.html?error=" . urlencode("Invalid Doctor Access Code. Registration denied."));
            exit();
        }
    } elseif ($role === "assistant") {
        if ($access_code !== "AST_VERIFY_2026") {
            header("Location: ../register.html?error=" . urlencode("Invalid Assistant Access Code. Registration denied."));
            exit();
        }
    }
    
    // Check if username or email already exists
    $check_sql = "SELECT id FROM users WHERE username = ? OR email = ?";
    if ($stmt = $conn->prepare($check_sql)) {
        $stmt->bind_param("ss", $username, $email);
        $stmt->execute();
        $stmt->store_result();
        
        if ($stmt->num_rows > 0) {
            header("Location: ../register.html?error=" . urlencode("Username or email already exists."));
            $stmt->close();
            exit();
        }
        $stmt->close();
    }
    
    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // Insert into database
    $insert_sql = "INSERT INTO users (full_name, username, email, phone, password, role, specialization, gender, date_of_birth, address) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    if ($stmt = $conn->prepare($insert_sql)) {
        $stmt->bind_param("ssssssssss", $full_name, $username, $email, $phone, $hashed_password, $role, $specialization, $gender, $dob, $address);
        
        if ($stmt->execute()) {
            // Success! Redirect to login with success message
            header("Location: ../login.html?success=" . urlencode("Registration successful! Please login."));
            exit();
        } else {
            header("Location: ../register.html?error=" . urlencode("Something went wrong. Please try again."));
        }
        $stmt->close();
    }
    
    $conn->close();
} else {
    // If accessed via GET, redirect to register page
    header("Location: ../register.html");
    exit();
}
?>
