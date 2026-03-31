<?php
// edit.php
require_once 'config.php';

// Check if user ID is present
if (!isset($_GET['id'])) {
    die("User ID is required.");
}

$id = $_GET['id'];

// Fetch the existing user data
$query = "SELECT * FROM users WHERE id = ?";
if ($stmt = $conn->prepare($query)) {
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
    $stmt->close();
}

if (!$data) {
    die("User not found.");
}

// Check for update submission
if(isset($_POST['update'])) {
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);

    // UPDATE query for the editable elements
    $update_sql = "UPDATE users SET email=?, phone=?, address=? WHERE id=?";
    
    if($update_stmt = $conn->prepare($update_sql)) {
        $update_stmt->bind_param("sssi", $email, $phone, $address, $id);
        
        if($update_stmt->execute()) {
            echo "<script>alert('Profile updated successfully!'); window.location.href='patient_dashboard.php';</script>";
        } else {
            echo "<script>alert('Error updating profile.');</script>";
        }
        $update_stmt->close();
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Edit Profile</title>
</head>
<body>
    <h2>Update Profile Details</h2>
    <!-- Read-only fields -->
    <p><strong>Full Name:</strong> <?php echo htmlspecialchars($data['full_name'] ?? ''); ?></p>
    <p><strong>Gender:</strong> <?php echo htmlspecialchars($data['gender'] ?? ''); ?></p>
    <p><strong>Date of Birth:</strong> <?php echo htmlspecialchars($data['date_of_birth'] ?? ''); ?></p>
    <p><strong>Blood Group:</strong> <?php echo htmlspecialchars($data['blood_group'] ?? ''); ?></p>
    <hr>
    
    <!-- HTML Form to edit the mutable values -->
    <form method="POST">
        <label>Email:</label>
        <input type="email" name="email" value="<?php echo htmlspecialchars($data['email'] ?? ''); ?>" required><br><br>
        
        <label>Phone Number:</label>
        <input type="tel" name="phone" value="<?php echo htmlspecialchars($data['phone'] ?? ''); ?>" required><br><br>
        
        <label>Address:</label>
        <input type="text" name="address" value="<?php echo htmlspecialchars($data['address'] ?? ''); ?>"><br><br>
        
        <button type="submit" name="update">Update Record</button>
    </form>
</body>
</html>