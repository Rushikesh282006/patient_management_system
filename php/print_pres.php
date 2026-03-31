<?php
require_once 'config.php';

if (!isset($_GET['id'])) {
    die('Prescription ID required');
}

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

if (mysqli_num_rows($result) !== 1) {
    die('Prescription not found');
}

$prescription = mysqli_fetch_assoc($result);
$age = $prescription['date_of_birth'] ? date_diff(date_create($prescription['date_of_birth']), date_create('today'))->y : 'N/A';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prescription - <?php echo htmlspecialchars($prescription['patient_name']); ?></title>
    <link rel="stylesheet" href="../css/print.css">
</head>
<body>
    <div class="prescription-document">
        <div class="prescription-header">
            <h1>℞ PRESCRIPTION</h1>
            <div class="doctor-info">
                <strong>Dr. <?php echo htmlspecialchars($prescription['doctor_name']); ?></strong><br>
                <?php echo htmlspecialchars($prescription['specialization']); ?><br>
                Phone: <?php echo htmlspecialchars($prescription['doctor_phone']); ?>
            </div>
        </div>
        
        <div class="info-grid">
            <div class="info-item">
                <span class="info-label">PATIENT NAME</span>
                <span class="info-value"><?php echo htmlspecialchars($prescription['patient_name']); ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">AGE / GENDER</span>
                <span class="info-value"><?php echo $age; ?> Years / <?php echo ucfirst($prescription['gender'] ?? 'N/A'); ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">PHONE</span>
                <span class="info-value"><?php echo htmlspecialchars($prescription['patient_phone']); ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">DATE</span>
                <span class="info-value"><?php echo date('F d, Y', strtotime($prescription['prescription_date'])); ?></span>
            </div>
            <div class="info-item" style="grid-column: 1 / -1;">
                <span class="info-label">ADDRESS</span>
                <span class="info-value"><?php echo htmlspecialchars($prescription['address'] ?? 'N/A'); ?></span>
            </div>
        </div>
        
        <div class="prescription-section">
            <h2 class="section-title">Diagnosis</h2>
            <div class="section-content"><?php echo nl2br(htmlspecialchars($prescription['diagnosis'])); ?></div>
        </div>
        
        <div class="prescription-section">
            <h2 class="section-title">Medications</h2>
            <div class="medications">
                <div class="section-content"><?php echo nl2br(htmlspecialchars($prescription['medications'])); ?></div>
            </div>
        </div>
        
        <div class="prescription-section">
            <h2 class="section-title">Instructions</h2>
            <div class="section-content"><?php echo nl2br(htmlspecialchars($prescription['instructions'])); ?></div>
        </div>
        
        <?php if ($prescription['follow_up_date']): ?>
        <div class="prescription-section">
            <h2 class="section-title">Follow-up Appointment</h2>
            <div class="section-content">
                <strong>Date:</strong> <?php echo date('F d, Y', strtotime($prescription['follow_up_date'])); ?>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="signature-section">
            <div class="signature-box">
                <div class="signature-line"></div>
                <span class="signature-label">Patient Signature</span>
            </div>
            <div class="signature-box">
                <div class="signature-line"></div>
                <span class="signature-label">Doctor Signature</span>
            </div>
        </div>
        
        <div class="footer">
            <p>This is a computer-generated prescription and is valid.</p>
            <p>Prescription ID: #<?php echo str_pad($prescription['id'], 6, '0', STR_PAD_LEFT); ?></p>
        </div>
    </div>
    
    <button class="print-button" onclick="window.print()">
        🖨️ Print Prescription
    </button>
</body>
</html>