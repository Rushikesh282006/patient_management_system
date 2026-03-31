<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'assistant') {
    header("Location: login.html");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assistant Dashboard - MediCare</title>
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
            <div style="display: flex; align-items: center; gap: 2rem;">
                <span style="color: var(--text-dark);">Welcome, <strong id="assistantName"><?php echo htmlspecialchars($_SESSION['username']); ?></strong></span>
                <button class="btn-logout" onclick="window.location.href='php/login.php?logout=1'"><span class="hide-on-mobile">Logout</span> <i class="fas fa-sign-out-alt"></i></button>
            </div>
        </div>
    </nav>

    <!-- Dashboard -->
    <section class="dashboard">
        <div class="dashboard-container">
            <div class="dashboard-header">
                <h1>Assistant Dashboard</h1>
                <p>Manage appointments and coordinate patient care</p>
            </div>

            <!-- Quick Actions -->
            <div class="dashboard-grid" style="margin-bottom: 3rem;">
                <div class="dashboard-card" style="background: linear-gradient(135deg, #00DFA2 0%, #088395 100%); color: white; cursor: pointer;" onclick="openModal('appointmentModal')">
                    <div class="card-header">
                        <div>
                            <div class="card-title" style="color: white; font-size: 1.8rem;">Schedule Appointment</div>
                            <p style="margin-top: 0.5rem; opacity: 0.9;">Book appointment for a patient</p>
                        </div>
                        <div class="card-icon" style="background: rgba(255,255,255,0.2);">
                            <i class="fas fa-calendar-plus"></i>
                        </div>
                    </div>
                </div>

                <div class="dashboard-card" style="background: linear-gradient(135deg, #0A4D68 0%, #088395 100%); color: white; cursor: pointer;" onclick="openModal('addHealthTipModal')">
                    <div class="card-header">
                        <div>
                            <div class="card-title" style="color: white; font-size: 1.8rem;">Add Health Tip</div>
                            <p style="margin-top: 0.5rem; opacity: 0.9;">Send health tip to patient</p>
                        </div>
                        <div class="card-icon" style="background: rgba(255,255,255,0.2);">
                            <i class="fas fa-lightbulb"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Appointments List -->
            <div class="dashboard-card">
                <div class="card-header">
                    <h2 class="card-title">All Appointments</h2>
                    <div style="display: flex; gap: 1rem;">
                        <input type="text" id="searchAppointments" class="form-input" placeholder="Search appointments..." 
                               style="max-width: 300px; padding: 0.7rem;" onkeyup="searchTable('searchAppointments', 'appointmentsTable')">
                        <select id="filterStatus" class="form-select" style="max-width: 200px;" onchange="filterAppointmentsByStatus()">
                            <option value="">All Status</option>
                            <option value="scheduled">Scheduled</option>
                            <option value="completed">Completed</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                </div>
                <div id="appointmentsList">
                    <p>Loading appointments...</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Book Appointment Modal -->
    <div id="appointmentModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Schedule Appointment</h2>
                <button class="close-modal" onclick="closeModal('appointmentModal')">&times;</button>
            </div>
            <form id="appointmentForm" onsubmit="createAppointment(event)">
                <div class="form-group">
                    <label class="form-label">Select Patient *</label>
                    <select name="patient_id" class="form-select" id="patientSelect" required>
                        <option value="">Choose a patient...</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Select Doctor *</label>
                    <select name="doctor_id" class="form-select" id="doctorSelect" required>
                        <option value="">Choose a doctor...</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Appointment Date *</label>
                    <input type="date" name="appointment_date" class="form-input" required min="">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Appointment Time *</label>
                    <input type="time" name="appointment_time" class="form-input" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Reason for Visit *</label>
                    <textarea name="reason" class="form-textarea" placeholder="Enter reason for appointment..." required></textarea>
                </div>
                
                <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                    <button type="button" class="btn-secondary" onclick="closeModal('appointmentModal')">Cancel</button>
                    <button type="submit" class="btn-primary">Schedule Appointment</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Add Health Tip Modal -->
    <div id="addHealthTipModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Add Health Tip</h2>
                <button class="close-modal" onclick="closeModal('addHealthTipModal')">&times;</button>
            </div>
            <form id="healthTipForm" onsubmit="addHealthTip(event)">
                <div class="form-group">
                    <label class="form-label">Select Patient *</label>
                    <select name="patient_id" class="form-select" id="tipPatientSelect" required>
                        <option value="">Choose a patient...</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Category *</label>
                    <input type="text" name="tip_category" class="form-input" placeholder="e.g., Diet, Exercise, Medication" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Tip Title *</label>
                    <input type="text" name="tip_title" class="form-input" placeholder="Enter tip title..." required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Tip Content *</label>
                    <textarea name="tip_content" class="form-textarea" placeholder="Enter health tip details..." required></textarea>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Priority *</label>
                    <select name="priority" class="form-select" required>
                        <option value="low">Low</option>
                        <option value="medium" selected>Medium</option>
                        <option value="high">High</option>
                    </select>
                </div>
                
                <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                    <button type="button" class="btn-secondary" onclick="closeModal('addHealthTipModal')">Cancel</button>
                    <button type="submit" class="btn-primary">Add Tip</button>
                </div>
            </form>
        </div>
    </div>

    <script src="js/main.js"></script>
    <script src="js/assistant_dashboard.js"></script>
</body>
</html>