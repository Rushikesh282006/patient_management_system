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
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&family=Crimson+Pro:wght@600;700&display=swap');
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Outfit', sans-serif;
            padding: 2rem;
            background: #f5f5f5;
        }
        
        .prescription-document {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 3rem;
            border: 3px solid #0A4D68;
            border-radius: 16px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.1);
        }
        
        .prescription-header {
            text-align: center;
            border-bottom: 3px solid #0A4D68;
            padding-bottom: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .prescription-header h1 {
            font-family: 'Crimson Pro', serif;
            font-size: 2.5rem;
            color: #0A4D68;
            margin-bottom: 0.5rem;
        }
        
        .doctor-info {
            color: #6B7280;
            font-size: 1.1rem;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
            margin-bottom: 2rem;
            padding: 1.5rem;
            background: #F0F8FF;
            border-radius: 12px;
        }
        
        .info-item {
            display: flex;
            flex-direction: column;
        }
        
        .info-label {
            font-weight: 600;
            color: #0A4D68;
            font-size: 0.9rem;
            margin-bottom: 0.3rem;
        }
        
        .info-value {
            color: #1A1A2E;
            font-size: 1rem;
        }
        
        .prescription-section {
            margin-bottom: 2rem;
        }
        
        .section-title {
            font-family: 'Crimson Pro', serif;
            font-size: 1.5rem;
            color: #0A4D68;
            border-bottom: 2px solid #00DFA2;
            padding-bottom: 0.5rem;
            margin-bottom: 1rem;
        }
        
        .section-content {
            line-height: 1.8;
            color: #1A1A2E;
            white-space: pre-wrap;
        }
        
        .medications {
            background: #F0F8FF;
            padding: 1.5rem;
            border-radius: 12px;
            border-left: 4px solid #00DFA2;
        }
        
        .signature-section {
            margin-top: 3rem;
            padding-top: 2rem;
            border-top: 2px dashed #0A4D68;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
        }
        
        .signature-box {
            text-align: center;
        }
        
        .signature-line {
            width: 200px;
            border-bottom: 2px solid #0A4D68;
            margin-bottom: 0.5rem;
            height: 60px;
        }
        
        .signature-label {
            font-weight: 600;
            color: #0A4D68;
        }
        
        .footer {
            margin-top: 2rem;
            text-align: center;
            color: #6B7280;
            font-size: 0.9rem;
            padding-top: 1rem;
            border-top: 1px solid #E5E7EB;
        }
        
        .print-button {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            padding: 1rem 2rem;
            background: #00DFA2;
            color: #0A4D68;
            border: none;
            border-radius: 12px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(0, 223, 162, 0.3);
            transition: all 0.3s;
        }
        
        .print-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(0, 223, 162, 0.4);
        }
        
        @media print {
            body {
                padding: 0;
                background: white;
            }
            
            .print-button {
                display: none;
            }
            
            .prescription-document {
                box-shadow: none;
                margin: 0;
                max-width: 100%;
            }
        }
    </style>
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