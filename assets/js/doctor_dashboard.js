// Load doctor dashboard data
async function loadDoctorDashboard() {
    try {
        await loadAppointments();
        
        // Stats
        const response = await fetch('php/appointment.php?action=list');
        const appointments = await response.json();
        
        // Calculate today's appointments
        const today = new Date().toISOString().split('T')[0];
        const todayAppts = appointments.filter(apt => apt.appointment_date === today && apt.status === 'scheduled');
        document.getElementById('todayAppointments').textContent = todayAppts.length;
        
        // Get unique patients
        const uniquePatients = [...new Set(appointments.map(apt => apt.patient_id))];
        document.getElementById('totalPatients').textContent = uniquePatients.length;
        
        // Get prescriptions count
        const prescResponse = await fetch('php/prescription.php?action=doctor_prescriptions');
        const prescriptions = await prescResponse.json();
        document.getElementById('totalPrescriptions').textContent = prescriptions.length;
    } catch (error) {
        console.error('Error loading dashboard:', error);
    }
}

// Separate loadAppointments to allow AJAX search
async function loadAppointments(search = '') {
    try {
        const status = document.getElementById('filterStatus') ? document.getElementById('filterStatus').value : '';
        const url = `php/appointment.php?action=list${search ? `&search=${encodeURIComponent(search)}` : ''}${status ? `&status=${status}` : ''}`;
        const response = await fetch(url);
        const appointments = await response.json();
        displayAppointments(appointments);
    } catch (error) {
        console.error('Error loading appointments:', error);
    }
}
window.loadAppointments = loadAppointments;

function displayAppointments(appointments) {
    const container = document.getElementById('appointmentsList');
    
    if (appointments.length === 0) {
        container.innerHTML = '<p class="text-center">No appointments found</p>';
        return;
    }
    
    container.innerHTML = `
        <div class="data-table-wrapper">
            <div class="data-table">
                <table id="appointmentsTable">
                    <thead>
                        <tr>
                            <th>Patient</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Reason</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${appointments.map(apt => `
                            <tr>
                                <td data-label="Patient:"><strong>${apt.patient_name}</strong><br><small>${apt.patient_phone}</small></td>
                                <td data-label="Date:">${formatDate(apt.appointment_date)}</td>
                                <td data-label="Time:">${formatTime(apt.appointment_time)}</td>
                                <td data-label="Reason:">${apt.reason}</td>
                                <td data-label="Status:"><span class="badge badge-${getStatusClass(apt.status)}">${apt.status}</span></td>
                                <td data-label="Actions:">
                                    <button onclick="viewPatientDetails(${apt.patient_id})" class="btn-secondary" style="padding: 0.5rem 1rem; font-size: 0.9rem; margin-right: 0.5rem;">View Patient</button>
                                    ${apt.status === 'scheduled' ? `<button onclick="openPrescriptionModal(${apt.id}, ${apt.patient_id})" class="btn-primary" style="padding: 0.5rem 1rem; font-size: 0.9rem;">Prescribe</button>` : ''}
                                </td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            </div>
        </div>
    `;
}

async function viewPatientDetails(patientId) {
    try {
        // Get patient summary
        const response = await fetch(`php/medical_his.php?action=summary&patient_id=${patientId}`);
        const summary = await response.json();
        
        if (!summary || summary.success === false || !summary.patient) {
            showNotification('Could not load patient details. Patient may not exist.', 'error');
            return;
        }

        const patient = summary.patient;
        const conditions = summary.active_conditions || [];
        const vitals = summary.latest_vitals;
        
        const detailsHtml = `
            <div class="dashboard-grid" style="margin-bottom: 2rem;">
                <div class="dashboard-card">
                    <h3 style="color: var(--primary); margin-bottom: 1rem;">Patient Information</h3>
                    <p><strong>Name:</strong> ${patient.full_name}</p>
                    <p><strong>Email:</strong> ${patient.email}</p>
                    <p><strong>Phone:</strong> ${patient.phone}</p>
                    <p><strong>Address:</strong> ${patient.address || 'N/A'}</p>
                </div>
                
                ${vitals ? `
                <div class="dashboard-card">
                    <h3 style="color: var(--primary); margin-bottom: 1rem;">Latest Vitals</h3>
                    <p><strong>BP:</strong> ${vitals.blood_pressure || 'N/A'}</p>
                    <p><strong>Heart Rate:</strong> ${vitals.heart_rate ? vitals.heart_rate + ' bpm' : 'N/A'}</p>
                    <p><strong>Temperature:</strong> ${vitals.temperature ? vitals.temperature + '°F' : 'N/A'}</p>
                    <p><strong>Weight:</strong> ${vitals.weight ? vitals.weight + ' kg' : 'N/A'}</p>
                </div>
                ` : ''}
            </div>
            
            <div style="display: flex; gap: 1rem; margin-bottom: 2rem;">
                <button class="btn-primary" id="btnAddVitals" data-patient-id="${patient.id}" style="padding: 0.8rem 1.5rem;"><i class="fas fa-heartbeat"></i> Add Vitals</button>
                <button class="btn-primary" id="btnAddHistory" data-patient-id="${patient.id}" style="padding: 0.8rem 1.5rem;"><i class="fas fa-notes-medical"></i> Add Medical History</button>
            </div>

            <div class="dashboard-card">
                <h3 style="color: var(--primary); margin-bottom: 1rem;">Active Medical Conditions</h3>
                ${conditions.length > 0 ? conditions.map(c => `
                    <div style="padding: 1rem; background: var(--secondary); border-radius: 12px; margin-bottom: 1rem;">
                        <h4 style="color: var(--text-dark);">${c.condition_name}</h4>
                        <p><strong>Diagnosed:</strong> ${formatDate(c.diagnosis_date)}</p>
                        <p><strong>Description:</strong> ${c.description}</p>
                        <p><strong>Treatment:</strong> ${c.treatment}</p>
                    </div>
                `).join('') : '<p>No active medical conditions</p>'}
            </div>
        `;
        
        document.getElementById('patientDetails').innerHTML = detailsHtml;
        openModal('patientModal');
    } catch (error) {
        console.error('Error loading patient details:', error);
        showNotification('Error loading patient details', 'error');
    }
}

function openPrescriptionModal(appointmentId, patientId) {
    document.getElementById('prescriptionAppointmentId').value = appointmentId;
    document.getElementById('prescriptionPatientId').value = patientId;
    openModal('prescriptionModal');
}

// Using event delegation for dynamic buttons inside the modal
document.addEventListener('click', (e) => {
    const target = e.target.closest('button');
    if (!target) return;
    
    // Check if it's the Add Vitals button
    if (target.id === 'btnAddVitals' || target.innerText.includes('Add Vitals')) {
        const patientId = target.getAttribute('data-patient-id');
        if (patientId) openVitalsModal(patientId);
    }
    
    // Check if it's the Add History button
    if (target.id === 'btnAddHistory' || target.innerText.includes('Add Medical History')) {
        const patientId = target.getAttribute('data-patient-id');
        if (patientId) openHistoryModal(patientId);
    }
});

function openVitalsModal(patientId) {
    try {
        closeModal('patientModal');
        const idInput = document.getElementById('vitalsPatientId');
        if (idInput) idInput.value = patientId;
        const dateInput = document.getElementById('vitalsDate');
        if (dateInput) {
            dateInput.value = new Date().toISOString().split('T')[0];
            if (dateInput._flatpickr) dateInput._flatpickr.setDate(dateInput.value);
        }
        openModal('vitalsModal');
    } catch (err) {
        openModal('vitalsModal');
    }
}

function openHistoryModal(patientId) {
    try {
        closeModal('patientModal');
        const idInput = document.getElementById('historyPatientId');
        if (idInput) idInput.value = patientId;
        const dateInput = document.getElementById('historyDate');
        if (dateInput) {
            dateInput.value = new Date().toISOString().split('T')[0];
            if (dateInput._flatpickr) dateInput._flatpickr.setDate(dateInput.value);
        }
        openModal('historyModal');
    } catch (err) {
        openModal('historyModal');
    }
}

// Initialize dashboard
document.addEventListener('DOMContentLoaded', () => {
    loadDoctorDashboard();
});
