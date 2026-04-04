<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'patient') {
    header("Location: login.html");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Dashboard - MediCare</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/tabs.css">
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
                <span style="color: var(--text-dark);">Welcome, <strong id="patientName"><?php echo htmlspecialchars($_SESSION['username']); ?></strong></span>
                <button class="btn-logout" onclick="window.location.href='php/login.php?logout=1'"><span class="hide-on-mobile">Logout</span> <i class="fas fa-sign-out-alt"></i></button>
            </div>
        </div>
    </nav>

    <!-- Dashboard -->
    <section class="dashboard">
        <div class="dashboard-container">
            <div class="dashboard-header">
                <h1>Patient Dashboard</h1>
                <p>Manage your health and appointments</p>
            </div>

            <!-- Quick Actions -->
            <div class="dashboard-grid" style="margin-bottom: 3rem;">
                <div class="dashboard-card" style="background: linear-gradient(135deg, #00DFA2 0%, #088395 100%); color: white; cursor: pointer;" onclick="openModal('appointmentModal')">
                    <div class="card-header">
                        <div>
                            <div class="card-title" style="color: white; font-size: 1.8rem;">Book Appointment</div>
                            <p style="margin-top: 0.5rem; opacity: 0.9;">Schedule a visit with your doctor</p>
                        </div>
                        <div class="card-icon" style="background: rgba(255,255,255,0.2);">
                            <i class="fas fa-calendar-plus"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabs -->
            <div style="margin-bottom: 2rem;">
                <div class="tabs-wrapper" style="display: flex; gap: 1rem; border-bottom: 2px solid var(--secondary);">
                    <button class="tab-btn active" onclick="switchTab('appointments')" id="appointmentsTab">
                        <i class="fas fa-calendar-check"></i> My Appointments
                    </button>
                    <button class="tab-btn" onclick="switchTab('history')" id="historyTab">
                        <i class="fas fa-file-medical"></i> Medical History
                    </button>
                    <button class="tab-btn" onclick="switchTab('prescriptions')" id="prescriptionsTab">
                        <i class="fas fa-prescription"></i> Prescriptions
                    </button>
                    <button class="tab-btn" onclick="switchTab('tips')" id="tipsTab">
                        <i class="fas fa-heartbeat"></i> Health Tips
                    </button>
                </div>
            </div>

            <!-- Tab Content -->
            <div id="appointmentsContent" class="tab-content active">
                <div class="dashboard-card">
                    <div class="card-header">
                        <h2 class="card-title">My Appointments</h2>
                    </div>
                    <div id="appointmentsList">
                        <p>Loading appointments...</p>
                    </div>
                </div>
            </div>

            <div id="historyContent" class="tab-content">
                <div class="dashboard-card">
                    <div class="card-header">
                        <h2 class="card-title">Medical History</h2>
                    </div>
                    <div id="medicalHistoryList">
                        <p>Loading medical history...</p>
                    </div>
                </div>
            </div>

            <div id="prescriptionsContent" class="tab-content">
                <div class="dashboard-card">
                    <div class="card-header">
                        <h2 class="card-title">My Prescriptions</h2>
                    </div>
                    <div id="prescriptionsList">
                        <p>Loading prescriptions...</p>
                    </div>
                </div>
            </div>

            <div id="tipsContent" class="tab-content">
                <div class="dashboard-card">
                    <div class="card-header">
                        <h2 class="card-title">Personalized Health Tips</h2>
                    </div>
                    <div id="healthTipsList" class="health-tips">
                        <p>Loading health tips...</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Book Appointment Modal -->
    <div id="appointmentModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Book Appointment</h2>
                <button class="close-modal" onclick="closeModal('appointmentModal')">&times;</button>
            </div>
            <form id="appointmentForm" onsubmit="createAppointment(event)">
                <input type="hidden" name="patient_id" id="currentPatientId">
                
                <div class="form-group">
                    <label class="form-label">Select Doctor</label>
                    <select name="doctor_id" class="form-select" id="doctorSelect" required>
                        <option value="">Choose a doctor...</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Appointment Date *</label>
                    <input type="date" name="appointment_date" class="form-input" required min="">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Preferred Time *</label>
                    <input type="time" name="appointment_time" class="form-input" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Reason for Visit *</label>
                    <textarea name="reason" class="form-textarea" placeholder="Describe your symptoms or reason for visit..." required></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">Symptoms *</label>
                    <input type="text" name="symptoms" class="form-input" placeholder="Enter symptoms" required>
                </div>
                <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                    <button type="button" class="btn-secondary" onclick="closeModal('appointmentModal')">Cancel</button>
                    <button type="submit" class="btn-primary">Book Appointment</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const sessionUserId = <?php echo isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'null'; ?>;
    </script>
    <script src="js/main.js"></script>
    <script src="js/patient_dashboard.js"></script>
</body>
</html>