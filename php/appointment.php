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
    $created_by = $_SESSION['user_id'];
    
    // Check if time slot is available
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
    
    $query = "INSERT INTO appointments (patient_id, doctor_id, appointment_date, appointment_time, reason, created_by) 
              VALUES ('$patient_id', '$doctor_id', '$appointment_date', '$appointment_time', '$reason', '$created_by')";
    
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
              JOIN users d ON a.doctor_id = d.id
              WHERE ";
    
    if ($role === 'doctor') {
        $query .= "a.doctor_id = '$user_id'";
    } elseif ($role === 'patient') {
        $query .= "a.patient_id = '$user_id'";
    } else {
        $query .= "1=1"; // Assistant can see all
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
    $status = mysqli_real_escape_string($conn, $_PUT['status']);
    $notes = isset($_PUT['notes']) ? mysqli_real_escape_string($conn, $_PUT['notes']) : '';
    
    $query = "UPDATE appointments 
              SET status = '$status', notes = '$notes'
              WHERE id = '$id'";
    
    if (mysqli_query($conn, $query)) {
        echo json_encode(['success' => true, 'message' => 'Appointment updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error updating appointment']);
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
    $query = "SELECT id, full_name, phone, email FROM users WHERE role = 'patient' ORDER BY full_name";
    $result = mysqli_query($conn, $query);
    $patients = [];
    
    while ($row = mysqli_fetch_assoc($result)) {
        $patients[] = $row;
    }
    
    echo json_encode($patients);
    exit();
}
?>