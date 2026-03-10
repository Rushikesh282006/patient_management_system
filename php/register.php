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
    $specialization = isset($_POST["specialization"]) ? trim($_POST["specialization"]) : "";
    
    // Validate required fields
    if (empty($full_name) || empty($username) || empty($email) || empty($phone) || empty($password) || empty($confirm_password) || empty($role)) {
        header("Location: ../register.html?error=" . urlencode("Please fill in all required fields."));
        exit();
    }
    
    // Check if passwords match
    if ($password !== $confirm_password) {
        header("Location: ../register.html?error=" . urlencode("Passwords do not match."));
        exit();
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
    $insert_sql = "INSERT INTO users (full_name, username, email, phone, password, role, specialization) VALUES (?, ?, ?, ?, ?, ?, ?)";
    
    if ($stmt = $conn->prepare($insert_sql)) {
        $stmt->bind_param("sssssss", $full_name, $username, $email, $phone, $hashed_password, $role, $specialization);
        
        if ($stmt->execute()) {
            // Success! Redirect to login
            echo "<script>alert('Registration successful! Please login.'); window.location.href='../login.html';</script>";
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
