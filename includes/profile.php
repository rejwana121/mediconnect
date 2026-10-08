<?php
require_once '../includes/functions.php';
requireLogin();

$user = getCurrentUser();
$role = $_SESSION['role'];

// Get role-specific record
if ($role === 'doctor') {
    $stmt = $conn->prepare("SELECT * FROM doctors WHERE user_id = ?");
    $stmt->bind_param("i", $user['id']);
    $stmt->execute();
    $extra = $stmt->get_result()->fetch_assoc();
} elseif ($role === 'patient') {
    $stmt = $conn->prepare("SELECT * FROM patients WHERE user_id = ?");
    $stmt->bind_param("i", $user['id']);
    $stmt->execute();
    $extra = $stmt->get_result()->fetch_assoc();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'update_profile';

    if ($action === 'update_profile') {
        $full_name     = sanitize($_POST['full_name']);
        $phone         = sanitize($_POST['phone']);
        $gender        = sanitize($_POST['gender']);
        $date_of_birth = sanitize($_POST['date_of_birth']);
        $address       = sanitize($_POST['address'] ?? '');

        $stmt = $conn->prepare("UPDATE users SET full_name=?, phone=?, gender=?, date_of_birth=?, address=? WHERE id=?");
        $stmt->bind_param("sssssi", $full_name, $phone, $gender, $date_of_birth, $address, $user['id']);
        $stmt->execute();

        // Update role-specific data
        if ($role === 'doctor') {
            $bio = sanitize($_POST['bio'] ?? '');
            $fee = floatval($_POST['consultation_fee'] ?? 0);
            $qualification = sanitize($_POST['qualification'] ?? '');
            $experience_years = intval($_POST['experience_years'] ?? 0);

            $stmt2 = $conn->prepare("UPDATE doctors SET bio=?, consultation_fee=?, qualification=?, experience_years=? WHERE user_id=?");
            $stmt2->bind_param("sdsii", $bio, $fee, $qualification, $experience_years, $user['id']);
            $stmt2->execute();
        } elseif ($role === 'patient') {
            $blood_group       = sanitize($_POST['blood_group'] ?? '');
            $allergies         = sanitize($_POST['allergies'] ?? '');
            $emergency_contact = sanitize($_POST['emergency_contact'] ?? '');
            $emergency_name    = sanitize($_POST['emergency_contact_name'] ?? '');

            $stmt2 = $conn->prepare("UPDATE patients SET blood_group=?, allergies=?, emergency_contact=?, emergency_contact_name=? WHERE user_id=?");
            $stmt2->bind_param("ssssi", $blood_group, $allergies, $emergency_contact, $emergency_name, $user['id']);
            $stmt2->execute();
        }

        $_SESSION['user_name'] = $full_name;
        setAlert('success', 'Profile information updated successfully!');
    } elseif ($action === 'change_password') {
        $current_pwd = $_POST['current_password'];
        $new_pwd     = $_POST['new_password'];
        $confirm_pwd = $_POST['confirm_password'];

        if (verifyPassword($current_pwd, $user['password'])) {
            if ($new_pwd === $confirm_pwd) {
                if (strlen($new_pwd) >= 6) {
                    $hashed = hashPassword($new_pwd);
                    $pstmt = $conn->prepare("UPDATE users SET password=? WHERE id=?");
                    $pstmt->bind_param("si", $hashed, $user['id']);
                    $pstmt->execute();
                    setAlert('success', 'Security credentials updated successfully!');
                } else {
                    setAlert('danger', 'New password must be at least 6 characters long.');
                }
            } else {
                setAlert('danger', 'New passwords do not match. Please verify.');
            }
        } else {
            setAlert('danger', 'Current password entered is incorrect.');
        }
    }
    header("Location: profile.php");
    exit();
}

// Refresh data
$user = getCurrentUser();
if ($role === 'doctor') {
    $stmt = $conn->prepare("SELECT * FROM doctors WHERE user_id = ?");
    $stmt->bind_param("i", $user['id']);
    $stmt->execute();
    $extra = $stmt->get_result()->fetch_assoc();
} elseif ($role === 'patient') {
    $stmt = $conn->prepare("SELECT * FROM patients WHERE user_id = ?");
    $stmt->bind_param("i", $user['id']);
    $stmt->execute();
    $extra = $stmt->get_result()->fetch_assoc();
}

$unreadCount = countUnreadNotifications($conn, $_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Settings - MediConnect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/dashboard.css" rel="stylesheet">
    <style>
        .profile-hero {
            background: linear-gradient(135deg, #0284C7 0%, #0369A1 60%, #0D9488 100%);
            border-radius: 20px;
            padding: 32px 36px;
            color: white;
            margin-bottom: 24px;
            box-shadow: 0 10px 25px -5px rgba(2, 132, 199, 0.25);
            position: relative;
            overflow: hidden;
        }
        .profile-hero::after {
            content: '';
            position: absolute;
            right: -20px;
            top: -40px;
            width: 200px;
            height: 200px;
            background: radial-gradient(circle, rgba(255,255,255,0.15) 0%, transparent 70%);
            border-radius: 50%;
        }
        .avatar-frame {
            width: 88px;
            height: 88px;
            border-radius: 22px;
            background: white;
            padding: 4px;
            box-shadow: 0 8px 20px rgba(0,0,0,0.15);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 36px;
            font-weight: 800;
            color: #0284C7;
        }
        .info-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(255,255,255,0.15);
            backdrop-filter: blur(10px);
            padding: 5px 14px;
            border-radius: 50px;
            font-size: 13px;
            font-weight: 600;
        }
        .profile-tab-btn {
            border-radius: 12px !important;
            padding: 12px 20px !important;
            font-weight: 700 !important;
            font-size: 14px !important;
            color: #64748B !important;
            border: 1px solid transparent !important;
            transition: all 0.2s ease !important;
        }
        .profile-tab-btn.active {
            background: #E0F2FE !important;
            color: #0284C7 !important;
            border-color: #BAE6FD !important;
        }
        .form-control, .form-select {
            border-radius: 12px !important;
            padding: 11px 16px !important;
            border: 1.5px solid #E2E8F0 !important;
            font-size: 14.5px !important;
            font-weight: 500 !important;
            color: #0F172A !important;
            background-color: #F8FAFC !important;
        }
        .form-control:focus, .form-select:focus {
            background-color: #FFFFFF !important;
            border-color: #0284C7 !important;
            box-shadow: 0 0 0 4px rgba(2, 132, 199, 0.12) !important;
        }
        .form-label {
            font-size: 13.5px;
            font-weight: 700;
            color: #334155;
            margin-bottom: 6px;
        }
    </style>
</head>
<body>

<!-- DYNAMIC SIDEBAR BASED ON ROLE -->
<div class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <div class="brand-icon-pill">
            <i class="fas fa-heartbeat"></i>
        </div>
        <span>Medi<b>Connect</b></span>
    </div>
    <ul class="sidebar-nav">
        <?php if($role === 'admin'): ?>
            <li class="nav-item"><a href="../admin/dashboard.php" class="nav-link"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a></li>
            <li class="nav-item"><a href="../admin/doctors.php" class="nav-link"><i class="fas fa-user-md"></i> <span>Doctors</span></a></li>
            <li class="nav-item"><a href="../admin/patients.php" class="nav-link"><i class="fas fa-users"></i> <span>Patients</span></a></li>
            <li class="nav-item"><a href="../admin/appointments.php" class="nav-link"><i class="fas fa-calendar-check"></i> <span>Appointments</span></a></li>
            <li class="nav-item"><a href="../admin/reports.php" class="nav-link"><i class="fas fa-chart-bar"></i> <span>Reports</span></a></li>
            <li class="nav-item"><a href="../admin/messages.php" class="nav-link"><i class="fas fa-envelope"></i> <span>Messages</span></a></li>
            <li class="nav-item"><a href="../admin/profile.php" class="nav-link active"><i class="fas fa-user-cog"></i> <span>Profile</span></a></li>
        <?php elseif($role === 'doctor'): ?>
            <li class="nav-item"><a href="../doctor/dashboard.php" class="nav-link"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a></li>
            <li class="nav-item"><a href="../doctor/appointments.php" class="nav-link"><i class="fas fa-calendar-check"></i> <span>Appointments</span></a></li>
            <li class="nav-item"><a href="../doctor/patients.php" class="nav-link"><i class="fas fa-users"></i> <span>My Patients</span></a></li>
            <li class="nav-item"><a href="../doctor/prescriptions.php" class="nav-link"><i class="fas fa-file-medical"></i> <span>Prescriptions</span></a></li>
            <li class="nav-item"><a href="../doctor/schedule.php" class="nav-link"><i class="fas fa-clock"></i> <span>My Schedule</span></a></li>
            <li class="nav-item"><a href="../doctor/profile.php" class="nav-link active"><i class="fas fa-user-cog"></i> <span>Profile</span></a></li>
        <?php elseif($role === 'patient'): ?>
            <li class="nav-item"><a href="../patient/dashboard.php" class="nav-link"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a></li>
            <li class="nav-item"><a href="../patient/find_doctors.php" class="nav-link"><i class="fas fa-search"></i> <span>Find Doctors</span></a></li>
            <li class="nav-item"><a href="../patient/book_appointment.php" class="nav-link"><i class="fas fa-calendar-plus"></i> <span>Book Appointment</span></a></li>
            <li class="nav-item"><a href="../patient/appointments.php" class="nav-link"><i class="fas fa-calendar-check"></i> <span>My Appointments</span></a></li>
            <li class="nav-item"><a href="../patient/prescriptions.php" class="nav-link"><i class="fas fa-file-medical"></i> <span>Prescriptions</span></a></li>
            <li class="nav-item"><a href="../patient/medical_history.php" class="nav-link"><i class="fas fa-history"></i> <span>Medical History</span></a></li>
            <li class="nav-item"><a href="../patient/profile.php" class="nav-link active"><i class="fas fa-user-cog"></i> <span>Profile</span></a></li>
        <?php endif; ?>
        <li class="nav-item mt-4 pt-3 border-top"><a href="../auth/logout.php" class="nav-link text-danger"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a></li>
    </ul>
</div>

<!-- MAIN CONTENT -->
<div class="main-content" id="main-content">
    <nav class="top-navbar">
        <button class="btn btn-link sidebar-toggle" onclick="toggleSidebar()"><i class="fas fa-bars fa-lg"></i></button>
        <div class="navbar-title">Account Settings & Profile</div>
        <div class="navbar-right">
            <div class="dropdown me-3">
                <button class="btn btn-link position-relative" data-bs-toggle="dropdown">
                    <i class="fas fa-bell fa-lg text-muted"></i>
                    <?php if($unreadCount > 0): ?><span class="notification-badge"><?= $unreadCount ?></span><?php endif; ?>
                </button>
                <div class="dropdown-menu dropdown-menu-end notification-dropdown">
                    <?php $notifs = getUnreadNotifications($conn, $_SESSION['user_id']); foreach($notifs as $n): ?>
                        <div class="dropdown-item">
                            <small class="text-muted d-block"><?= timeAgo($n['created_at']) ?></small>
                            <span class="fw-semibold"><?= htmlspecialchars($n['message']) ?></span>
                        </div>
                    <?php endforeach; if(empty($notifs)): ?>
                        <div class="text-center text-muted p-3">No new notifications</div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="dropdown">
                <button class="btn btn-link d-flex align-items-center text-decoration-none" data-bs-toggle="dropdown">
                    <div class="user-avatar me-2"><?= strtoupper(substr($_SESSION['user_name'], 0, 1)) ?></div>
                    <div class="text-start d-none d-md-block">
                        <div class="fw-bold text-dark" style="font-size:13.5px;"><?= htmlspecialchars($_SESSION['user_name']) ?></div>
                        <small class="text-muted text-capitalize"><?= $role ?></small>
                    </div>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item text-danger" href="../auth/logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="content-area">
        <?= showAlert() ?>

        <!-- Rich Header Profile Banner -->
        <div class="profile-hero">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                <div class="d-flex align-items-center gap-4">
                    <div class="avatar-frame">
                        <?= strtoupper(substr($user['full_name'], 0, 1)) ?>
                    </div>
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <h3 class="fw-bold mb-0 text-white"><?= htmlspecialchars($user['full_name']) ?></h3>
                            <i class="fas fa-check-circle text-warning fs-5" title="Verified Account"></i>
                        </div>
                        <p class="mb-2 text-white-50" style="font-size: 14.5px;">
                            <?= htmlspecialchars($user['email']) ?>
                            <?php if(!empty($user['phone'])): ?> · <?= htmlspecialchars($user['phone']) ?><?php endif; ?>
                        </p>
                        <div class="d-flex flex-wrap gap-2">
                            <span class="info-pill"><i class="fas fa-shield-alt"></i> <?= strtoupper($role) ?></span>
                            <span class="info-pill"><i class="fas fa-calendar-check"></i> Member Since <?= date('M Y', strtotime($user['created_at'])) ?></span>
                            <span class="info-pill" style="background: rgba(16, 185, 129, 0.25); color: #A7F3D0;"><i class="fas fa-circle" style="font-size:8px;"></i> Active & Verified</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <!-- Left Info Cards -->
            <div class="col-lg-4">
                <!-- Profile Status Card -->
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-id-badge text-primary me-2"></i>Account Overview</span>
                        <span class="badge bg-success">Healthy</span>
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled mb-0">
                            <li class="d-flex justify-content-between py-2 border-bottom">
                                <span class="text-muted"><i class="fas fa-user-tag me-2"></i>Role</span>
                                <span class="fw-bold text-capitalize"><?= $role ?></span>
                            </li>
                            <li class="d-flex justify-content-between py-2 border-bottom">
                                <span class="text-muted"><i class="fas fa-fingerprint me-2"></i>User ID</span>
                                <span class="fw-bold">MED-<?= str_pad($user['id'], 5, '0', STR_PAD_LEFT) ?></span>
                            </li>
                            <li class="d-flex justify-content-between py-2 border-bottom">
                                <span class="text-muted"><i class="fas fa-venus-mars me-2"></i>Gender</span>
                                <span class="fw-bold"><?= $user['gender'] ?? 'Not Specified' ?></span>
                            </li>
                            <li class="d-flex justify-content-between py-2 border-bottom">
                                <span class="text-muted"><i class="fas fa-birthday-cake me-2"></i>Date of Birth</span>
                                <span class="fw-bold"><?= !empty($user['date_of_birth']) ? formatDate($user['date_of_birth']) : 'Not Specified' ?></span>
                            </li>
                            <li class="d-flex justify-content-between py-2">
                                <span class="text-muted"><i class="fas fa-map-marker-alt me-2"></i>Address</span>
                                <span class="fw-bold text-end" style="max-width: 160px;"><?= !empty($user['address']) ? htmlspecialchars($user['address']) : 'Not added' ?></span>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Role Specific Highlights -->
                <?php if($role === 'doctor' && isset($extra)): ?>
                <div class="card mb-4" style="border-left: 4px solid #0D9488 !important;">
                    <div class="card-header">
                        <i class="fas fa-stethoscope text-success me-2"></i>Practice Details
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <small class="text-muted d-block">Specialization</small>
                            <span class="fw-bold fs-6 text-dark"><?= $extra['specialization'] ?></span>
                        </div>
                        <div class="mb-3">
                            <small class="text-muted d-block">Consultation Fee</small>
                            <span class="fw-bold fs-5 text-primary">৳<?= number_format($extra['consultation_fee'], 0) ?></span>
                        </div>
                        <div>
                            <small class="text-muted d-block">Experience</small>
                            <span class="fw-bold"><?= $extra['experience_years'] ?> Years Clinical Practice</span>
                        </div>
                    </div>
                </div>
                <?php elseif($role === 'patient' && isset($extra)): ?>
                <div class="card mb-4" style="border-left: 4px solid #0284C7 !important;">
                    <div class="card-header">
                        <i class="fas fa-notes-medical text-primary me-2"></i>Medical Summary
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <small class="text-muted d-block">Blood Group</small>
                            <span class="badge bg-danger fs-6"><?= $extra['blood_group'] ?: 'Not recorded' ?></span>
                        </div>
                        <div class="mb-3">
                            <small class="text-muted d-block">Emergency Contact</small>
                            <span class="fw-bold text-dark"><?= htmlspecialchars($extra['emergency_contact'] ?: 'None') ?></span>
                        </div>
                        <div>
                            <small class="text-muted d-block">Recorded Allergies</small>
                            <span class="fw-bold text-muted"><?= htmlspecialchars($extra['allergies'] ?: 'No allergies listed') ?></span>
                        </div>
                    </div>
                </div>
                <?php else: ?>
                <div class="card mb-4" style="border-left: 4px solid #6366F1 !important;">
                    <div class="card-header">
                        <i class="fas fa-server text-primary me-2"></i>System Privileges
                    </div>
                    <div class="card-body">
                        <p class="small text-muted mb-2">You are logged in as a <strong>System Administrator</strong> with root rights.</p>
                        <span class="badge bg-primary mb-1">Doctor Verifications</span>
                        <span class="badge bg-success mb-1">Appointment Database</span>
                        <span class="badge bg-dark mb-1">Analytics Engine</span>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Right Main Form Tabs -->
            <div class="col-lg-8">
                <div class="card shadow-sm">
                    <div class="card-header p-3">
                        <ul class="nav nav-pills gap-2" id="profileTabs">
                            <li class="nav-item">
                                <a class="nav-link profile-tab-btn active" data-bs-toggle="pill" href="#tab-personal">
                                    <i class="fas fa-user-edit me-2"></i>Personal Details
                                </a>
                            </li>
                            <?php if($role !== 'admin'): ?>
                            <li class="nav-item">
                                <a class="nav-link profile-tab-btn" data-bs-toggle="pill" href="#tab-role">
                                    <i class="fas fa-briefcase-medical me-2"></i><?= $role === 'doctor' ? 'Clinical Profile' : 'Medical Records' ?>
                                </a>
                            </li>
                            <?php endif; ?>
                            <li class="nav-item">
                                <a class="nav-link profile-tab-btn" data-bs-toggle="pill" href="#tab-security">
                                    <i class="fas fa-lock me-2"></i>Password & Security
                                </a>
                            </li>
                        </ul>
                    </div>

                    <div class="card-body p-4">
                        <div class="tab-content">
                            <!-- Tab 1: Personal -->
                            <div class="tab-pane fade show active" id="tab-personal">
                                <form method="POST">
                                    <input type="hidden" name="action" value="update_profile">
                                    <h6 class="fw-bold text-dark mb-3">Basic Information</h6>
                                    <div class="row g-3 mb-4">
                                        <div class="col-md-6">
                                            <label class="form-label">Full Name <span class="text-danger">*</span></label>
                                            <input type="text" name="full_name" class="form-control" value="<?= htmlspecialchars($user['full_name']) ?>" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Email Address</label>
                                            <input type="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" disabled title="Contact support to change email">
                                            <small class="text-muted" style="font-size:12px;">Email is verified and linked to your credentials.</small>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Phone Number</label>
                                            <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($user['phone'] ?? '') ?>" placeholder="+880 1700-000000">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Gender</label>
                                            <select name="gender" class="form-select">
                                                <option value="">Select Gender</option>
                                                <?php foreach(['Male','Female','Other'] as $g): ?>
                                                    <option <?= $user['gender']===$g?'selected':'' ?>><?= $g ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Date of Birth</label>
                                            <input type="date" name="date_of_birth" class="form-control" value="<?= htmlspecialchars($user['date_of_birth'] ?? '') ?>">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Residential Address</label>
                                            <input type="text" name="address" class="form-control" value="<?= htmlspecialchars($user['address'] ?? '') ?>" placeholder="e.g. Dhaka, Bangladesh">
                                        </div>
                                    </div>

                                    <div class="text-end">
                                        <button type="submit" class="btn btn-primary px-4 py-2">
                                            <i class="fas fa-save me-2"></i>Save Personal Details
                                        </button>
                                    </div>
                                </form>
                            </div>

                            <!-- Tab 2: Role Details -->
                            <?php if($role !== 'admin'): ?>
                            <div class="tab-pane fade" id="tab-role">
                                <form method="POST">
                                    <input type="hidden" name="action" value="update_profile">
                                    <input type="hidden" name="full_name" value="<?= htmlspecialchars($user['full_name']) ?>">
                                    <input type="hidden" name="phone" value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
                                    <input type="hidden" name="gender" value="<?= htmlspecialchars($user['gender'] ?? '') ?>">
                                    <input type="hidden" name="date_of_birth" value="<?= htmlspecialchars($user['date_of_birth'] ?? '') ?>">
                                    <input type="hidden" name="address" value="<?= htmlspecialchars($user['address'] ?? '') ?>">

                                    <?php if($role === 'doctor'): ?>
                                        <h6 class="fw-bold text-dark mb-3">Medical Credentials</h6>
                                        <div class="row g-3 mb-4">
                                            <div class="col-md-6">
                                                <label class="form-label">Qualification Degrees</label>
                                                <input type="text" name="qualification" class="form-control" value="<?= htmlspecialchars($extra['qualification'] ?? '') ?>" placeholder="e.g. MBBS, FCPS, MD">
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Consultation Fee (৳)</label>
                                                <input type="number" name="consultation_fee" class="form-control" value="<?= $extra['consultation_fee'] ?? 500 ?>" min="0">
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Experience (Years)</label>
                                                <input type="number" name="experience_years" class="form-control" value="<?= $extra['experience_years'] ?? 0 ?>" min="0">
                                            </div>
                                            <div class="col-12">
                                                <label class="form-label">Professional Biography</label>
                                                <textarea name="bio" class="form-control" rows="4" placeholder="Briefly describe your medical specialties, background, and patient care philosophy..."><?= htmlspecialchars($extra['bio'] ?? '') ?></textarea>
                                            </div>
                                        </div>
                                    <?php elseif($role === 'patient'): ?>
                                        <h6 class="fw-bold text-dark mb-3">Health & Emergency Profile</h6>
                                        <div class="row g-3 mb-4">
                                            <div class="col-md-6">
                                                <label class="form-label">Blood Group</label>
                                                <select name="blood_group" class="form-select">
                                                    <option value="">Select Blood Group</option>
                                                    <?php foreach(['A+','A-','B+','B-','AB+','AB-','O+','O-'] as $bg): ?>
                                                        <option <?= ($extra['blood_group']??'')===$bg?'selected':'' ?>><?= $bg ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Emergency Contact Name</label>
                                                <input type="text" name="emergency_contact_name" class="form-control" value="<?= htmlspecialchars($extra['emergency_contact_name'] ?? '') ?>" placeholder="e.g. Father, Spouse">
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Emergency Phone Number</label>
                                                <input type="text" name="emergency_contact" class="form-control" value="<?= htmlspecialchars($extra['emergency_contact'] ?? '') ?>" placeholder="+880 1700-000000">
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Known Allergies</label>
                                                <input type="text" name="allergies" class="form-control" value="<?= htmlspecialchars($extra['allergies'] ?? '') ?>" placeholder="e.g. Penicillin, Dust, Peanuts">
                                            </div>
                                        </div>
                                    <?php endif; ?>

                                    <div class="text-end">
                                        <button type="submit" class="btn btn-primary px-4 py-2">
                                            <i class="fas fa-save me-2"></i>Save Details
                                        </button>
                                    </div>
                                </form>
                            </div>
                            <?php endif; ?>

                            <!-- Tab 3: Security -->
                            <div class="tab-pane fade" id="tab-security">
                                <form method="POST">
                                    <input type="hidden" name="action" value="change_password">
                                    <h6 class="fw-bold text-dark mb-2">Change Password</h6>
                                    <p class="text-muted small mb-4">Protect your account with a secure password containing letters, numbers, and symbols.</p>

                                    <div class="row g-3 mb-4">
                                        <div class="col-12">
                                            <label class="form-label">Current Password <span class="text-danger">*</span></label>
                                            <input type="password" name="current_password" class="form-control" placeholder="••••••••" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">New Password <span class="text-danger">*</span></label>
                                            <input type="password" name="new_password" class="form-control" placeholder="Minimum 6 characters" minlength="6" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Confirm New Password <span class="text-danger">*</span></label>
                                            <input type="password" name="confirm_password" class="form-control" placeholder="Repeat new password" required>
                                        </div>
                                    </div>

                                    <div class="text-end">
                                        <button type="submit" class="btn btn-danger px-4 py-2">
                                            <i class="fas fa-shield-alt me-2"></i>Update Password
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function toggleSidebar() { 
    document.getElementById('sidebar').classList.toggle('collapsed'); 
    document.getElementById('main-content').classList.toggle('expanded'); 
}
</script>
</body>
</html>
