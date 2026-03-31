let allAppointments = [];

// Load assistant dashboard data
async function loadAssistantDashboard() {
    try {
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
async function loadAppointments() {
    try {
        const response = await fetch('php/appointment.php?action=list');
        allAppointments = await response.json();
        
        displayAppointments(allAppointments);
    } catch (error) {
        console.error('Error loading appointments:', error);
    }
}

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
                                <td><strong>${apt.patient_name}</strong><br><small>${apt.patient_phone}</small></td>
                                <td><strong>${apt.doctor_name}</strong><br><small>${apt.specialization}</small></td>
                                <td>${formatDate(apt.appointment_date)}</td>
                                <td>${formatTime(apt.appointment_time)}</td>
                                <td>${apt.reason}</td>
                                <td><span class="badge badge-${getStatusClass(apt.status)}">${apt.status}</span></td>
                                <td>
                                    ${apt.status === 'scheduled' ? `
                                        <button onclick="updateAppointmentStatus(${apt.id}, 'cancelled')" class="btn-secondary" style="padding: 0.4rem 0.8rem; font-size: 0.85rem;">Cancel</button>
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
    const status = document.getElementById('filterStatus').value;
    
    if (status === '') {
        displayAppointments(allAppointments);
    } else {
        const filtered = allAppointments.filter(apt => apt.status === status);
        displayAppointments(filtered);
    }
}

async function updateAppointmentStatus(appointmentId, status) {
    if (!confirm('Are you sure you want to ' + status + ' this appointment?')) {
        return;
    }
    
    try {
        const response = await fetch('php/appointment.php', {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `id=${appointmentId}&status=${status}`
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
