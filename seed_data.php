<?php
require_once 'includes/config.php';

$passwordHash = password_hash('password', PASSWORD_BCRYPT);

echo "<h3>Seeding Realistic MediConnect Data...</h3>";

// 1. Ensure Doctors exist in users & doctors
$doctorsData = [
    ['Dr. John Smith', 'doctor@mediconnect.com', 'Male', '01711111111', 'Cardiology', 'MBBS, MD (Cardiology)', 10, 500.00, 'Senior Consultant in Cardiology with 10+ years of dedicated clinical experience in preventive and interventional cardiovascular care.'],
    ['Dr. Sarah Rahman', 'sarah@mediconnect.com', 'Female', '01722222222', 'Neurology', 'MBBS, FCPS (Neurology)', 12, 700.00, 'Specialist neurologist focusing on migraine management, stroke rehabilitation, and neuro-diagnostics.'],
    ['Dr. Arif Hasan', 'arif@mediconnect.com', 'Male', '01733333333', 'Pediatrics', 'MBBS, DCH, MD (Child Health)', 8, 500.00, 'Pediatric specialist with compassionate care for neonates, infant growth milestones, and childhood illnesses.'],
    ['Dr. Nusrat Jahan', 'nusrat@mediconnect.com', 'Female', '01744444444', 'Dermatology', 'MBBS, DDV (Dermatology)', 9, 600.00, 'Expert clinical and cosmetic dermatologist specializing in chronic eczema, acne treatments, and skin allergies.'],
    ['Dr. Tanvir Ahmed', 'tanvir@mediconnect.com', 'Male', '01755555555', 'Orthopedics', 'MBBS, MS (Orthopedics)', 14, 800.00, 'Chief orthopedic surgeon specializing in joint replacement, sports injury recovery, and bone density preservation.']
];

foreach ($doctorsData as $d) {
    // Check if user exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $d[1]);
    $stmt->execute();
    $existing = $stmt->get_result()->fetch_assoc();

    if ($existing) {
        $userId = $existing['id'];
        $conn->query("UPDATE users SET full_name = '{$d[0]}' WHERE id = $userId");
    } else {
        $stmt2 = $conn->prepare("INSERT INTO users (full_name, email, password, role, phone, gender) VALUES (?, ?, ?, 'doctor', ?, ?)");
        $stmt2->bind_param("sssss", $d[0], $d[1], $passwordHash, $d[3], $d[2]);
        $stmt2->execute();
        $userId = $stmt2->insert_id;
    }

    // Insert or update doctor profile
    $docCheck = $conn->query("SELECT id FROM doctors WHERE user_id = $userId")->fetch_assoc();
    if (!$docCheck) {
        $stmtDoc = $conn->prepare("INSERT INTO doctors (user_id, specialization, qualification, experience_years, consultation_fee, bio, is_approved) VALUES (?, ?, ?, ?, ?, ?, 1)");
        $stmtDoc->bind_param("issids", $userId, $d[4], $d[5], $d[6], $d[7], $d[8]);
        $stmtDoc->execute();
    } else {
        $conn->query("UPDATE doctors SET is_approved = 1, consultation_fee = {$d[7]} WHERE id = {$docCheck['id']}");
    }
}

// 2. Ensure Patients exist in users & patients
$patientsData = [
    ['Jane Doe', 'patient@mediconnect.com', 'Female', '01722222222', 'B+', 'Penicillin, Dust', '01733333333', 'John Doe'],
    ['Kamal Hossain', 'kamal@mediconnect.com', 'Male', '01811223344', 'A+', 'None reported', '01899887766', 'Fatima Hossain'],
    ['Farhana Akter', 'farhana@mediconnect.com', 'Female', '01922334455', 'O+', 'Seafood', '01911223344', 'Rashid Akter'],
    ['Rafiqul Islam', 'rafiq@mediconnect.com', 'Male', '01633445566', 'AB+', 'None', '01655443322', 'Nasima Islam']
];

foreach ($patientsData as $p) {
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $p[1]);
    $stmt->execute();
    $existing = $stmt->get_result()->fetch_assoc();

    if ($existing) {
        $userId = $existing['id'];
    } else {
        $stmt2 = $conn->prepare("INSERT INTO users (full_name, email, password, role, phone, gender) VALUES (?, ?, ?, 'patient', ?, ?)");
        $stmt2->bind_param("sssss", $p[0], $p[1], $passwordHash, $p[3], $p[2]);
        $stmt2->execute();
        $userId = $stmt2->insert_id;
    }

    $patCheck = $conn->query("SELECT id FROM patients WHERE user_id = $userId")->fetch_assoc();
    if (!$patCheck) {
        $stmtPat = $conn->prepare("INSERT INTO patients (user_id, blood_group, allergies, emergency_contact, emergency_contact_name) VALUES (?, ?, ?, ?, ?)");
        $stmtPat->bind_param("issss", $userId, $p[4], $p[5], $p[6], $p[7]);
        $stmtPat->execute();
    }
}

// Get primary IDs
$doctors = $conn->query("SELECT id, user_id FROM doctors")->fetch_all(MYSQLI_ASSOC);
$patients = $conn->query("SELECT id, user_id FROM patients")->fetch_all(MYSQLI_ASSOC);

if (count($doctors) > 0 && count($patients) > 0) {
    $d1 = $doctors[0]['id'];
    $d2 = isset($doctors[1]) ? $doctors[1]['id'] : $d1;
    $d3 = isset($doctors[2]) ? $doctors[2]['id'] : $d1;
    $d4 = isset($doctors[3]) ? $doctors[3]['id'] : $d1;
    $d5 = isset($doctors[4]) ? $doctors[4]['id'] : $d1;

    $p1 = $patients[0]['id']; // Jane Doe
    $p2 = isset($patients[1]) ? $patients[1]['id'] : $p1;
    $p3 = isset($patients[2]) ? $patients[2]['id'] : $p1;
    $p4 = isset($patients[3]) ? $patients[3]['id'] : $p1;

    // Check count of appointments
    $aptCount = $conn->query("SELECT COUNT(*) as c FROM appointments")->fetch_assoc()['c'];
    if ($aptCount < 5) {
        $conn->query("
            INSERT INTO appointments (patient_id, doctor_id, appointment_date, appointment_time, status, reason, payment_method, payment_status, transaction_id, notes) VALUES
            ($p1, $d1, '2026-01-15', '10:00:00', 'completed', 'Routine cardiovascular health screening and blood pressure review', 'bKash', 'paid', 'BK7829104X', 'Blood pressure noted at 135/85. Heart sounds normal.'),
            ($p1, $d1, '2026-03-22', '11:30:00', 'completed', 'Follow-up visit on hypertension medication tolerance', 'Pay at Clinic', 'paid', NULL, 'Reported zero side-effects. Medication dosage maintained.'),
            ($p1, $d2, '2026-05-10', '14:00:00', 'completed', 'Persistent morning tension headaches and neck stiffness', 'bKash', 'paid', 'BK9912048X', 'Cervical tension diagnosed, posture improvement guided.'),
            ($p1, $d1, CURDATE(), '15:00:00', 'confirmed', 'Six-month cardiovascular wellness follow-up', 'Debit/Credit Card', 'paid', 'TXN_CARD_99182', 'Awaiting telehealth conference link.'),
            ($p1, $d4, DATE_ADD(CURDATE(), INTERVAL 4 DAY), '16:00:00', 'pending', 'Skin rash consultation on left forearm', 'Pay at Clinic', 'unpaid', NULL, 'Attached photo in intake form.'),
            ($p2, $d2, '2026-02-18', '09:30:00', 'completed', 'Frequent migraines during screen work', 'bKash', 'paid', 'BK4819201Y', 'Prescribed preventative and rescue protocol.'),
            ($p2, $d3, '2026-04-12', '10:30:00', 'completed', 'Child immunization schedule inquiry', 'Pay at Clinic', 'paid', NULL, 'Completed routine vaccines.'),
            ($p2, $d1, '2026-06-08', '11:00:00', 'completed', 'Chest tightness during heavy stair climbing', 'bKash', 'paid', 'BK7829104X', 'ECG conducted: Normal sinus rhythm.'),
            ($p2, $d5, '2026-07-25', '15:30:00', 'completed', 'Knee joint pain after morning jogging', 'Debit/Credit Card', 'paid', 'TXN_CARD_44102', 'Mild patellar strain. Physiotherapy recommended.'),
            ($p3, $d4, '2026-03-14', '14:30:00', 'completed', 'Eczema flare-up on hands during dry weather', 'Pay at Clinic', 'paid', NULL, 'Topical emollient therapy administered.'),
            ($p3, $d1, '2026-07-02', '10:00:00', 'completed', 'Family history of coronary artery disease counseling', 'bKash', 'paid', 'BK5501928X', 'Advised balanced Mediterranean diet and exercise.'),
            ($p3, $d2, '2026-08-19', '11:30:00', 'completed', 'Occasional dizziness upon standing suddenly', 'Debit/Credit Card', 'paid', 'TXN_CARD_78912', 'Benign orthostatic hypotension. Hydration increased.'),
            ($p4, $d5, '2026-05-28', '16:00:00', 'completed', 'Lower back soreness from office ergonomic seating', 'Pay at Clinic', 'paid', NULL, 'Core strengthening protocol instructed.'),
            ($p4, $d3, '2026-08-04', '09:00:00', 'completed', 'Toddler seasonal cough and cold consultation', 'bKash', 'paid', 'BK8821940X', 'Viral symptoms, advised hydration and rest.'),
            ($p4, $d1, '2026-09-12', '10:30:00', 'completed', 'Annual health checkup and lipid profile review', 'Pay at Clinic', 'paid', NULL, 'Total cholesterol slightly elevated. Diet plan given.')
        ");
        echo "<li>Added 15 realistic historical and active appointments across 2026!</li>";
    }

    // Prescriptions for completed appointments
    $rxCount = $conn->query("SELECT COUNT(*) as c FROM prescriptions")->fetch_assoc()['c'];
    if ($rxCount < 3) {
        $completedApts = $conn->query("SELECT id, doctor_id, patient_id FROM appointments WHERE status = 'completed' LIMIT 4")->fetch_all(MYSQLI_ASSOC);
        foreach ($completedApts as $idx => $apt) {
            $dx = ($idx == 0) ? 'Stage 1 Hypertension & Mild Tachycardia' : (($idx == 1) ? 'Chronic Tension Headache & Sleep Disturbance' : 'Acute Bronchial Allergy & Cough');
            $meds = ($idx == 0) ? "Tab. Amlodipine 5mg — 1 tablet daily (Morning, after breakfast)\nTab. Nebivolol 2.5mg — 1 tablet daily (Morning)\nTab. Vitamin D3 40000 IU — 1 capsule weekly (with milk)" : "Tab. Naproxen 250mg — 1 tablet as needed with food\nTab. Amitriptyline 10mg — 1 tablet at bedtime\nCap. Esomeprazole 20mg — 1 capsule before meals (Morning)";
            $ins = "Maintain low-sodium diet, drink 2.5L water daily, brisk walking for 30 minutes 5 days a week.";
            $followUp = date('Y-m-d', strtotime('+30 days'));

            $conn->query("
                INSERT INTO prescriptions (appointment_id, doctor_id, patient_id, diagnosis, medicines, instructions, follow_up_date)
                VALUES ({$apt['id']}, {$apt['doctor_id']}, {$apt['patient_id']}, '$dx', '$meds', '$ins', '$followUp')
            ");
        }
        echo "<li>Added realistic digital prescriptions with medications and dosages!</li>";
    }

    // Medical history records
    $mhCount = $conn->query("SELECT COUNT(*) as c FROM medical_history")->fetch_assoc()['c'];
    if ($mhCount < 3) {
        $conn->query("
            INSERT INTO medical_history (patient_id, doctor_id, title, description, diagnosis, treatment, visit_date) VALUES
            ($p1, $d1, 'Cardiovascular Health Review', 'Comprehensive cardiac assessment including blood pressure, resting ECG, and cholesterol panel.', 'Stage 1 Essential Hypertension', 'Prescribed Amlodipine 5mg and lifestyle modifications.', '2026-01-15'),
            ($p1, $d1, 'Blood Pressure Follow-up', 'Patient reported feeling energetic with normal daily blood pressure readings averaging 122/78 mmHg.', 'Controlled Hypertension', 'Maintained existing medication regime.', '2026-03-22'),
            ($p1, $d2, 'Neurological Assessment', 'Evaluation of occipital tension and neck stiffness due to desk work posture.', 'Tension-Type Cephalea', 'Prescribed muscle relaxant and ergonomic exercises.', '2026-05-10')
        ");
        echo "<li>Added detailed medical history timeline entries!</li>";
    }

    // Reviews
    $revCount = $conn->query("SELECT COUNT(*) as c FROM reviews")->fetch_assoc()['c'];
    if ($revCount < 4) {
        $conn->query("
            INSERT INTO reviews (patient_id, doctor_id, appointment_id, rating, comment) VALUES
            ($p1, $d1, 1, 5, 'Dr. Smith was exceptionally patient, explained my ECG thoroughly, and gave very practical lifestyle advice.'),
            ($p2, $d2, 6, 5, 'Remarkable neurologist! Diagnosed my migraine triggers within the first 15 minutes of consultation.'),
            ($p3, $d4, 10, 5, 'My eczema resolved completely after following Dr. Nusrat\'s personalized skincare routine. Highly recommended!'),
            ($p4, $d5, 13, 4, 'Very knowledgeable orthopedic consultant. Guided my post-injury physical therapy clearly.')
        ");
        echo "<li>Added verified patient reviews with ratings!</li>";
    }

    // Contact messages
    $msgCount = $conn->query("SELECT COUNT(*) as c FROM contact_messages")->fetch_assoc()['c'];
    if ($msgCount < 3) {
        $conn->query("
            INSERT INTO contact_messages (name, email, subject, message, is_read) VALUES
            ('Tanvir Ahmed', 'tanvir@gmail.com', 'Online Prescription Delivery', 'Can prescriptions written on MediConnect be delivered directly to local pharmacies in Dhanmondi?', 1),
            ('Shirin Sultana', 'shirin@yahoo.com', 'Pediatric Urgent Consultations', 'Do you provide on-call emergency pediatrician booking during weekends?', 0),
            ('Mustafizur Rahman', 'mustafiz@outlook.com', 'Corporate Healthcare Packages', 'We want to provide MediConnect telemedicine coverage to our team of 45 employees. Please share package details.', 0)
        ");
        echo "<li>Added realistic contact messages in Admin portal!</li>";
    }
}

echo "<h4 style='color:green;'>Data seeding completed successfully! You can refresh your dashboard now.</h4>";
?>
