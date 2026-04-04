let currentPatientId = null;

// Tab switching
function switchTab(tabName) {
    // Hide all content
    document.querySelectorAll('.tab-content').forEach(content => {
        content.classList.remove('active');
        content.style.display = 'none';
    });
    
    // Remove active from all tabs
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    
    // Show selected content
    document.getElementById(tabName + 'Content').classList.add('active');
    document.getElementById(tabName + 'Content').style.display = 'block';
    document.getElementById(tabName + 'Tab').classList.add('active');
    
    // Load data for the tab
    switch(tabName) {
        case 'history':
            if (currentPatientId) loadMedicalHistory(currentPatientId);
            break;
        case 'prescriptions':
            if (currentPatientId) loadPrescriptions();
            break;
        case 'tips':
            loadHealthTips();
            break;
    }
}

// Load patient dashboard data
async function loadPatientDashboard() {
    try {
        // Fetch actual session ID from PHP injected variable
        currentPatientId = typeof sessionUserId !== 'undefined' && sessionUserId !== null ? sessionUserId : 4;
        document.getElementById('currentPatientId').value = currentPatientId;
        
        // Load doctors list
        const doctorsResponse = await fetch('php/appointment.php?action=doctors');
        const doctors = await doctorsResponse.json();
        
        const doctorSelect = document.getElementById('doctorSelect');
        doctors.forEach(doctor => {
            const option = document.createElement('option');
            option.value = doctor.id;
            option.textContent = `Dr. ${doctor.full_name} - ${doctor.specialization}`;
            doctorSelect.appendChild(option);
        });
        
        // Set minimum date to today
        const today = new Date().toISOString().split('T')[0];
        document.querySelector('input[name="appointment_date"]').min = today;
        
        // Load appointments
        loadAppointments();
    } catch (error) {
        console.error('Error loading dashboard:', error);
    }
}

// Override loadAppointments to display in patient dashboard
async function loadAppointments() {
    try {
        const response = await fetch('php/appointment.php?action=list');
        const appointments = await response.json();
        
        const container = document.getElementById('appointmentsList');
        
        if (appointments.length === 0) {
            container.innerHTML = '<p class="text-center">No appointments found. Book your first appointment!</p>';
            return;
        }
        
        container.innerHTML = `
            <div class="data-table-wrapper">
                <div class="data-table">
                    <table>
                        <thead>
                            <tr>
                                <th>Doctor</th>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Reason</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${appointments.map(apt => `
                                <tr>
                                    <td data-label="Doctor:"><strong>${apt.doctor_name}</strong><br><small>${apt.specialization}</small></td>
                                    <td data-label="Date:">${formatDate(apt.appointment_date)}</td>
                                    <td data-label="Time:">${formatTime(apt.appointment_time)}</td>
                                    <td data-label="Reason:">
                                        ${apt.reason}
                                        ${apt.status === 'cancelled' && apt.cancellation_reason ? `
                                            <div style="margin-top: 0.8rem; padding: 0.8rem; background: rgba(244, 67, 54, 0.05); border-left: 3px solid #D32F2F; border-radius: 4px;">
                                                <small style="color: #D32F2F; display: block; margin-bottom: 2px;"><strong>Cancellation Reason:</strong></small>
                                                <span style="font-size: 0.9rem; color: #1A1A2E;">${apt.cancellation_reason}</span>
                                            </div>
                                        ` : ''}
                                    </td>
                                    <td data-label="Status:"><span class="badge badge-${getStatusClass(apt.status)}">${apt.status}</span></td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                </div>
            </div>
        `;
    } catch (error) {
        console.error('Error loading appointments:', error);
    }
}

async function loadPrescriptions() {
    try {
        const response = await fetch(`php/prescription.php?patient_id=${currentPatientId}`);
        const prescriptions = await response.json();
        
        const container = document.getElementById('prescriptionsList');
        
        if (prescriptions.length === 0) {
            container.innerHTML = '<p>No prescriptions found</p>';
            return;
        }
        
        container.innerHTML = prescriptions.map(presc => `
            <div class="dashboard-card" style="margin-bottom: 1.5rem;">
                <div class="card-header">
                    <div>
                        <h3 style="color: var(--primary); margin-bottom: 0.5rem;">Prescription from Dr. ${presc.doctor_name}</h3>
                        <p style="color: var(--text-light);">${presc.specialization} • ${formatDate(presc.prescription_date)}</p>
                    </div>
                    <button onclick="printPrescription(${presc.id})" class="btn-primary" style="padding: 0.7rem 1.5rem;">
                        <i class="fas fa-print"></i> Print
                    </button>
                </div>
                <div style="margin-top: 1rem;">
                    <p><strong>Diagnosis:</strong> ${presc.diagnosis}</p>
                    <p style="margin-top: 0.5rem;"><strong>Medications:</strong></p>
                    <div style="background: var(--secondary); padding: 1rem; border-radius: 12px; margin-top: 0.5rem; white-space: pre-wrap;">${presc.medications}</div>
                    ${presc.follow_up_date ? `<p style="margin-top: 0.5rem;"><strong>Follow-up:</strong> ${formatDate(presc.follow_up_date)}</p>` : ''}
                </div>
            </div>
        `).join('');
    } catch (error) {
        console.error('Error loading prescriptions:', error);
    }
}

// Initialize dashboard
document.addEventListener('DOMContentLoaded', () => {
    loadPatientDashboard();
});
