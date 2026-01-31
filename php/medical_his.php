<?php
require_once 'config.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

// Get Medical History
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['patient_id'])) {
    $patient_id = mysqli_real_escape_string($conn, $_GET['patient_id']);
    
    // Check authorization
    if ($_SESSION['role'] === 'patient' && $_SESSION['user_id'] != $patient_id) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit();
    }
    
    $query = "SELECT mh.*, 
              d.full_name as doctor_name, d.specialization
              FROM medical_history mh
              LEFT JOIN users d ON mh.doctor_id = d.id
              WHERE mh.patient_id = '$patient_id'
              ORDER BY mh.diagnosis_date DESC";
    
    $result = mysqli_query($conn, $query);
    $history = [];
    
    while ($row = mysqli_fetch_assoc($result)) {
        $history[] = $row;
    }
    
    echo json_encode($history);
    exit();
}

// Add Medical History Record
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $patient_id = mysqli_real_escape_string($conn, $_POST['patient_id']);
    $condition_name = mysqli_real_escape_string($conn, $_POST['condition_name']);
    $diagnosis_date = mysqli_real_escape_string($conn, $_POST['diagnosis_date']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $treatment = mysqli_real_escape_string($conn, $_POST['treatment']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    $doctor_id = $_SESSION['role'] === 'doctor' ? $_SESSION['user_id'] : NULL;
    
    $query = "INSERT INTO medical_history (patient_id, condition_name, diagnosis_date, description, treatment, status, doctor_id) 
              VALUES ('$patient_id', '$condition_name', '$diagnosis_date', '$description', '$treatment', '$status', " . ($doctor_id ? "'$doctor_id'" : "NULL") . ")";
    
    if (mysqli_query($conn, $query)) {
        echo json_encode(['success' => true, 'message' => 'Medical history record added successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error adding medical history record']);
    }
    exit();
}

// Get Vital Signs
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'vitals' && isset($_GET['patient_id'])) {
    $patient_id = mysqli_real_escape_string($conn, $_GET['patient_id']);
    
    $query = "SELECT vs.*, 
              u.full_name as recorded_by_name
              FROM vital_signs vs
              JOIN users u ON vs.recorded_by = u.id
              WHERE vs.patient_id = '$patient_id'
              ORDER BY vs.recorded_date DESC
              LIMIT 10";
    
    $result = mysqli_query($conn, $query);
    $vitals = [];
    
    while ($row = mysqli_fetch_assoc($result)) {
        $vitals[] = $row;
    }
    
    echo json_encode($vitals);
    exit();
}

// Add Vital Signs
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_vitals') {
    $patient_id = mysqli_real_escape_string($conn, $_POST['patient_id']);
    $blood_pressure = mysqli_real_escape_string($conn, $_POST['blood_pressure']);
    $heart_rate = mysqli_real_escape_string($conn, $_POST['heart_rate']);
    $temperature = mysqli_real_escape_string($conn, $_POST['temperature']);
    $weight = mysqli_real_escape_string($conn, $_POST['weight']);
    $height = mysqli_real_escape_string($conn, $_POST['height']);
    $recorded_date = mysqli_real_escape_string($conn, $_POST['recorded_date']);
    $notes = isset($_POST['notes']) ? mysqli_real_escape_string($conn, $_POST['notes']) : '';
    $recorded_by = $_SESSION['user_id'];
    
    $query = "INSERT INTO vital_signs (patient_id, blood_pressure, heart_rate, temperature, weight, height, recorded_date, recorded_by, notes) 
              VALUES ('$patient_id', '$blood_pressure', '$heart_rate', '$temperature', '$weight', '$height', '$recorded_date', '$recorded_by', '$notes')";
    
    if (mysqli_query($conn, $query)) {
        echo json_encode(['success' => true, 'message' => 'Vital signs recorded successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error recording vital signs']);
    }
    exit();
}

// Get Patient Summary
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'summary' && isset($_GET['patient_id'])) {
    $patient_id = mysqli_real_escape_string($conn, $_GET['patient_id']);
    
    // Get patient info
    $patient_query = "SELECT * FROM users WHERE id = '$patient_id' AND role = 'patient'";
    $patient_result = mysqli_query($conn, $patient_query);
    $patient = mysqli_fetch_assoc($patient_result);
    
    // Get recent conditions
    $conditions_query = "SELECT * FROM medical_history WHERE patient_id = '$patient_id' AND status = 'active' ORDER BY diagnosis_date DESC LIMIT 5";
    $conditions_result = mysqli_query($conn, $conditions_query);
    $conditions = [];
    while ($row = mysqli_fetch_assoc($conditions_result)) {
        $conditions[] = $row;
    }
    
    // Get latest vitals
    $vitals_query = "SELECT * FROM vital_signs WHERE patient_id = '$patient_id' ORDER BY recorded_date DESC LIMIT 1";
    $vitals_result = mysqli_query($conn, $vitals_query);
    $latest_vitals = mysqli_fetch_assoc($vitals_result);
    
    // Get appointment count
    $apt_query = "SELECT COUNT(*) as count FROM appointments WHERE patient_id = '$patient_id'";
    $apt_result = mysqli_query($conn, $apt_query);
    $apt_count = mysqli_fetch_assoc($apt_result)['count'];
    
    $summary = [
        'patient' => $patient,
        'active_conditions' => $conditions,
        'latest_vitals' => $latest_vitals,
        'total_appointments' => $apt_count
    ];
    
    echo json_encode($summary);
    exit();
}
?>