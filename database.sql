-- ============================================
-- MediConnect - Telemedicine Appointment Platform
-- Database Schema
-- ============================================

CREATE DATABASE IF NOT EXISTS mediconnect_db;
USE mediconnect_db;

-- -----------------------------------------------
-- Table: users (shared login for all roles)
-- -----------------------------------------------
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'doctor', 'patient') NOT NULL DEFAULT 'patient',
    profile_image VARCHAR(255) DEFAULT 'default.png',
    phone VARCHAR(20),
    gender ENUM('Male', 'Female', 'Other'),
    date_of_birth DATE,
    address TEXT,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- -----------------------------------------------
-- Table: doctors
-- -----------------------------------------------
CREATE TABLE doctors (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    specialization VARCHAR(100) NOT NULL,
    qualification VARCHAR(255),
    experience_years INT DEFAULT 0,
    consultation_fee DECIMAL(10,2) DEFAULT 0.00,
    bio TEXT,
    available_days VARCHAR(255) DEFAULT 'Monday,Tuesday,Wednesday,Thursday,Friday',
    start_time TIME DEFAULT '09:00:00',
    end_time TIME DEFAULT '17:00:00',
    slot_duration INT DEFAULT 30,
    is_approved TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- -----------------------------------------------
-- Table: patients
-- -----------------------------------------------
CREATE TABLE patients (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    blood_group ENUM('A+','A-','B+','B-','AB+','AB-','O+','O-') DEFAULT NULL,
    allergies TEXT,
    emergency_contact VARCHAR(20),
    emergency_contact_name VARCHAR(100),
    insurance_number VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- -----------------------------------------------
-- Table: appointments
-- -----------------------------------------------
CREATE TABLE appointments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    patient_id INT NOT NULL,
    doctor_id INT NOT NULL,
    appointment_date DATE NOT NULL,
    appointment_time TIME NOT NULL,
    status ENUM('pending','confirmed','completed','cancelled') DEFAULT 'pending',
    reason TEXT,
    payment_method VARCHAR(50) DEFAULT 'Pay at Clinic',
    payment_status ENUM('unpaid','paid') DEFAULT 'unpaid',
    transaction_id VARCHAR(100) DEFAULT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
    FOREIGN KEY (doctor_id) REFERENCES doctors(id) ON DELETE CASCADE
);

-- -----------------------------------------------
-- Table: prescriptions
-- -----------------------------------------------
CREATE TABLE prescriptions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    appointment_id INT NOT NULL,
    doctor_id INT NOT NULL,
    patient_id INT NOT NULL,
    diagnosis TEXT NOT NULL,
    medicines TEXT NOT NULL,
    instructions TEXT,
    follow_up_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (appointment_id) REFERENCES appointments(id) ON DELETE CASCADE,
    FOREIGN KEY (doctor_id) REFERENCES doctors(id) ON DELETE CASCADE,
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE
);

-- -----------------------------------------------
-- Table: medical_history
-- -----------------------------------------------
CREATE TABLE medical_history (
    id INT PRIMARY KEY AUTO_INCREMENT,
    patient_id INT NOT NULL,
    doctor_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    diagnosis TEXT,
    treatment TEXT,
    visit_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
    FOREIGN KEY (doctor_id) REFERENCES doctors(id) ON DELETE CASCADE
);

-- -----------------------------------------------
-- Table: notifications
-- -----------------------------------------------
CREATE TABLE notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    is_read TINYINT(1) DEFAULT 0,
    type ENUM('appointment','prescription','system','alert') DEFAULT 'system',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- -----------------------------------------------
-- Table: reviews
-- -----------------------------------------------
CREATE TABLE reviews (
    id INT PRIMARY KEY AUTO_INCREMENT,
    patient_id INT NOT NULL,
    doctor_id INT NOT NULL,
    appointment_id INT NOT NULL,
    rating INT CHECK(rating BETWEEN 1 AND 5),
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
    FOREIGN KEY (doctor_id) REFERENCES doctors(id) ON DELETE CASCADE,
    FOREIGN KEY (appointment_id) REFERENCES appointments(id) ON DELETE CASCADE
);

-- -----------------------------------------------
-- Table: contact_messages
-- -----------------------------------------------
CREATE TABLE contact_messages (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    subject VARCHAR(255),
    message TEXT NOT NULL,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- -----------------------------------------------
-- Default Admin Account
-- Password: admin123 (hashed)
-- -----------------------------------------------
INSERT INTO users (full_name, email, password, role, phone, gender) VALUES
('Super Admin', 'admin@mediconnect.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', '01700000000', 'Male');

-- -----------------------------------------------
-- Sample Specializations (for reference)
-- -----------------------------------------------
-- Cardiology, Neurology, Orthopedics, Dermatology,
-- Pediatrics, Gynecology, Psychiatry, General Medicine,
-- Dentistry, Ophthalmology, ENT, Urology

-- -----------------------------------------------
-- Seed Data: Admins, Doctors & Patients
-- Default password for all seed accounts: password
-- -----------------------------------------------
INSERT INTO users (id, full_name, email, password, role, phone, gender, created_at) VALUES
(1, 'Super Admin', 'admin@mediconnect.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', '01700000000', 'Male', '2026-01-01 00:00:00'),
(2, 'Dr. John Smith', 'doctor@mediconnect.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'doctor', '01711111111', 'Male', '2026-01-05 09:00:00'),
(3, 'Dr. Sarah Rahman', 'sarah@mediconnect.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'doctor', '01722222222', 'Female', '2026-01-10 10:00:00'),
(4, 'Dr. Arif Hasan', 'arif@mediconnect.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'doctor', '01733333333', 'Male', '2026-01-15 11:00:00'),
(5, 'Dr. Nusrat Jahan', 'nusrat@mediconnect.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'doctor', '01744444444', 'Female', '2026-02-01 12:00:00'),
(6, 'Dr. Tanvir Ahmed', 'tanvir@mediconnect.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'doctor', '01755555555', 'Male', '2026-02-10 14:00:00'),
(7, 'Jane Doe', 'patient@mediconnect.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'patient', '01722222222', 'Female', '2026-01-12 08:30:00'),
(8, 'Kamal Hossain', 'kamal@mediconnect.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'patient', '01811223344', 'Male', '2026-01-20 09:15:00'),
(9, 'Farhana Akter', 'farhana@mediconnect.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'patient', '01922334455', 'Female', '2026-02-05 11:45:00'),
(10, 'Rafiqul Islam', 'rafiq@mediconnect.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'patient', '01633445566', 'Male', '2026-02-22 13:20:00');

INSERT INTO doctors (id, user_id, specialization, qualification, experience_years, consultation_fee, bio, is_approved) VALUES
(1, 2, 'Cardiology', 'MBBS, MD (Cardiology)', 10, 500.00, 'Senior Consultant in Cardiology with 10+ years of dedicated clinical experience in preventive and interventional cardiovascular care.', 1),
(2, 3, 'Neurology', 'MBBS, FCPS (Neurology)', 12, 700.00, 'Specialist neurologist focusing on migraine management, stroke rehabilitation, and neuro-diagnostics.', 1),
(3, 4, 'Pediatrics', 'MBBS, DCH, MD (Child Health)', 8, 500.00, 'Pediatric specialist with compassionate care for neonates, infant growth milestones, and childhood illnesses.', 1),
(4, 5, 'Dermatology', 'MBBS, DDV (Dermatology)', 9, 600.00, 'Expert clinical and cosmetic dermatologist specializing in chronic eczema, acne treatments, and skin allergies.', 1),
(5, 6, 'Orthopedics', 'MBBS, MS (Orthopedics)', 14, 800.00, 'Chief orthopedic surgeon specializing in joint replacement, sports injury recovery, and bone density preservation.', 1);

INSERT INTO patients (id, user_id, blood_group, allergies, emergency_contact, emergency_contact_name) VALUES
(1, 7, 'B+', 'Penicillin, Dust', '01733333333', 'John Doe'),
(2, 8, 'A+', 'None reported', '01899887766', 'Fatima Hossain'),
(3, 9, 'O+', 'Seafood', '01911223344', 'Rashid Akter'),
(4, 10, 'AB+', 'None', '01655443322', 'Nasima Islam');

INSERT INTO appointments (id, patient_id, doctor_id, appointment_date, appointment_time, status, reason, notes) VALUES
(1, 1, 1, '2026-01-15', '10:00:00', 'completed', 'Routine cardiovascular health screening and blood pressure review', 'Blood pressure noted at 135/85. Heart sounds normal.'),
(2, 1, 1, '2026-03-22', '11:30:00', 'completed', 'Follow-up visit on hypertension medication tolerance', 'Reported zero side-effects. Medication dosage maintained.'),
(3, 1, 2, '2026-05-10', '14:00:00', 'completed', 'Persistent morning tension headaches and neck stiffness', 'Cervical tension diagnosed, posture improvement guided.'),
(4, 1, 1, CURDATE(), '15:00:00', 'confirmed', 'Six-month cardiovascular wellness follow-up', 'Awaiting telehealth conference link.'),
(5, 1, 4, DATE_ADD(CURDATE(), INTERVAL 4 DAY), '16:00:00', 'pending', 'Skin rash consultation on left forearm', 'Attached photo in intake form.'),
(6, 2, 2, '2026-02-18', '09:30:00', 'completed', 'Frequent migraines during screen work', 'Prescribed preventative and rescue protocol.'),
(7, 2, 3, '2026-04-12', '10:30:00', 'completed', 'Child immunization schedule inquiry', 'Completed routine vaccines.'),
(8, 2, 1, '2026-06-08', '11:00:00', 'completed', 'Chest tightness during heavy stair climbing', 'ECG conducted: Normal sinus rhythm.'),
(9, 2, 5, '2026-07-25', '15:30:00', 'completed', 'Knee joint pain after morning jogging', 'Mild patellar strain. Physiotherapy recommended.'),
(10, 3, 4, '2026-03-14', '14:30:00', 'completed', 'Eczema flare-up on hands during dry weather', 'Topical emollient therapy administered.'),
(11, 3, 1, '2026-07-02', '10:00:00', 'completed', 'Family history of coronary artery disease counseling', 'Advised balanced Mediterranean diet and exercise.'),
(12, 3, 2, '2026-08-19', '11:30:00', 'completed', 'Occasional dizziness upon standing suddenly', 'Benign orthostatic hypotension. Hydration increased.'),
(13, 4, 5, '2026-05-28', '16:00:00', 'completed', 'Lower back soreness from office ergonomic seating', 'Core strengthening protocol instructed.'),
(14, 4, 3, '2026-08-04', '09:00:00', 'completed', 'Toddler seasonal cough and cold consultation', 'Viral symptoms, advised hydration and rest.'),
(15, 4, 1, '2026-09-12', '10:30:00', 'completed', 'Annual health checkup and lipid profile review', 'Total cholesterol slightly elevated. Diet plan given.');

INSERT INTO prescriptions (id, appointment_id, doctor_id, patient_id, diagnosis, medicines, instructions, follow_up_date) VALUES
(1, 1, 1, 1, 'Stage 1 Essential Hypertension & Mild Tachycardia', 'Tab. Amlodipine 5mg — 1 tablet daily (Morning, after breakfast)\nTab. Nebivolol 2.5mg — 1 tablet daily (Morning)\nTab. Vitamin D3 40000 IU — 1 capsule weekly', 'Maintain low-sodium diet, drink 2.5L water daily, brisk walking 30 mins 5 days a week.', DATE_ADD(CURDATE(), INTERVAL 30 DAY)),
(2, 2, 1, 1, 'Controlled Hypertension (Maintenance Phase)', 'Tab. Amlodipine 5mg — 1 tablet daily (Morning)\nTab. Vitamin C 500mg — 1 tablet daily (Evening)', 'Continue existing lifestyle habits, monitor BP twice weekly in health log.', DATE_ADD(CURDATE(), INTERVAL 60 DAY)),
(3, 3, 2, 1, 'Chronic Tension-Type Headache', 'Tab. Naproxen 250mg — 1 tablet as needed with food\nTab. Amitriptyline 10mg — 1 tablet at bedtime\nCap. Esomeprazole 20mg — 1 capsule before meals', 'Reduce late-night blue light screen exposure, take 10-minute eye breaks hourly.', DATE_ADD(CURDATE(), INTERVAL 45 DAY));

INSERT INTO reviews (id, patient_id, doctor_id, appointment_id, rating, comment) VALUES
(1, 1, 1, 1, 5, 'Dr. Smith was exceptionally patient, explained my ECG thoroughly, and gave very practical lifestyle advice.'),
(2, 2, 2, 6, 5, 'Remarkable neurologist! Diagnosed my migraine triggers within the first 15 minutes of consultation.'),
(3, 3, 4, 10, 5, 'My eczema resolved completely after following Dr. Nusrat personalized skincare routine. Highly recommended!'),
(4, 4, 5, 13, 4, 'Very knowledgeable orthopedic consultant. Guided my post-injury physical therapy clearly.');

INSERT INTO contact_messages (id, name, email, subject, message, is_read) VALUES
(1, 'Tanvir Ahmed', 'tanvir@gmail.com', 'Online Prescription Delivery', 'Can prescriptions written on MediConnect be delivered directly to local pharmacies in Dhanmondi?', 1),
(2, 'Shirin Sultana', 'shirin@yahoo.com', 'Pediatric Urgent Consultations', 'Do you provide on-call emergency pediatrician booking during weekends?', 0),
(3, 'Mustafizur Rahman', 'mustafiz@outlook.com', 'Corporate Healthcare Packages', 'We want to provide MediConnect telemedicine coverage to our team of 45 employees. Please share package details.', 0);

