<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'doctor') {
    header("Location: login.html");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Dashboard - MediCare</title>
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
                <span style="color: var(--text-dark);">Welcome, <strong id="doctorName"><?php echo htmlspecialchars($_SESSION['username']); ?></strong></span>
                <button class="btn-logout" onclick="window.location.href='php/login.php?logout=1'"><span class="hide-on-mobile">Logout</span> <i class="fas fa-sign-out-alt"></i></button>
            </div>
        </div>
    </nav>

    <!-- Dashboard -->
    <section class="dashboard">
        <div class="dashboard-container">
            <div class="dashboard-header">
                <h1>Doctor Dashboard</h1>
                <p>Manage your appointments and patient care</p>
            </div>

            <!-- Quick Stats -->
            <div class="dashboard-grid">
                <div class="dashboard-card" style="background: linear-gradient(135deg, #0A4D68 0%, #088395 100%); color: white;">
                    <div class="card-header">
                        <div>
                            <div style="font-size: 3rem; font-weight: 700; margin-bottom: 0.5rem;" id="todayAppointments">0</div>
                            <div class="card-title" style="color: white;">Today's Appointments</div>
                        </div>
                        <div class="card-icon" style="background: rgba(255,255,255,0.2);">
                            <i class="fas fa-calendar-day"></i>
                        </div>
                    </div>
                </div>

                <div class="dashboard-card">
                    <div class="card-header">
                        <div>
                            <div style="font-size: 3rem; font-weight: 700; margin-bottom: 0.5rem; color: var(--accent);" id="totalPatients">0</div>
                            <div class="card-title">Total Patients</div>
                        </div>
                        <div class="card-icon">
                            <i class="fas fa-users"></i>
                        </div>
                    </div>
                </div>

                <div class="dashboard-card">
                    <div class="card-header">
                        <div>
                            <div style="font-size: 3rem; font-weight: 700; margin-bottom: 0.5rem; color: var(--primary);" id="totalPrescriptions">0</div>
                            <div class="card-title">Prescriptions Issued</div>
                        </div>
                        <div class="card-icon">
                            <i class="fas fa-prescription"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Appointments List -->
            <div class="dashboard-card mt-2">
                <div class="card-header">
                    <h2 class="card-title">Appointments</h2>
                    <div class="header-actions" style="display: flex; gap: 1rem;">
                        <input type="text" id="searchAppointments" class="form-input" placeholder="Search appointments..." 
                               style="max-width: 300px; padding: 0.7rem;" onkeyup="searchTable('searchAppointments', 'appointmentsTable')">
                    </div>
                </div>
                <div id="appointmentsList">
                    <p>Loading appointments...</p>
                </div>
            </div>
        </div>
    </section>

    <!-- View Patient Modal -->
    <div id="patientModal" class="modal">
        <div class="modal-content" style="max-width: 900px;">
            <div class="modal-header">
                <h2 class="modal-title">Patient Details</h2>
                <button class="close-modal" onclick="closeModal('patientModal')">&times;</button>
            </div>
            <div id="patientDetails">
                <p>Loading patient details...</p>
            </div>
        </div>
    </div>

    <!-- Create Prescription Modal -->
    <div id="prescriptionModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Generate Prescription</h2>
                <button class="close-modal" onclick="closeModal('prescriptionModal')">&times;</button>
            </div>
            <form id="prescriptionForm" onsubmit="generatePrescription(event)">
                <input type="hidden" name="appointment_id" id="prescriptionAppointmentId">
                <input type="hidden" name="patient_id" id="prescriptionPatientId">
                
                <div class="form-group">
                    <label class="form-label">Diagnosis *</label>
                    <textarea name="diagnosis" class="form-textarea" placeholder="Enter diagnosis..." required></textarea>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Medications *</label>
                    <textarea name="medications" class="form-textarea" placeholder="Enter medications with dosage and frequency..." required style="min-height: 150px;"></textarea>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Instructions</label>
                    <textarea name="instructions" class="form-textarea" placeholder="Enter additional instructions for the patient..."></textarea>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Follow-up Date</label>
                    <input type="date" name="follow_up_date" class="form-input">
                </div>
                
                <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                    <button type="button" class="btn-secondary" onclick="closeModal('prescriptionModal')">Cancel</button>
                    <button type="submit" class="btn-primary">Generate Prescription</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Add Vitals Modal -->
    <div id="vitalsModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Add Patient Vitals</h2>
                <button class="close-modal" onclick="closeModal('vitalsModal')">&times;</button>
            </div>
            <form id="vitalsForm" onsubmit="addVitals(event)">
                <input type="hidden" name="action" value="add_vitals">
                <input type="hidden" name="patient_id" id="vitalsPatientId">
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="form-group">
                        <label class="form-label">Blood Pressure</label>
                        <input type="text" name="blood_pressure" class="form-input" placeholder="120/80">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Heart Rate (bpm)</label>
                        <input type="number" name="heart_rate" class="form-input" placeholder="72">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Temperature (°F)</label>
                        <input type="number" step="0.1" name="temperature" class="form-input" placeholder="98.6">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Weight (kg)</label>
                        <input type="number" step="0.1" name="weight" class="form-input" placeholder="70.5">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Height (cm)</label>
                        <input type="number" step="0.1" name="height" class="form-input" placeholder="175">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Recorded Date *</label>
                        <input type="date" name="recorded_date" class="form-input" required id="vitalsDate">
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Additional Notes</label>
                    <textarea name="notes" class="form-textarea" placeholder="Any additional observations..."></textarea>
                </div>
                
                <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                    <button type="button" class="btn-secondary" onclick="closeModal('vitalsModal')">Cancel</button>
                    <button type="submit" class="btn-primary">Save Vitals</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Add Medical History Modal -->
    <div id="historyModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Add Medical History</h2>
                <button class="close-modal" onclick="closeModal('historyModal')">&times;</button>
            </div>
            <form id="historyForm" onsubmit="addMedicalHistory(event)">
                <input type="hidden" name="patient_id" id="historyPatientId">
                <input type="hidden" name="action" value="add_history">
                
                <div class="form-group">
                    <label class="form-label">Condition Name *</label>
                    <input type="text" name="condition_name" class="form-input" placeholder="e.g. Hypertension" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Diagnosis Date *</label>
                    <input type="date" name="diagnosis_date" class="form-input" required id="historyDate">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-textarea" placeholder="Describe the condition..."></textarea>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Treatment Plan *</label>
                    <textarea name="treatment" class="form-textarea" placeholder="Current treatment or management plan..." required></textarea>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Status *</label>
                    <select name="status" class="form-select" required>
                        <option value="active">Active</option>
                        <option value="resolved">Resolved</option>
                        <option value="chronic">Chronic</option>
                    </select>
                </div>
                
                <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                    <button type="button" class="btn-secondary" onclick="closeModal('historyModal')">Cancel</button>
                    <button type="submit" class="btn-primary">Save History</button>
                </div>
            </form>
        </div>
    </div>

    <script src="js/main.js"></script>
    <script src="js/doctor_dashboard.js"></script>
</body>
</html>