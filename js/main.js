// Medical Appointment System - JavaScript

// Parallax Effect
document.addEventListener('mousemove', (e) => {
    const parallaxElements = document.querySelectorAll('.parallax-element');
    const mouseX = e.clientX / window.innerWidth;
    const mouseY = e.clientY / window.innerHeight;
    
    parallaxElements.forEach((element) => {
        const speed = element.getAttribute('data-speed') || 5;
        const x = (window.innerWidth - e.pageX * speed) / 100;
        const y = (window.innerHeight - e.pageY * speed) / 100;
        
        element.style.transform = `translateX(${x}px) translateY(${y}px)`;
    });
});

// Scroll Parallax
let lastScrollTop = 0;
window.addEventListener('scroll', () => {
    const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
    const navbar = document.querySelector('.navbar');
    
    // Navbar scroll effect
    if (scrollTop > 50) {
        navbar.classList.add('scrolled');
    } else {
        navbar.classList.remove('scrolled');
    }
    
    // Parallax background
    const parallaxBg = document.querySelectorAll('.parallax-bg');
    parallaxBg.forEach((bg) => {
        const speed = 0.5;
        bg.style.transform = `translateY(${scrollTop * speed}px)`;
    });
    
    lastScrollTop = scrollTop;
});

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

// Form Validation
function validateForm(formId) {
    const form = document.getElementById(formId);
    if (!form) return false;
    
    const inputs = form.querySelectorAll('[required]');
    let isValid = true;
    
    inputs.forEach(input => {
        if (!input.value.trim()) {
            input.style.borderColor = '#D32F2F';
            isValid = false;
        } else {
            input.style.borderColor = '';
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
        const response = await fetch('appointment.php', {
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
        const response = await fetch('appointment.php?action=list');
        const appointments = await response.json();
        
        const container = document.getElementById('appointmentsList');
        if (!container) return;
        
        if (appointments.length === 0) {
            container.innerHTML = '<p class="text-center">No appointments found</p>';
            return;
        }
        
        container.innerHTML = `
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
        `;
    } catch (error) {
        console.error('Error loading appointments:', error);
    }
}

// View Appointment Details
async function viewAppointment(id) {
    try {
        const response = await fetch(`appointment.php?id=${id}`);
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
        const response = await fetch(`medical_his.php?patient_id=${patientId}`);
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
        const response = await fetch('presciption.php', {
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
    window.open(`print_pres.php?id=${prescriptionId}`, '_blank');
}

// Load Health Tips
async function loadHealthTips() {
    try {
        const response = await fetch('health_tips.php');
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
});

// Search Functionality
function searchTable(inputId, tableId) {
    const input = document.getElementById(inputId);
    const filter = input.value.toUpperCase();
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