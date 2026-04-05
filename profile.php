<?php
session_start();
require_once 'php/config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit();
}

$user_id = $_SESSION['user_id'];
$success_msg = $error_msg = "";

// Handle Profile Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update') {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $address = mysqli_real_escape_string($conn, $_POST['address']);
    $password = $_POST['current_password'];
    
    // Additional field for doctors
    $specialization = isset($_POST['specialization']) ? mysqli_real_escape_string($conn, $_POST['specialization']) : null;
    
    // Verify Password
    $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    
    if (password_verify($password, $user['password']) || $password === $user['password']) {
        // Build Update Query
        if ($_SESSION['role'] === 'doctor' && $specialization !== null) {
            $update_stmt = $conn->prepare("UPDATE users SET email = ?, phone = ?, address = ?, specialization = ? WHERE id = ?");
            $update_stmt->bind_param("ssssi", $email, $phone, $address, $specialization, $user_id);
        } else {
            $update_stmt = $conn->prepare("UPDATE users SET email = ?, phone = ?, address = ? WHERE id = ?");
            $update_stmt->bind_param("sssi", $email, $phone, $address, $user_id);
        }
        
        if ($update_stmt->execute()) {
            $success_msg = "Profile updated successfully!";
        } else {
            $error_msg = "Error updating profile. Please try again.";
        }
        $update_stmt->close();
    } else {
        $error_msg = "Incorrect password. Update denied.";
    }
    $stmt->close();
}

// Handle Account Deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $password = $_POST['delete_password'];
    
    $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    
    if (password_verify($password, $user['password']) || $password === $user['password']) {
        // Delete User
        $delete_stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $delete_stmt->bind_param("i", $user_id);
        
        if ($delete_stmt->execute()) {
            session_destroy();
            echo "<script>alert('Account deleted successfully.'); window.location.href='index.html';</script>";
            exit();
        } else {
            $error_msg = "Error deleting account.";
        }
        $delete_stmt->close();
    } else {
        $error_msg = "Incorrect password. Account deletion denied.";
    }
    $stmt->close();
}

// Fetch current user data
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_data = $stmt->get_result()->fetch_assoc();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - MediCare</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="nav-container">
            <a href="index.html" class="logo">
                <div class="logo-icon">
                    <i class="fas fa-heartbeat"></i>
                </div>
                MediCare
            </a>
            <div style="display: flex; align-items: center; gap: 1rem;">
                <a href="<?php echo $_SESSION['role']; ?>_dashboard.php" class="btn-secondary" style="padding: 0.6rem 1.2rem; font-size: 0.9rem; border-radius: 12px; text-decoration: none;">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
            </div>
        </div>
    </nav>

    <section class="dashboard">
        <div class="dashboard-container" style="max-width: 800px;">
            <div class="dashboard-header" style="text-align: center;">
                <h1>My Profile</h1>
                <p>Manage your account settings and preferences</p>
            </div>

            <?php if ($success_msg): ?>
                <div class="status-msg success" style="background: #00DFA2; color: white; padding: 1rem; border-radius: 12px; margin-bottom: 2rem; text-align: center;">
                    <i class="fas fa-check-circle"></i> <?php echo $success_msg; ?>
                </div>
            <?php endif; ?>

            <?php if ($error_msg): ?>
                <div class="status-msg error" style="background: #D32F2F; color: white; padding: 1rem; border-radius: 12px; margin-bottom: 2rem; text-align: center;">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error_msg; ?>
                </div>
            <?php endif; ?>

            <div class="dashboard-card" id="profileView">
                <div class="card-header">
                    <h2 class="card-title">Personal Information</h2>
                    <button class="btn-primary" onclick="toggleEdit(true)" style="padding: 0.6rem 1.2rem; font-size: 0.9rem;">
                        <i class="fas fa-edit"></i> Edit Profile
                    </button>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-top: 1.5rem;">
                    <div>
                        <label style="display: block; font-weight: 600; color: var(--text-light); font-size: 0.9rem; margin-bottom: 0.3rem;">Full Name</label>
                        <p style="font-size: 1.1rem;"><?php echo htmlspecialchars($user_data['full_name']); ?></p>
                    </div>
                    <div>
                        <label style="display: block; font-weight: 600; color: var(--text-light); font-size: 0.9rem; margin-bottom: 0.3rem;">Role</label>
                        <p style="font-size: 1.1rem; text-transform: capitalize;"><?php echo htmlspecialchars($user_data['role']); ?></p>
                    </div>
                    <div>
                        <label style="display: block; font-weight: 600; color: var(--text-light); font-size: 0.9rem; margin-bottom: 0.3rem;">Username</label>
                        <p style="font-size: 1.1rem;"><?php echo htmlspecialchars($user_data['username']); ?></p>
                    </div>
                    <div>
                        <label style="display: block; font-weight: 600; color: var(--text-light); font-size: 0.9rem; margin-bottom: 0.3rem;">Email Address</label>
                        <p style="font-size: 1.1rem;"><?php echo htmlspecialchars($user_data['email']); ?></p>
                    </div>
                    <div>
                        <label style="display: block; font-weight: 600; color: var(--text-light); font-size: 0.9rem; margin-bottom: 0.3rem;">Phone Number</label>
                        <p style="font-size: 1.1rem;"><?php echo htmlspecialchars($user_data['phone']); ?></p>
                    </div>
                    <div>
                        <label style="display: block; font-weight: 600; color: var(--text-light); font-size: 0.9rem; margin-bottom: 0.3rem;">Date of Birth</label>
                        <p style="font-size: 1.1rem;"><?php echo htmlspecialchars($user_data['date_of_birth']); ?></p>
                    </div>
                    <?php if ($_SESSION['role'] === 'doctor'): ?>
                    <div>
                        <label style="display: block; font-weight: 600; color: var(--text-light); font-size: 0.9rem; margin-bottom: 0.3rem;">Specialization</label>
                        <p style="font-size: 1.1rem;"><?php echo htmlspecialchars($user_data['specialization']); ?></p>
                    </div>
                    <?php endif; ?>
                    <div style="grid-column: span 2;">
                        <label style="display: block; font-weight: 600; color: var(--text-light); font-size: 0.9rem; margin-bottom: 0.3rem;">Address</label>
                        <p style="font-size: 1.1rem;"><?php echo htmlspecialchars($user_data['address'] ?: 'Not provided'); ?></p>
                    </div>
                </div>

                <div style="margin-top: 3rem; border-top: 1px solid var(--secondary); padding-top: 2rem;">
                    <h3 style="color: #D32F2F; margin-bottom: 1rem;">Danger Zone</h3>
                    <p style="color: var(--text-light); margin-bottom: 1.5rem;">Once you delete your account, there is no going back. Please be certain.</p>
                    <button class="btn-logout" onclick="openModal('deleteModal')" style="padding: 0.8rem 1.5rem; width: fit-content;">
                        <i class="fas fa-trash-alt"></i> Delete My Account
                    </button>
                </div>
            </div>

            <!-- Edit Form (Initially Hidden) -->
            <div class="dashboard-card" id="profileEdit" style="display: none;">
                <div class="card-header">
                    <h2 class="card-title">Edit Profile</h2>
                    <button class="btn-secondary" onclick="toggleEdit(false)" style="padding: 0.6rem 1.2rem; font-size: 0.9rem;">
                        Cancel
                    </button>
                </div>
                
                <form method="POST" style="margin-top: 1.5rem;">
                    <input type="hidden" name="action" value="update">
                    
                    <div class="form-group">
                        <label class="form-label">Email Address *</label>
                        <input type="email" name="email" class="form-input" value="<?php echo htmlspecialchars($user_data['email']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Phone Number *</label>
                        <input type="tel" name="phone" class="form-input" value="<?php echo htmlspecialchars($user_data['phone']); ?>" required>
                    </div>
                    
                    <?php if ($_SESSION['role'] === 'doctor'): ?>
                    <div class="form-group">
                        <label class="form-label">Specialization *</label>
                        <input type="text" name="specialization" class="form-input" value="<?php echo htmlspecialchars($user_data['specialization']); ?>" required>
                    </div>
                    <?php endif; ?>
                    
                    <div class="form-group">
                        <label class="form-label">Address</label>
                        <textarea name="address" class="form-textarea" style="min-height: 80px;"><?php echo htmlspecialchars($user_data['address']); ?></textarea>
                    </div>

                    <div style="background: var(--secondary); padding: 1.5rem; border-radius: 12px; margin: 2rem 0;">
                        <label class="form-label" style="color: var(--primary); font-weight: 700;">Confirm Password *</label>
                        <p style="font-size: 0.85rem; color: var(--text-light); margin-bottom: 1rem;">Please enter your current password to save changes.</p>
                        <input type="password" name="current_password" class="form-input" placeholder="Enter your password" required>
                    </div>

                    <button type="submit" class="btn-primary" style="width: 100%; padding: 1.2rem;">
                        <i class="fas fa-save"></i> Save Changes
                    </button>
                </form>
            </div>
        </div>
    </section>

    <!-- Delete Account Confirmation Modal -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title" style="color: #D32F2F;">Delete Account?</h2>
                <button class="close-modal" onclick="closeModal('deleteModal')">&times;</button>
            </div>
            <div class="modal-body">
                <p style="margin-bottom: 1.5rem;">Are you absolutely sure? This will permanently delete your account and all associated data. You will not be able to log in again.</p>
                
                <form method="POST">
                    <input type="hidden" name="action" value="delete">
                    <div class="form-group">
                        <label class="form-label">Type your password to confirm *</label>
                        <input type="password" name="delete_password" class="form-input" placeholder="Current Password" required>
                    </div>
                    
                    <div class="modal-actions">
                        <button type="button" class="btn-secondary" onclick="closeModal('deleteModal')">No, Keep Account</button>
                        <button type="submit" class="btn-logout" style="background: #D32F2F; color: white;">Yes, Delete Account</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="js/main.js"></script>
    <script>
        function toggleEdit(isEditing) {
            document.getElementById('profileView').style.display = isEditing ? 'none' : 'block';
            document.getElementById('profileEdit').style.display = isEditing ? 'block' : 'none';
            // Scroll to top of card
            if (isEditing) {
                document.getElementById('profileEdit').scrollIntoView({ behavior: 'smooth' });
            }
        }
    </script>
</body>
</html>
