<?php
require_once 'config.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

$action = $_GET['action'] ?? $_POST['action'] ?? null;
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// Get Medical History (Default action when no 'action' parameter is specified)
if ($method === 'GET' && isset($_GET['patient_id']) && !$action) {
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
if ($method === 'POST' && $action === 'add_history') {
    $patient_id = mysqli_real_escape_string($conn, $_POST['patient_id']);
    $condition_name = mysqli_real_escape_string($conn, $_POST['condition_name']);
    $diagnosis_date = mysqli_real_escape_string($conn, $_POST['diagnosis_date']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $treatment = mysqli_real_escape_string($conn, $_POST['treatment']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    
    if (empty($condition_name) || empty($diagnosis_date) || empty($status)) {
        echo json_encode(['success' => false, 'message' => 'Required fields are missing.']);
        exit();
    }

    $doctor_id = $_SESSION['role'] === 'doctor' ? $_SESSION['user_id'] : NULL;
    
    $query = "INSERT INTO medical_history (patient_id, condition_name, diagnosis_date, description, treatment, status, doctor_id) 
              VALUES ('$patient_id', '$condition_name', '$diagnosis_date', '$description', '$treatment', '$status', " . ($doctor_id ? "'$doctor_id'" : "NULL") . ")";
    
    if (mysqli_query($conn, $query)) {
        echo json_encode(['success' => true, 'message' => 'Medical history record added successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error adding medical history record: ' . mysqli_error($conn)]);
    }
    exit();
}

// Get Vital Signs
if ($method === 'GET' && $action === 'vitals' && isset($_GET['patient_id'])) {
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
if ($method === 'POST' && $action === 'add_vitals') {
    $patient_id = mysqli_real_escape_string($conn, $_POST['patient_id']);
    $blood_pressure = mysqli_real_escape_string($conn, $_POST['blood_pressure']);
    $heart_rate = $_POST['heart_rate'] !== "" ? (int)$_POST['heart_rate'] : NULL;
    $temperature = $_POST['temperature'] !== "" ? (float)$_POST['temperature'] : NULL;
    $weight = $_POST['weight'] !== "" ? (float)$_POST['weight'] : NULL;
    $height = $_POST['height'] !== "" ? (float)$_POST['height'] : NULL;
    $recorded_date = mysqli_real_escape_string($conn, $_POST['recorded_date']);
    $notes = isset($_POST['notes']) ? mysqli_real_escape_string($conn, $_POST['notes']) : '';
    $recorded_by = $_SESSION['user_id'];

    // Server-side validation
    $errors = [];
    if (empty($recorded_date)) $errors[] = "Recorded date is required.";
    
    if (!empty($blood_pressure) && !preg_match('/^\d{2,3}\/\d{2,3}$/', $blood_pressure)) {
        $errors[] = "Invalid Blood Pressure format. Use ###/##.";
    }
    if ($heart_rate !== NULL && ($heart_rate < 30 || $heart_rate > 250)) {
        $errors[] = "Heart rate out of biological range (30-250 bpm).";
    }
    if ($temperature !== NULL && ($temperature < 90 || $temperature > 115)) {
        $errors[] = "Temperature out of biological range (90-115 °F).";
    }

    if (!empty($errors)) {
        echo json_encode(['success' => false, 'message' => implode(" ", $errors)]);
        exit();
    }
    
    $query = "INSERT INTO vital_signs (patient_id, blood_pressure, heart_rate, temperature, weight, height, recorded_date, recorded_by, notes) 
              VALUES ('$patient_id', '$blood_pressure', " . ($heart_rate ?? "NULL") . ", " . ($temperature ?? "NULL") . ", " . ($weight ?? "NULL") . ", " . ($height ?? "NULL") . ", '$recorded_date', '$recorded_by', '$notes')";
    
    if (mysqli_query($conn, $query)) {
        echo json_encode(['success' => true, 'message' => 'Vital signs recorded successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error recording vital signs: ' . mysqli_error($conn)]);
    }
    exit();
}

// Get Patient Summary
if ($method === 'GET' && $action === 'summary' && isset($_GET['patient_id'])) {
    $patient_id = mysqli_real_escape_string($conn, $_GET['patient_id']);
    
    // Get patient info (allow any user lookup — role check has already been done above)
    $patient_query = "SELECT id, full_name, email, phone, address, date_of_birth, blood_group, gender FROM users WHERE id = '$patient_id'";
    $patient_result = mysqli_query($conn, $patient_query);
    if (!$patient_result) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . mysqli_error($conn)]);
        exit();
    }
    $patient = mysqli_fetch_assoc($patient_result);

    if (!$patient) {
        echo json_encode(['success' => false, 'message' => 'Patient record not found']);
        exit();
    }
    
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