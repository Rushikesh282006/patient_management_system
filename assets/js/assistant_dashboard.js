let allAppointments = [];
let allDoctorsList = [];

// Load assistant dashboard data
async function loadAssistantDashboard() {
    try {
        // Load doctors list
        const doctorsResponse = await fetch('php/appointment.php?action=doctors');
        allDoctorsList = await doctorsResponse.json();
        
        const doctorSelect = document.getElementById('doctorSelect');
        allDoctorsList.forEach(doctor => {
            const option = document.createElement('option');
            option.value = doctor.id;
            option.textContent = `Dr. ${doctor.full_name} - ${doctor.specialization}`;
            doctorSelect.appendChild(option);
        });
        
        // Load patients list
        const patientsResponse = await fetch('php/appointment.php?action=patients');
        const patients = await patientsResponse.json();
        
        const patientSelect = document.getElementById('patientSelect');
        const tipPatientSelect = document.getElementById('tipPatientSelect');
        
        patients.forEach(patient => {
            const option1 = document.createElement('option');
            option1.value = patient.id;
            option1.textContent = patient.full_name;
            patientSelect.appendChild(option1);
            
            const option2 = document.createElement('option');
            option2.value = patient.id;
            option2.textContent = patient.full_name;
            tipPatientSelect.appendChild(option2);
        });
        
        // Initialize Choices.js for select fields
        const selects = document.querySelectorAll('.form-select');
        selects.forEach(select => {
            if (select.choicesInstance) {
                select.choicesInstance.destroy();
            }
            select.choicesInstance = new Choices(select, {
                searchEnabled: false,
                itemSelectText: '',
                shouldSort: false,
                position: 'bottom'
            });
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

// Override loadAppointments for assistant view
async function loadAppointments(search = '') {
    try {
        const status = document.getElementById('filterStatus') ? document.getElementById('filterStatus').value : '';
        const url = `php/appointment.php?action=list${search ? `&search=${encodeURIComponent(search)}` : ''}${status ? `&status=${status}` : ''}`;
        const response = await fetch(url);
        allAppointments = await response.json();
        
        displayAppointments(allAppointments);
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
                            <tr>
                                <td data-label="Patient:"><strong>${apt.patient_name}</strong><br><small>${apt.patient_phone}</small></td>
                                <td data-label="Doctor:"><strong>${apt.doctor_name || '<em style="color:var(--text-light)">Not assigned yet</em>'}</strong><br><small>${apt.specialization || ''}</small></td>
                                <td data-label="Date:">${formatDate(apt.appointment_date)}</td>
                                <td data-label="Time:">${formatTime(apt.appointment_time)}</td>
                                <td data-label="Reason:">${apt.reason}</td>
                                <td data-label="Status:"><span class="badge badge-${getStatusClass(apt.status)}">${apt.status}</span></td>
                                <td data-label="Actions:">
                                    ${apt.status === 'scheduled' ? `
                                        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                                            <button onclick="openEditModal(${apt.id})" class="btn-primary" style="padding: 0.4rem 0.8rem; font-size: 0.85rem; background: var(--primary);">✏️ Edit</button>
                                            <button onclick="openCancellationModal(${apt.id})" class="btn-secondary" style="padding: 0.4rem 0.8rem; font-size: 0.85rem;">✕ Cancel</button>
                                        </div>
                                    ` : ''}
                                </td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            </div>
        </div>
    `;
}

function filterAppointmentsByStatus() {
    loadAppointments();
}

function openCancellationModal(appointmentId) {
    document.getElementById('cancelAppointmentId').value = appointmentId;
    document.getElementById('cancellationForm').reset();
    openModal('cancellationModal');
}

function openEditModal(appointmentId) {
    const apt = allAppointments.find(a => a.id == appointmentId);
    if (!apt) return;

    // Set hidden id
    document.getElementById('editAppointmentId').value = apt.id;

    // Populate doctor dropdown fresh
    const editDoctorSelect = document.getElementById('editDoctorSelect');
    if (editDoctorSelect.choicesInstance) {
        editDoctorSelect.choicesInstance.destroy();
    }
    editDoctorSelect.innerHTML = '<option value="">Choose a doctor...</option>';
    allDoctorsList.forEach(doctor => {
        const option = document.createElement('option');
        option.value = doctor.id;
        option.textContent = `Dr. ${doctor.full_name} - ${doctor.specialization}`;
        if (doctor.id == apt.doctor_id) option.selected = true;
        editDoctorSelect.appendChild(option);
    });
    editDoctorSelect.choicesInstance = new Choices(editDoctorSelect, {
        searchEnabled: false,
        itemSelectText: '',
        shouldSort: false,
        position: 'bottom'
    });

    // Pre-fill date
    const dateInput = document.querySelector('.edit-date-picker');
    if (dateInput._flatpickr) {
        dateInput._flatpickr.setDate(apt.appointment_date);
    } else {
        dateInput.value = apt.appointment_date;
    }

    // Pre-fill time
    const timeInput = document.querySelector('.edit-time-picker');
    if (timeInput._flatpickr) {
        timeInput._flatpickr.setDate(apt.appointment_time, false, 'H:i');
    } else {
        timeInput.value = apt.appointment_time;
    }

    // Pre-fill reason
    document.getElementById('editReason').value = apt.reason;

    openModal('editAppointmentModal');
}

async function submitEditAppointment(event) {
    event.preventDefault();
    const formData = new FormData(event.target);
    const appointmentId = formData.get('appointment_id');
    const doctorId = formData.get('doctor_id');
    const date = formData.get('appointment_date');
    const time = formData.get('appointment_time');
    const reason = formData.get('reason');

    try {
        const response = await fetch('php/appointment.php', {
            method: 'PUT',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `id=${appointmentId}&doctor_id=${doctorId}&appointment_date=${encodeURIComponent(date)}&appointment_time=${encodeURIComponent(time)}&reason=${encodeURIComponent(reason)}&status=scheduled`
        });
        const result = await response.json();
        if (result.success) {
            showNotification('Appointment updated successfully!', 'success');
            closeModal('editAppointmentModal');
            loadAppointments();
        } else {
            showNotification(result.message || 'Error updating appointment', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification('Server error occurred', 'error');
    }
}

async function submitCancellation(event) {
    event.preventDefault();
    
    const appointmentId = document.getElementById('cancelAppointmentId').value;
    const reason = new FormData(event.target).get('cancellation_reason');
    
    await updateAppointmentStatus(appointmentId, 'cancelled', reason);
    closeModal('cancellationModal');
}

async function updateAppointmentStatus(appointmentId, status, reason = '') {
    try {
        const response = await fetch('php/appointment.php', {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `id=${appointmentId}&status=${status}&cancellation_reason=${encodeURIComponent(reason)}`
        });
        
        const result = await response.json();
        
        if (result.success) {
            showNotification('Appointment ' + status + ' successfully', 'success');
            loadAppointments();
        } else {
            showNotification(result.message || 'Error updating appointment', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification('Server error occurred', 'error');
    }
}

async function addHealthTip(event) {
    event.preventDefault();
    
    const formData = new FormData(event.target);
    
    try {
        const response = await fetch('php/health_tips.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            showNotification('Health tip added successfully!', 'success');
            closeModal('addHealthTipModal');
            event.target.reset();
        } else {
            showNotification(result.message || 'Error adding health tip', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification('Server error occurred', 'error');
    }
}

// Initialize dashboard
document.addEventListener('DOMContentLoaded', () => {
    loadAssistantDashboard();
});
