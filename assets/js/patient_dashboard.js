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
        
        // Initialize Choices.js for select fields
        const selects = document.querySelectorAll('.form-select');
        selects.forEach(select => {
            if (!select.classList.contains('choices__input')) {
                new Choices(select, {
                    searchEnabled: false,
                    itemSelectText: '',
                    shouldSort: false,
                    position: 'bottom'
                });
            }
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
async function loadAppointments(search = '') {
    try {
        const url = `php/appointment.php?action=list${search ? `&search=${encodeURIComponent(search)}` : ''}`;
        const response = await fetch(url);
        const appointments = await response.json();
        
        const container = document.getElementById('appointmentsList');
        
        if (appointments.length === 0) {
            container.innerHTML = '<p class="text-center">No appointments found. Book your first appointment!</p>';
            return;
        }
        
        container.innerHTML = `
            <div class="data-table-wrapper">
                <div class="data-table">
                    <table id="appointmentsTable">
                        <thead>
                            <tr>
                                <th>Doctor</th>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Reason</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${appointments.map(apt => `
                                <tr class="${apt.status === 'cancelled' && apt.cancellation_reason ? 'has-cancellation' : ''}">
                                    <td data-label="Doctor:"><strong>${apt.doctor_name || '<em style="color:var(--text-light)">Not assigned yet</em>'}</strong><br><small>${apt.specialization || ''}</small></td>
                                    <td data-label="Date:">${formatDate(apt.appointment_date)}</td>
                                    <td data-label="Time:">${formatTime(apt.appointment_time)}</td>
                                    <td data-label="Reason:">${apt.reason}</td>
                                    <td data-label="Status:"><span class="badge badge-${getStatusClass(apt.status)}">${apt.status}</span></td>
                                    <td data-label="Actions:">
                                        ${apt.status === 'scheduled' ? `
                                            <button onclick="openCancellationModal(${apt.id})" class="btn-secondary" style="padding: 0.4rem 0.8rem; font-size: 0.85rem;">✕ Cancel</button>
                                        ` : (apt.status === 'cancelled' && apt.cancellation_reason ? `
                                            <div class="reason-dropdown-container">
                                                <button onclick="toggleReason(${apt.id})" class="btn-secondary" style="padding: 0.4rem 0.8rem; font-size: 0.85rem; background: rgba(244, 67, 54, 0.05); color: #D32F2F; border-color: rgba(244, 67, 54, 0.3);">
                                                    <i class="fas fa-info-circle"></i> Reason
                                                </button>
                                                <div id="reason-${apt.id}" class="reason-dropdown-content">
                                                    <div class="reason-label">📋 CANCELLATION REASON</div>
                                                    <div class="reason-text">${apt.cancellation_reason}</div>
                                                </div>
                                            </div>
                                        ` : '<span style="color: var(--text-light); font-size: 0.85rem;">No actions</span>')}
                                    </td>
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
window.loadAppointments = loadAppointments;

function toggleReason(id) {
    const content = document.getElementById(`reason-${id}`);
    const isVisible = content.classList.contains('show');
    
    // Close any other open reasons
    document.querySelectorAll('.reason-dropdown-content').forEach(el => {
        el.classList.remove('show');
    });
    
    // Toggle the current one
    if (!isVisible) {
        content.classList.add('show');
    }
}

async function loadPrescriptions(search = '') {
    try {
        const url = `php/prescription.php?patient_id=${currentPatientId}${search ? `&search=${encodeURIComponent(search)}` : ''}`;
        const response = await fetch(url);
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
window.loadPrescriptions = loadPrescriptions;

// Cancellation logic
function openCancellationModal(appointmentId) {
    document.getElementById('cancelAppointmentId').value = appointmentId;
    document.getElementById('cancellationForm').reset();
    openModal('cancellationModal');
}

async function submitCancellation(event) {
    event.preventDefault();
    
    const appointmentId = document.getElementById('cancelAppointmentId').value;
    const reason = new FormData(event.target).get('cancellation_reason');
    
    try {
        const response = await fetch('php/appointment.php', {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `id=${appointmentId}&status=cancelled&cancellation_reason=${encodeURIComponent(reason)}`
        });
        
        const result = await response.json();
        
        if (result.success) {
            showNotification('Appointment cancelled successfully', 'success');
            closeModal('cancellationModal');
            loadAppointments();
        } else {
            showNotification(result.message || 'Error cancelling appointment', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification('Server error occurred', 'error');
    }
}

// Initialize dashboard
document.addEventListener('DOMContentLoaded', () => {
    loadPatientDashboard();
});
