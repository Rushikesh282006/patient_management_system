<?php
require_once 'config.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

// Create Appointment
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $patient_id = mysqli_real_escape_string($conn, $_POST['patient_id']);
    $doctor_id = mysqli_real_escape_string($conn, $_POST['doctor_id']);
    $appointment_date = mysqli_real_escape_string($conn, $_POST['appointment_date']);
    $appointment_time = mysqli_real_escape_string($conn, $_POST['appointment_time']);
    $reason = mysqli_real_escape_string($conn, $_POST['reason']);
    $symptoms = mysqli_real_escape_string($conn, $_POST['symptoms']);
    $created_by = $_SESSION['user_id'];

    // Prevent booking past dates
    $today = date('Y-m-d');
    if ($appointment_date < $today) {
        echo json_encode(['success' => false, 'message' => 'You cannot book an appointment in the past.']);
        exit();
    }

    // Prevent booking past times for today
    if ($appointment_date === $today) {
        $now = time();
        $selected_time = strtotime($appointment_time);
        
        // Use a 5-minute grace period for network latency
        if ($selected_time < ($now - 300)) {
            echo json_encode(['success' => false, 'message' => 'You cannot book an appointment for a past time.']);
            exit();
        }
    }
    
    // Check if time slot is available (only if doctor is selected)
    if (!empty($doctor_id)) {
        $check_query = "SELECT * FROM appointments 
                        WHERE doctor_id = '$doctor_id' 
                        AND appointment_date = '$appointment_date' 
                        AND appointment_time = '$appointment_time'
                        AND status != 'cancelled'";
        $check_result = mysqli_query($conn, $check_query);
        if (mysqli_num_rows($check_result) > 0) {
            echo json_encode(['success' => false, 'message' => 'This time slot is already booked']);
            exit();
        }
    }
    
    $doctor_val = !empty($doctor_id) ? "'$doctor_id'" : 'NULL';
    $query = "INSERT INTO appointments (patient_id, doctor_id, appointment_date, appointment_time, reason, symptoms, created_by) 
          VALUES ('$patient_id', $doctor_val, '$appointment_date', '$appointment_time', '$reason', '$symptoms', '$created_by')";
    
    if (mysqli_query($conn, $query)) {
        echo json_encode(['success' => true, 'message' => 'Appointment created successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error creating appointment']);
    }
    exit();
}

// Get Appointments
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'list') {
    $user_id = $_SESSION['user_id'];
    $role = $_SESSION['role'];
    
    $query = "SELECT a.*, 
              p.full_name as patient_name, p.phone as patient_phone,
              d.full_name as doctor_name, d.specialization
              FROM appointments a
              JOIN users p ON a.patient_id = p.id
              LEFT JOIN users d ON a.doctor_id = d.id
              WHERE ";
    
    if ($role === 'doctor') {
        $query .= "a.doctor_id = '$user_id'";
    } elseif ($role === 'patient') {
        $query .= "a.patient_id = '$user_id' AND (a.appointment_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) OR a.status = 'scheduled')";
    } else {
        $query .= "1=1"; // Assistant can see all
    }

    if (isset($_GET['search']) && !empty($_GET['search'])) {
        $search = mysqli_real_escape_string($conn, $_GET['search']);
        $query .= " AND (p.full_name LIKE '%$search%' OR d.full_name LIKE '%$search%' OR a.symptoms LIKE '%$search%')";
    }

    if (isset($_GET['status']) && !empty($_GET['status'])) {
        $status = mysqli_real_escape_string($conn, $_GET['status']);
        $query .= " AND a.status = '$status'";
    }
    
    $query .= " ORDER BY a.appointment_date DESC, a.appointment_time DESC";
    
    $result = mysqli_query($conn, $query);
    $appointments = [];
    
    while ($row = mysqli_fetch_assoc($result)) {
        $appointments[] = $row;
    }
    
    echo json_encode($appointments);
    exit();
}

// Get Single Appointment
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id'])) {
    $id = mysqli_real_escape_string($conn, $_GET['id']);
    
    $query = "SELECT a.*, 
              p.full_name as patient_name, p.phone as patient_phone, p.email as patient_email,
              d.full_name as doctor_name, d.specialization, d.phone as doctor_phone
              FROM appointments a
              JOIN users p ON a.patient_id = p.id
              JOIN users d ON a.doctor_id = d.id
              WHERE a.id = '$id'";
    
    $result = mysqli_query($conn, $query);
    
    if (mysqli_num_rows($result) === 1) {
        $appointment = mysqli_fetch_assoc($result);
        echo json_encode($appointment);
    } else {
        echo json_encode(['success' => false, 'message' => 'Appointment not found']);
    }
    exit();
}

// Update Appointment Status
if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    parse_str(file_get_contents("php://input"), $_PUT);
    
    $id = mysqli_real_escape_string($conn, $_PUT['id']);
    $status = isset($_PUT['status']) ? mysqli_real_escape_string($conn, $_PUT['status']) : null;
    $notes = isset($_PUT['notes']) ? mysqli_real_escape_string($conn, $_PUT['notes']) : '';
    $cancellation_reason = isset($_PUT['cancellation_reason']) ? mysqli_real_escape_string($conn, $_PUT['cancellation_reason']) : '';
    $doctor_id = isset($_PUT['doctor_id']) ? mysqli_real_escape_string($conn, $_PUT['doctor_id']) : null;
    $appointment_date = isset($_PUT['appointment_date']) ? mysqli_real_escape_string($conn, $_PUT['appointment_date']) : null;
    $appointment_time = isset($_PUT['appointment_time']) ? mysqli_real_escape_string($conn, $_PUT['appointment_time']) : null;
    $reason = isset($_PUT['reason']) ? mysqli_real_escape_string($conn, $_PUT['reason']) : null;

    // Build query dynamically based on provided fields
    $fields = [];
    if ($status !== null) $fields[] = "status = '$status'";
    if ($notes !== '') $fields[] = "notes = '$notes'";
    if ($cancellation_reason !== '') $fields[] = "cancellation_reason = '$cancellation_reason'";
    if ($doctor_id !== null && $doctor_id !== '') $fields[] = "doctor_id = '$doctor_id'";
    if ($appointment_date !== null) $fields[] = "appointment_date = '$appointment_date'";
    if ($appointment_time !== null) $fields[] = "appointment_time = '$appointment_time'";
    if ($reason !== null) $fields[] = "reason = '$reason'";
    
    if (empty($fields)) {
        echo json_encode(['success' => false, 'message' => 'No fields to update']);
        exit();
    }

    $query = "UPDATE appointments SET " . implode(', ', $fields) . " WHERE id = '$id'";
    
    if (mysqli_query($conn, $query)) {
        echo json_encode(['success' => true, 'message' => 'Appointment updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error updating appointment']);
    }
    exit();
}
// Delete Appointment
if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    parse_str(file_get_contents("php://input"), $_DELETE);
    
    $id = mysqli_real_escape_string($conn, $_DELETE['id']);
    
    $query = "DELETE FROM appointments WHERE id = '$id'";
    
    if (mysqli_query($conn, $query)) {
        echo json_encode(['success' => true, 'message' => 'Appointment deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error deleting appointment']);
    }
    exit();
}

// Get Doctors List
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'doctors') {
    $query = "SELECT id, full_name, specialization, phone FROM users WHERE role = 'doctor' ORDER BY full_name";
    $result = mysqli_query($conn, $query);
    $doctors = [];
    
    while ($row = mysqli_fetch_assoc($result)) {
        $doctors[] = $row;
    }
    
    echo json_encode($doctors);
    exit();
}

// Get Patients List
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'patients') {
    $query = "SELECT id, full_name, phone, email FROM users WHERE role = 'patient'";
    
    if (isset($_GET['search']) && !empty($_GET['search'])) {
        $search = mysqli_real_escape_string($conn, $_GET['search']);
        $query .= " AND (full_name LIKE '%$search%' OR phone LIKE '%$search%' OR email LIKE '%$search%')";
    }
    
    $query .= " ORDER BY full_name";
    
    $result = mysqli_query($conn, $query);
    $patients = [];
    
    while ($row = mysqli_fetch_assoc($result)) {
        $patients[] = $row;
    }
    
    echo json_encode($patients);
    exit();
}
?>