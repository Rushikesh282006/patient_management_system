<?php
require_once 'config.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

// Get Health Tips for Patient
if ($_SERVER['REQUEST_METHOD'] === 'GET' && !isset($_GET['action'])) {
    $patient_id = $_SESSION['role'] === 'patient' ? $_SESSION['user_id'] : (isset($_GET['patient_id']) ? mysqli_real_escape_string($conn, $_GET['patient_id']) : null);
    
    if (!$patient_id) {
        echo json_encode([]);
        exit();
    }
    
    // Get patient's medical conditions
    $conditions_query = "SELECT * FROM medical_history WHERE patient_id = '$patient_id' AND status = 'active'";
    $conditions_result = mysqli_query($conn, $conditions_query);
    
    $tips = [];
    
    // Generate personalized tips based on conditions
    while ($condition = mysqli_fetch_assoc($conditions_result)) {
        $condition_name = strtolower($condition['condition_name']);
        
        // Add condition-specific tips
        if (strpos($condition_name, 'hypertension') !== false || strpos($condition_name, 'blood pressure') !== false) {
            $tips[] = [
                'tip_category' => 'Cardiovascular Health',
                'tip_title' => 'Monitor Your Blood Pressure',
                'tip_content' => 'Check your blood pressure regularly and maintain a log. Aim for readings below 120/80 mmHg. Reduce salt intake and engage in regular physical activity.',
                'priority' => 'high'
            ];
            $tips[] = [
                'tip_category' => 'Diet',
                'tip_title' => 'Follow a Heart-Healthy Diet',
                'tip_content' => 'Include more fruits, vegetables, whole grains, and lean proteins. Limit saturated fats, trans fats, and sodium. The DASH diet is especially beneficial for managing blood pressure.',
                'priority' => 'high'
            ];
        }
        
        if (strpos($condition_name, 'diabetes') !== false || strpos($condition_name, 'sugar') !== false) {
            $tips[] = [
                'tip_category' => 'Blood Sugar Management',
                'tip_title' => 'Monitor Blood Glucose Levels',
                'tip_content' => 'Check your blood sugar as recommended by your doctor. Keep a record of your readings, especially before and after meals. Target range is typically 80-130 mg/dL before meals.',
                'priority' => 'high'
            ];
            $tips[] = [
                'tip_category' => 'Diet',
                'tip_title' => 'Control Carbohydrate Intake',
                'tip_content' => 'Choose complex carbohydrates over simple sugars. Eat small, frequent meals throughout the day. Include fiber-rich foods to help manage blood sugar levels.',
                'priority' => 'high'
            ];
        }
        
        if (strpos($condition_name, 'asthma') !== false || strpos($condition_name, 'respiratory') !== false) {
            $tips[] = [
                'tip_category' => 'Respiratory Health',
                'tip_title' => 'Avoid Asthma Triggers',
                'tip_content' => 'Identify and avoid your asthma triggers such as smoke, pollen, dust mites, and pet dander. Keep your living space clean and well-ventilated.',
                'priority' => 'high'
            ];
        }
        
        if (strpos($condition_name, 'allergy') !== false || strpos($condition_name, 'allergies') !== false) {
            $tips[] = [
                'tip_category' => 'Allergy Management',
                'tip_title' => 'Seasonal Allergy Care',
                'tip_content' => 'During high pollen seasons, keep windows closed and use air conditioning. Shower and change clothes after being outdoors. Consider using a HEPA filter.',
                'priority' => 'medium'
            ];
        }
    }
    
    // Add general wellness tips
    $general_tips = [
        [
            'tip_category' => 'General Wellness',
            'tip_title' => 'Stay Hydrated',
            'tip_content' => 'Drink at least 8 glasses of water daily. Proper hydration supports all bodily functions, aids digestion, and helps maintain healthy skin.',
            'priority' => 'medium'
        ],
        [
            'tip_category' => 'Exercise',
            'tip_title' => 'Regular Physical Activity',
            'tip_content' => 'Aim for at least 150 minutes of moderate aerobic activity or 75 minutes of vigorous activity per week. Include strength training exercises twice a week.',
            'priority' => 'medium'
        ],
        [
            'tip_category' => 'Sleep',
            'tip_title' => 'Quality Sleep Matters',
            'tip_content' => 'Aim for 7-9 hours of sleep each night. Maintain a consistent sleep schedule, create a relaxing bedtime routine, and keep your bedroom cool and dark.',
            'priority' => 'medium'
        ],
        [
            'tip_category' => 'Mental Health',
            'tip_title' => 'Manage Stress Effectively',
            'tip_content' => 'Practice stress-reduction techniques like meditation, deep breathing, or yoga. Make time for hobbies and activities you enjoy. Don\'t hesitate to seek professional help if needed.',
            'priority' => 'medium'
        ],
        [
            'tip_category' => 'Preventive Care',
            'tip_title' => 'Regular Health Checkups',
            'tip_content' => 'Schedule annual physical exams and recommended screenings. Stay up to date with vaccinations. Early detection is key to preventing serious health issues.',
            'priority' => 'low'
        ]
    ];
    
    // Add general tips
    $tips = array_merge($tips, array_slice($general_tips, 0, 3));
    
    // If no tips generated, add default tips
    if (empty($tips)) {
        $tips = $general_tips;
    }
    
    echo json_encode($tips);
    exit();
}

// Create Health Tip (for doctors/assistants)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($_SESSION['role'] === 'patient') {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit();
    }
    
    $patient_id = mysqli_real_escape_string($conn, $_POST['patient_id']);
    $tip_category = mysqli_real_escape_string($conn, $_POST['tip_category']);
    $tip_title = mysqli_real_escape_string($conn, $_POST['tip_title']);
    $tip_content = mysqli_real_escape_string($conn, $_POST['tip_content']);
    $priority = mysqli_real_escape_string($conn, $_POST['priority']);
    
    $query = "INSERT INTO health_tips (patient_id, tip_category, tip_title, tip_content, priority) 
              VALUES ('$patient_id', '$tip_category', '$tip_title', '$tip_content', '$priority')";
    
    if (mysqli_query($conn, $query)) {
        echo json_encode(['success' => true, 'message' => 'Health tip added successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error adding health tip']);
    }
    exit();
}

// Mark tip as read
if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    parse_str(file_get_contents("php://input"), $_PUT);
    
    $id = mysqli_real_escape_string($conn, $_PUT['id']);
    
    $query = "UPDATE health_tips SET is_read = TRUE WHERE id = '$id'";
    
    if (mysqli_query($conn, $query)) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error updating tip']);
    }
    exit();
}
?>