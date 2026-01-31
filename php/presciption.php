<?php
require_once 'config.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

// Create Prescription
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['action'])) {
    if ($_SESSION['role'] !== 'doctor') {
        echo json_encode(['success' => false, 'message' => 'Only doctors can create prescriptions']);
        exit();
    }
    
    $appointment_id = mysqli_real_escape_string($conn, $_POST['appointment_id']);
    $patient_id = mysqli_real_escape_string($conn, $_POST['patient_id']);
    $doctor_id = $_SESSION['user_id'];
    $diagnosis = mysqli_real_escape_string($conn, $_POST['diagnosis']);
    $medications = mysqli_real_escape_string($conn, $_POST['medications']);
    $instructions = mysqli_real_escape_string($conn, $_POST['instructions']);
    $follow_up_date = isset($_POST['follow_up_date']) ? mysqli_real_escape_string($conn, $_POST['follow_up_date']) : NULL;
    $prescription_date = date('Y-m-d');
    
    $query = "INSERT INTO prescriptions (appointment_id, patient_id, doctor_id, diagnosis, medications, instructions, follow_up_date, prescription_date) 
              VALUES ('$appointment_id', '$patient_id', '$doctor_id', '$diagnosis', '$medications', '$instructions', " . ($follow_up_date ? "'$follow_up_date'" : "NULL") . ", '$prescription_date')";
    
    if (mysqli_query($conn, $query)) {
        $prescription_id = mysqli_insert_id($conn);
        
        // Update appointment status
        $update_query = "UPDATE appointments SET status = 'completed' WHERE id = '$appointment_id'";
        mysqli_query($conn, $update_query);
        
        echo json_encode([
            'success' => true, 
            'message' => 'Prescription created successfully',
            'prescription_id' => $prescription_id
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error creating prescription']);
    }
    exit();
}

// Get Prescriptions
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['patient_id'])) {
    $patient_id = mysqli_real_escape_string($conn, $_GET['patient_id']);
    
    // Check authorization
    if ($_SESSION['role'] === 'patient' && $_SESSION['user_id'] != $patient_id) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit();
    }
    
    $query = "SELECT p.*, 
              d.full_name as doctor_name, d.specialization,
              a.appointment_date, a.reason
              FROM prescriptions p
              JOIN users d ON p.doctor_id = d.id
              JOIN appointments a ON p.appointment_id = a.id
              WHERE p.patient_id = '$patient_id'
              ORDER BY p.prescription_date DESC";
    
    $result = mysqli_query($conn, $query);
    $prescriptions = [];
    
    while ($row = mysqli_fetch_assoc($result)) {
        $prescriptions[] = $row;
    }
    
    echo json_encode($prescriptions);
    exit();
}

// Get Single Prescription
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id'])) {
    $id = mysqli_real_escape_string($conn, $_GET['id']);
    
    $query = "SELECT p.*, 
              pat.full_name as patient_name, pat.phone as patient_phone, pat.date_of_birth, pat.gender, pat.address,
              d.full_name as doctor_name, d.specialization, d.phone as doctor_phone,
              a.appointment_date, a.reason
              FROM prescriptions p
              JOIN users pat ON p.patient_id = pat.id
              JOIN users d ON p.doctor_id = d.id
              JOIN appointments a ON p.appointment_id = a.id
              WHERE p.id = '$id'";
    
    $result = mysqli_query($conn, $query);
    
    if (mysqli_num_rows($result) === 1) {
        $prescription = mysqli_fetch_assoc($result);
        echo json_encode($prescription);
    } else {
        echo json_encode(['success' => false, 'message' => 'Prescription not found']);
    }
    exit();
}

// Get Doctor's Recent Prescriptions
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'doctor_prescriptions') {
    if ($_SESSION['role'] !== 'doctor') {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit();
    }
    
    $doctor_id = $_SESSION['user_id'];
    
    $query = "SELECT p.*, 
              pat.full_name as patient_name,
              a.appointment_date
              FROM prescriptions p
              JOIN users pat ON p.patient_id = pat.id
              JOIN appointments a ON p.appointment_id = a.id
              WHERE p.doctor_id = '$doctor_id'
              ORDER BY p.prescription_date DESC
              LIMIT 20";
    
    $result = mysqli_query($conn, $query);
    $prescriptions = [];
    
    while ($row = mysqli_fetch_assoc($result)) {
        $prescriptions[] = $row;
    }
    
    echo json_encode($prescriptions);
    exit();
}
?>