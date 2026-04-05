// Medical Appointment System - JavaScript

// Cache elements to prevent repetitive DOM queries
const parallaxElements = document.querySelectorAll('.parallax-element');
let paramMouseX = 0;
let paramMouseY = 0;
let isMouseMoving = false;

// Optimize Parallax Effect with requestAnimationFrame
document.addEventListener('mousemove', (e) => {
    paramMouseX = e.pageX;
    paramMouseY = e.pageY;
    if (!isMouseMoving && parallaxElements.length > 0) {
        window.requestAnimationFrame(updateMouseParallax);
        isMouseMoving = true;
    }
});

function updateMouseParallax() {
    parallaxElements.forEach((element) => {
        // Prevent jank by doing hardware accelerated translates
        const speed = element.getAttribute('data-speed') || 5;
        const x = (window.innerWidth - paramMouseX * speed) / 100;
        const y = (window.innerHeight - paramMouseY * speed) / 100;
        
        element.style.transform = `translate3d(${x}px, ${y}px, 0)`;
    });
    isMouseMoving = false;
}

// Optimize Scroll Events with requestAnimationFrame
const navbar = document.querySelector('.navbar');
const parallaxBgs = document.querySelectorAll('.parallax-bg');
let isScrolling = false;

window.addEventListener('scroll', () => {
    if (!isScrolling) {
        window.requestAnimationFrame(updateScrollEvents);
        isScrolling = true;
    }
});

function updateScrollEvents() {
    const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
    
    // Navbar scroll effect
    if (navbar) {
        if (scrollTop > 50) {
            navbar.classList.add('scrolled');
        } else {
            navbar.classList.remove('scrolled');
        }
    }
    
    // Parallax background
    parallaxBgs.forEach((bg) => {
        const speed = 0.5;
        bg.style.transform = `translate3d(0, ${scrollTop * speed}px, 0)`;
    });
    
    isScrolling = false;
}

// Smooth Scrolling
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function(e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            target.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }
    });
});

// Intersection Observer for Animations
const observerOptions = {
    threshold: 0.2,
    rootMargin: '0px 0px -100px 0px'
};

const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.style.animation = 'fadeInUp 0.8s ease-out forwards';
            observer.unobserve(entry.target);
        }
    });
}, observerOptions);

document.querySelectorAll('.feature-card, .dashboard-card').forEach((el) => {
    observer.observe(el);
});

// Modal Functions
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('active');
        document.body.style.overflow = 'auto';
    }
}

// Close modal on outside click
document.querySelectorAll('.modal').forEach(modal => {
    modal.addEventListener('click', (e) => {
        if (e.target === modal) {
            closeModal(modal.id);
        }
    });
});

// Enhanced Form Validation
function showError(input, msg) {
    input.classList.add("input-error");
    let hint = input.parentElement.querySelector(".field-error-msg");
    if (!hint) {
        hint = document.createElement("span");
        hint.className = "field-error-msg";
        hint.style.cssText = "display:block;color:#D32F2F;font-size:0.82rem;margin-top:0.3rem;";
        input.parentElement.appendChild(hint);
    }
    hint.textContent = msg;
}

function clearError(input) {
    input.classList.remove("input-error");
    const hint = input.parentElement.querySelector(".field-error-msg");
    if (hint) hint.textContent = "";
}

function clearAllErrors(formId) {
    const form = document.getElementById(formId);
    if (!form) return;
    form.querySelectorAll(".input-error").forEach((el) => el.classList.remove("input-error"));
    form.querySelectorAll(".field-error-msg").forEach((el) => (el.textContent = ""));
}

function validateForm(formId) {
    const form = document.getElementById(formId);
    if (!form) return false;
    
    clearAllErrors(formId);
    const inputs = form.querySelectorAll('input, select, textarea');
    let isValid = true;
    
    inputs.forEach(input => {
        const val = input.value.trim();
        const label = input.parentElement.querySelector('.form-label');
        const fieldName = label ? label.textContent.replace('*', '').trim() : 'This field';

        // Required check
        if (input.hasAttribute('required') && (!val || val === "")) {
            showError(input, `${fieldName} is required.`);
            isValid = false;
            return;
        }

        // Numeric validation
        if (input.type === 'number' && val !== "") {
            const num = parseFloat(val);
            if (isNaN(num)) {
                showError(input, `${fieldName} must be a valid number.`);
                isValid = false;
            } else if (input.name === 'heart_rate' && (num < 30 || num > 250)) {
                showError(input, "Heart rate should be between 30 and 250 bpm.");
                isValid = false;
            } else if (input.name === 'temperature' && (num < 90 || num > 110)) {
                showError(input, "Temperature should be between 90 and 110 °F.");
                isValid = false;
            } else if (num < 0) {
                showError(input, `${fieldName} cannot be negative.`);
                isValid = false;
            }
        }

        // Blood Pressure pattern check
        if (input.name === 'blood_pressure' && val !== "") {
            const bpPattern = /^\d{2,3}\/\d{2,3}$/;
            if (!bpPattern.test(val)) {
                showError(input, "Format should be like 120/80.");
                isValid = false;
            }
        }
    });
    
    return isValid;
}

// Appointment Creation
async function createAppointment(event) {
    event.preventDefault();
    
    if (!validateForm('appointmentForm')) {
        showNotification('Please fill all required fields', 'error');
        return;
    }
    
    const formData = new FormData(event.target);
    
    try {
        const response = await fetch('php/appointment.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            showNotification('Appointment created successfully!', 'success');
            closeModal('appointmentModal');
            event.target.reset();
            loadAppointments();
        } else {
            showNotification(result.message || 'Error creating appointment', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification('Server error occurred', 'error');
    }
}

// Load Appointments
async function loadAppointments() {
    try {
        const response = await fetch('php/appointment.php?action=list');
        const appointments = await response.json();
        
        const container = document.getElementById('appointmentsList');
        if (!container) return;
        
        if (appointments.length === 0) {
            container.innerHTML = '<p class="text-center">No appointments found</p>';
            return;
        }
        
        container.innerHTML = `
            <div class="data-table-wrapper">
                <div class="data-table">
                    <table>
                        <thead>
                            <tr>
                                <th>Patient</th>
                                <th>Doctor</th>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${appointments.map(apt => `
                                <tr>
                                    <td>${apt.patient_name}</td>
                                    <td>${apt.doctor_name}</td>
                                    <td>${formatDate(apt.appointment_date)}</td>
                                    <td>${formatTime(apt.appointment_time)}</td>
                                    <td><span class="badge badge-${getStatusClass(apt.status)}">${apt.status}</span></td>
                                    <td>
                                        <button onclick="viewAppointment(${apt.id})" class="btn-secondary" style="padding: 0.5rem 1rem; font-size: 0.9rem;">View</button>
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

// View Appointment Details
async function viewAppointment(id) {
    try {
        const response = await fetch(`php/appointment.php?id=${id}`);
        const apt = await response.json();
        
        if (apt.success === false) {
            showNotification(apt.message, 'error');
            return;
        }
        
        // You can implement a modal here to show details
        // For now, we'll show an alert with details
        alert(`
            Patient: ${apt.patient_name}
            Doctor: ${apt.doctor_name}
            Date: ${formatDate(apt.appointment_date)}
            Reason: ${apt.reason}
        `);
    } catch (error) {
        console.error('Error viewing appointment:', error);
    }
}

// Load Medical History
async function loadMedicalHistory(patientId) {
    try {
        const response = await fetch(`php/medical_his.php?patient_id=${patientId}`);
        const history = await response.json();
        
        const container = document.getElementById('medicalHistoryList');
        if (!container) return;
        
        if (history.length === 0) {
            container.innerHTML = '<p>No medical history found</p>';
            return;
        }
        
        container.innerHTML = history.map(record => `
            <div class="dashboard-card" style="margin-bottom: 1rem;">
                <div class="card-header">
                    <h3>${record.condition_name}</h3>
                    <span class="badge badge-${record.status === 'active' ? 'warning' : 'success'}">${record.status}</span>
                </div>
                <p><strong>Diagnosis Date:</strong> ${formatDate(record.diagnosis_date)}</p>
                <p><strong>Description:</strong> ${record.description}</p>
                <p><strong>Treatment:</strong> ${record.treatment}</p>
                ${record.doctor_name ? `<p><strong>Doctor:</strong> ${record.doctor_name}</p>` : ''}
            </div>
        `).join('');
    } catch (error) {
        console.error('Error loading medical history:', error);
    }
}

// Generate Prescription
async function generatePrescription(event) {
    event.preventDefault();
    
    if (!validateForm('prescriptionForm')) {
        showNotification('Please fill all required fields', 'error');
        return;
    }
    
    const formData = new FormData(event.target);
    
    try {
        const response = await fetch('php/prescription.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            showNotification('Prescription generated successfully!', 'success');
            closeModal('prescriptionModal');
            event.target.reset();
            
            // Open print dialog
            if (result.prescription_id) {
                printPrescription(result.prescription_id);
            }
        } else {
            showNotification(result.message || 'Error generating prescription', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification('Server error occurred', 'error');
    }
}

// Print Prescription
function printPrescription(prescriptionId) {
    window.open(`php/print_pres.php?id=${prescriptionId}`, '_blank');
}

// Load Health Tips
async function loadHealthTips() {
    try {
        const response = await fetch('php/health_tips.php');
        const tips = await response.json();
        
        const container = document.getElementById('healthTipsList');
        if (!container) return;
        
        if (tips.length === 0) {
            container.innerHTML = '<p>No health tips available</p>';
            return;
        }
        
        container.innerHTML = tips.map(tip => `
            <div class="tip-card">
                <span class="tip-priority">${tip.priority}</span>
                <h4>${tip.tip_title}</h4>
                <p>${tip.tip_content}</p>
                <small style="opacity: 0.8;">${tip.tip_category}</small>
            </div>
        `).join('');
    } catch (error) {
        console.error('Error loading health tips:', error);
    }
}

// Notification System
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.style.cssText = `
        position: fixed;
        top: 100px;
        right: 2rem;
        padding: 1.5rem 2rem;
        background: ${type === 'success' ? '#00DFA2' : type === 'error' ? '#D32F2F' : '#088395'};
        color: white;
        border-radius: 12px;
        box-shadow: 0 8px 24px rgba(0,0,0,0.2);
        z-index: 3000;
        animation: slideIn 0.4s ease-out;
        max-width: 400px;
    `;
    notification.textContent = message;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.style.animation = 'slideOut 0.4s ease-out';
        setTimeout(() => notification.remove(), 400);
    }, 3000);
}

// Utility Functions
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-IN', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
}

function formatTime(timeString) {
    const [hours, minutes] = timeString.split(':');
    const hour = parseInt(hours);
    const ampm = hour >= 12 ? 'PM' : 'AM';
    const displayHour = hour % 12 || 12;
    return `${displayHour}:${minutes} ${ampm}`;
}

function getStatusClass(status) {
    switch(status) {
        case 'completed': return 'success';
        case 'scheduled': return 'warning';
        case 'cancelled': return 'danger';
        default: return 'warning';
    }
}

// Add CSS for notifications
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from {
            transform: translateX(400px);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    @keyframes slideOut {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(400px);
            opacity: 0;
        }
    }
`;
document.head.appendChild(style);

// Initialize on page load
document.addEventListener('DOMContentLoaded', () => {
    // Load initial data based on page
    if (document.getElementById('appointmentsList')) {
        loadAppointments();
    }
    
    if (document.getElementById('healthTipsList')) {
        loadHealthTips();
    }

    // Real-time error clearing for all forms
    document.querySelectorAll('form').forEach(form => {
        form.querySelectorAll(".form-input, .form-select, .form-textarea").forEach((input) => {
            ["input", "change"].forEach((evt) =>
                input.addEventListener(evt, () => clearError(input))
            );
        });
    });
});

let searchDebounceTimer;

function searchTable(inputId, tableId) {
    const query = document.getElementById(inputId).value;
    
    clearTimeout(searchDebounceTimer);
    searchDebounceTimer = setTimeout(() => {
        // Find which loader function to call based on tableId
        if (tableId === 'appointmentsTable') {
            if (typeof window.loadAppointments === 'function') {
                window.loadAppointments(query);
            }
        } else if (tableId === 'patientsTable') {
            if (typeof window.loadPatientsList === 'function') {
                window.loadPatientsList(query);
            }
        } else if (tableId === 'prescriptionsTable') {
            if (typeof window.loadPrescriptions === 'function') {
                window.loadPrescriptions(query);
            }
        }
        
        // If it's a static table or the loader isn't available, fall back to basic filtering
        else {
            const filter = query.toUpperCase();
            const table = document.getElementById(tableId);
            const tr = table.getElementsByTagName('tr');
            
            for (let i = 1; i < tr.length; i++) {
                let found = false;
                const td = tr[i].getElementsByTagName('td');
                for (let j = 0; j < td.length; j++) {
                    if (td[j]) {
                        const txtValue = td[j].textContent || td[j].innerText;
                        if (txtValue.toUpperCase().indexOf(filter) > -1) {
                            found = true;
                            break;
                        }
                    }
                }
                tr[i].style.display = found ? '' : 'none';
            }
        }
    }, 300);
}

// Add Vitals
async function addVitals(event) {
    event.preventDefault();
    if (!validateForm('vitalsForm')) {
        showNotification('Please fill all required fields', 'error');
        return;
    }
    const formData = new FormData(event.target);
    try {
        const response = await fetch('php/medical_his.php', {
            method: 'POST',
            body: formData
        });
        const result = await response.json();
        if (result.success) {
            showNotification('Vitals saved successfully!', 'success');
            closeModal('vitalsModal');
            event.target.reset();
            // Refresh patient view modal
            viewPatientDetails(formData.get('patient_id'));
        } else {
            showNotification(result.message || 'Error saving vitals', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification('Server error occurred', 'error');
    }
}

// Add Medical History
async function addMedicalHistory(event) {
    event.preventDefault();
    if (!validateForm('historyForm')) {
        showNotification('Please fill all required fields', 'error');
        return;
    }
    const formData = new FormData(event.target);
    try {
        const response = await fetch('php/medical_his.php', {
            method: 'POST',
            body: formData
        });
        const result = await response.json();
        if (result.success) {
            showNotification('Medical history saved successfully!', 'success');
            closeModal('historyModal');
            event.target.reset();
            // Refresh patient view modal
            viewPatientDetails(formData.get('patient_id'));
        } else {
            showNotification(result.message || 'Error saving history', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification('Server error occurred', 'error');
    }
}

// Mobile Menu Toggle
function toggleMenu() {
    const navLinks = document.getElementById('navLinks');
    if (navLinks) {
        navLinks.classList.toggle('active');
    }
}

// Export functions for global use
window.openModal = openModal;
window.closeModal = closeModal;
window.createAppointment = createAppointment;
window.viewAppointment = viewAppointment;
window.generatePrescription = generatePrescription;
window.loadAppointments = loadAppointments;
window.loadMedicalHistory = loadMedicalHistory;
window.loadHealthTips = loadHealthTips;
window.printPrescription = printPrescription;
window.searchTable = searchTable;
window.showNotification = showNotification;
window.addVitals = addVitals;
window.addMedicalHistory = addMedicalHistory;
window.toggleMenu = toggleMenu;