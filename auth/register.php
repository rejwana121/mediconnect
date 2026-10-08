<?php require_once '../includes/functions.php'; redirectIfLoggedIn(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account - MediConnect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/auth.css" rel="stylesheet">
</head>
<body>
<div class="auth-wrapper">
    <div class="auth-card" style="max-width: 650px;">
        <!-- Logo & Header -->
        <div class="text-center mb-4">
            <div class="auth-logo">
                <i class="fas fa-heartbeat"></i>
            </div>
            <h2 class="auth-title">Create an Account</h2>
            <p class="auth-subtitle">Join thousands of patients and certified doctors</p>
        </div>

        <?= showAlert() ?>

        <form action="register_process.php" method="POST">
            <!-- Role Selection Pill -->
            <div class="mb-4">
                <label class="form-label d-block text-center mb-2">Select Your Role</label>
                <div class="row g-2">
                    <div class="col-6">
                        <input type="radio" class="btn-check" name="role" id="patient_role" value="patient" checked>
                        <label class="btn btn-outline-primary w-100" for="patient_role">
                            <i class="fas fa-user me-2"></i>I am a Patient
                        </label>
                    </div>
                    <div class="col-6">
                        <input type="radio" class="btn-check" name="role" id="doctor_role" value="doctor">
                        <label class="btn btn-outline-success w-100" for="doctor_role">
                            <i class="fas fa-user-md me-2"></i>I am a Doctor
                        </label>
                    </div>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Full Name</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-user"></i></span>
                        <input type="text" name="full_name" class="form-control" placeholder="Dr. John Doe" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Email Address</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                        <input type="email" name="email" class="form-control" placeholder="john@example.com" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Phone Number</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-phone"></i></span>
                        <input type="text" name="phone" class="form-control" placeholder="+880 1700-000000">
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Gender</label>
                    <select name="gender" class="form-select">
                        <option value="">Select Gender</option>
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Date of Birth</label>
                    <input type="date" name="date_of_birth" class="form-control">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Password</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                        <input type="password" name="password" class="form-control" placeholder="Min 6 characters" required minlength="6">
                    </div>
                </div>
                <div class="col-12">
                    <label class="form-label">Confirm Password</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-shield-alt"></i></span>
                        <input type="password" name="confirm_password" class="form-control" placeholder="Repeat your password" required>
                    </div>
                </div>

                <!-- Doctor-only fields -->
                <div id="doctorFields" style="display:none;" class="col-12">
                    <div class="p-3 my-2 rounded-3" style="background: rgba(0, 137, 123, 0.05); border: 1px dashed rgba(0, 137, 123, 0.3);">
                        <h6 class="fw-bold mb-3" style="color: #00897B;"><i class="fas fa-certificate me-2"></i>Professional Medical Details</h6>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Specialization</label>
                                <select name="specialization" class="form-select">
                                    <option value="">Select Specialization</option>
                                    <option>General Medicine</option>
                                    <option>Cardiology</option>
                                    <option>Neurology</option>
                                    <option>Orthopedics</option>
                                    <option>Dermatology</option>
                                    <option>Pediatrics</option>
                                    <option>Gynecology</option>
                                    <option>Psychiatry</option>
                                    <option>Dentistry</option>
                                    <option>Ophthalmology</option>
                                    <option>ENT</option>
                                    <option>Urology</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Qualifications</label>
                                <input type="text" name="qualification" class="form-control" placeholder="e.g. MBBS, FCPS, MD">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Years of Experience</label>
                                <input type="number" name="experience_years" class="form-control" placeholder="e.g. 8" min="0">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Consultation Fee (৳)</label>
                                <input type="number" name="consultation_fee" class="form-control" placeholder="500" min="0">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Patient-only fields -->
                <div id="patientFields" class="col-12">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Blood Group</label>
                            <select name="blood_group" class="form-select">
                                <option value="">Select Blood Group</option>
                                <option>A+</option><option>A-</option>
                                <option>B+</option><option>B-</option>
                                <option>AB+</option><option>AB-</option>
                                <option>O+</option><option>O-</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Emergency Contact Phone</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-phone-alt"></i></span>
                                <input type="text" name="emergency_contact" class="form-control" placeholder="+880 1800-000000">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-primary w-100 py-3 mt-4">
                <i class="fas fa-user-plus me-2"></i>Complete Registration
            </button>
        </form>

        <p class="text-center mt-4 mb-0" style="font-size: 14px; color: #64748B;">
            Already have an account? <a href="login.php" class="text-primary">Sign in here</a>
        </p>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const doctorRole  = document.getElementById('doctor_role');
    const patientRole = document.getElementById('patient_role');
    const doctorFields  = document.getElementById('doctorFields');
    const patientFields = document.getElementById('patientFields');

    function toggleRoleFields() {
        if (doctorRole.checked) {
            doctorFields.style.display  = 'block';
            patientFields.style.display = 'none';
        } else {
            doctorFields.style.display  = 'none';
            patientFields.style.display = 'block';
        }
    }

    doctorRole.addEventListener('change', toggleRoleFields);
    patientRole.addEventListener('change', toggleRoleFields);
</script>
</body>
</html>
