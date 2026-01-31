<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = $_POST['password'];
    $role = mysqli_real_escape_string($conn, $_POST['role']);
    
    // Query user
    $query = "SELECT * FROM users WHERE username = '$username' AND role = '$role'";
    $result = mysqli_query($conn, $query);
    
    if (mysqli_num_rows($result) === 1) {
        $user = mysqli_fetch_assoc($result);
        
        // Verify password
        if (password_verify($password, $user['password'])) {
            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['full_name'] = $user['full_name'];
            
            // Redirect based on role
            switch ($user['role']) {
                case 'doctor':
                    header('Location: doctor_dashboard.html');
                    break;
                case 'patient':
                    header('Location: patient_dashboard.html');
                    break;
                case 'assistant':
                    header('Location: assistant_dashboard.html');
                    break;
                default:
                    header('Location: index.html');
            }
            exit();
        } else {
            $_SESSION['error'] = 'Invalid username or password';
            header('Location: login.html');
            exit();
        }
    } else {
        $_SESSION['error'] = 'Invalid username or password';
        header('Location: login.html');
        exit();
    }
}

// Logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: index.html');
    exit();
}
?>