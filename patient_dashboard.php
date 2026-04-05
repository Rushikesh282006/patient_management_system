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
    <link rel="stylesheet" href="css/style.css?v=5.0">
    <link rel="stylesheet" href="css/tabs.css">
    <link rel="stylesheet" href="css/plugins/flatpickr.min.css">
    <link rel="stylesheet" href="css/plugins/choices.min.css">
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
            <div class="nav-menu">
                <span class="welcome-msg">Welcome, <strong id="patientName"><?php echo htmlspecialchars($_SESSION['username']); ?></strong></span>
                <div class="nav-actions">
                    <a href="profile.php" class="btn-secondary" style="padding: 0.6rem 1.2rem; font-size: 0.9rem; display: flex; align-items: center; gap: 0.5rem; text-decoration: none; color: var(--primary); border: 1px solid rgba(10, 77, 104, 0.2); border-radius: 12px;">
                        <i class="fas fa-user-circle"></i> <span class="hide-on-mobile">My Profile</span>
                    </a>
                    <button class="btn-logout" onclick="window.location.href='php/login.php?logout=1'"><span class="hide-on-mobile">Logout</span> <i class="fas fa-sign-out-alt"></i></button>
                </div>
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
                    <div class="header-actions">
                        <input type="text" id="searchAppointments" class="form-input" placeholder="Search appointments..." 
                               onkeyup="searchTable('searchAppointments', 'appointmentsTable')">
                    </div>
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
                    <div class="header-actions">
                        <input type="text" id="searchPrescriptions" class="form-input" placeholder="Search diagnosis or med..." 
                               onkeyup="searchTable('searchPrescriptions', 'prescriptionsTable')">
                    </div>
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
            <form id="appointmentForm" class="modal-body" onsubmit="createAppointment(event)">
                <input type="hidden" name="patient_id" id="currentPatientId">
                
                <div class="form-group">
                    <label class="form-label">Select Doctor <span style="color: var(--text-light); font-weight: 400;">(Optional)</span></label>
                    <select name="doctor_id" class="form-select" id="doctorSelect">
                        <option value="">No preference / Let assistant assign</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Appointment Date *</label>
                    <input type="date" name="appointment_date" class="form-input" required min="">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Preferred Time *</label>
                    <input type="text" name="appointment_time" class="form-input time-picker" placeholder="Select time..." required>
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

    <!-- Cancellation Reason Modal -->
    <div id="cancellationModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Cancel Appointment</h2>
                <button class="close-modal" onclick="closeModal('cancellationModal')">&times;</button>
            </div>
            <form id="cancellationForm" class="modal-body" onsubmit="submitCancellation(event)">
                <input type="hidden" name="appointment_id" id="cancelAppointmentId">
                <div class="form-group">
                    <label class="form-label">Reason for Cancellation *</label>
                    <textarea name="cancellation_reason" class="form-textarea" placeholder="Describe the reason for cancellation (e.g., Change of plans, feeling better)..." required></textarea>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn-secondary" onclick="closeModal('cancellationModal')">Go Back</button>
                    <button type="submit" class="btn-primary" style="background: var(--primary); color: white;">Confirm Cancellation</button>
                </div>
            </form>
        </div>
    </div>

    <script src="js/plugins/flatpickr.min.js"></script>
    <script src="js/plugins/choices.min.js"></script>
    <script>
        const sessionUserId = <?php echo isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'null'; ?>;
        document.addEventListener('DOMContentLoaded', () => {
            const datePicker = flatpickr("input[name='appointment_date']", {
                dateFormat: "Y-m-d",
                minDate: "today",
                onChange: function(selectedDates, dateStr) {
                    const today = new Date().toISOString().split('T')[0];
                    if (dateStr === today) {
                        const now = new Date();
                        const hours = now.getHours();
                        const minutes = now.getMinutes();
                        timePicker.set('minTime', `${hours}:${minutes}`);
                    } else {
                        timePicker.set('minTime', null);
                    }
                }
            });
            
            const timePicker = flatpickr(".time-picker", {
                enableTime: true,
                noCalendar: true,
                dateFormat: "h:i K",
                time_24hr: false,
                minuteIncrement: 15
            });
        });
    </script>
    <script src="js/main.js?v=5.0"></script>
    <script src="js/patient_dashboard.js?v=5.0"></script>
  </body>
</html>