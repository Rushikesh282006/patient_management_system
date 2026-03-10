<?php
// login.php
session_start();

// Handle Logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("location: ../login.html");
    exit;
}

// Check if the user is already logged in
if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true){
    $role = $_SESSION["role"];
    header("location: ../{$role}_dashboard.php");
    exit;
}

// Include config file
require_once "config.php";

// Define variables and initialize with empty values
$username = $password = $role = "";
$username_err = $password_err = $role_err = $login_err = "";

// Processing form data when form is submitted
if($_SERVER["REQUEST_METHOD"] == "POST"){
 
    // Check if username is empty
    if(empty(trim($_POST["username"]))){
        $username_err = "Please enter username.";
    } else{
        $username = trim($_POST["username"]);
    }
    
    // Check if password is empty
    if(empty(trim($_POST["password"]))){
        $password_err = "Please enter your password.";
    } else{
        $password = trim($_POST["password"]);
    }
    
    // Check if role is empty
    if(empty(trim($_POST["role"]))){
        $role_err = "Please select a role.";
    } else{
        $role = trim($_POST["role"]);
    }
    
    // Validate credentials
    if(empty($username_err) && empty($password_err) && empty($role_err)){
        // Prepare a select statement
        $sql = "SELECT id, username, password, role FROM users WHERE username = ? AND role = ?";
        
        if($stmt = $conn->prepare($sql)){
            // Bind variables to the prepared statement as parameters
            $stmt->bind_param("ss", $param_username, $param_role);
            
            // Set parameters
            $param_username = $username;
            $param_role = $role;
            
            // Attempt to execute the prepared statement
            if($stmt->execute()){
                // Store result
                $stmt->store_result();
                
                // Check if username and role exists, if yes then verify password
                if($stmt->num_rows == 1){                    
                    // Bind result variables
                    $stmt->bind_result($id, $db_username, $hashed_password, $db_role);
                    if($stmt->fetch()){
                        // Using password_verify
                        // Fallback included if the DB has plaintext passwords for demo purposes
                        if(password_verify($password, $hashed_password) || $password === $hashed_password){
                            // Password is correct, so start a new session
                            
                            // Store data in session variables
                            $_SESSION["loggedin"] = true;
                            $_SESSION["user_id"] = $id; // Changed from 'id' to 'user_id'
                            $_SESSION["username"] = $db_username;
                            $_SESSION["role"] = $db_role; // Added role
                            
                            // Redirect user to their respective dashboard
                            header("location: ../{$db_role}_dashboard.php");
                            exit;
                        } else{
                            // Password is not valid
                            $login_err = "Invalid username or password.";
                        }
                    }
                } else{
                    // Username/role combination doesn't exist
                    $login_err = "Invalid username or password for the selected role.";
                }
            } else{
                $login_err = "Oops! Something went wrong. Please try again later.";
            }

            // Close statement
            $stmt->close();
        } else {
             $login_err = "Database error. Please try again later.";
        }
    }
    
    // Close connection
    $conn->close();
    
    // If we reach here, there was an error. Redirect back to login with error
    // In a real app we'd pass this error back, but for now just redirect to login page
    // Using crude alert script for demo since login form is pure html
    echo "<script>alert('{$login_err}'); window.location.href='../login.html';</script>";
}
?>
