# MediConnect — Telemedicine & Doctor Appointment System

MediConnect is a web-based telemedicine platform designed to connect patients with certified doctors for seamless online consultations, appointment bookings, and healthcare record management.

This project was built to address common issues in manual appointment scheduling by providing dedicated portals for patients, doctors, and system administrators.

---

## Key Features

### 1. Patient Portal
- Browse and search verified doctors by medical specialization.
- Book appointments based on doctor availability and schedule.
- Choose flexible payment methods (Pay at Clinic / Mobile Banking / Card).
- Real-time appointment status tracking (Pending, Confirmed, Completed, Cancelled).
- Access digital prescriptions with dosages and doctor advice.
- Personal medical history records and health vitals tracking.

### 2. Doctor Portal
- Clean dashboard showing daily schedules and patient appointments.
- Manage appointment requests (accept, complete, or reschedule).
- Digital prescription generator with diagnosis notes and medicine dosages.
- Patient history lookup for past consultations.
- Manage consulting availability, slots, and fees.

### 3. Administrator Panel
- System overview and statistics (total doctors, patients, appointments).
- Doctor verification and onboarding management.
- Patient records and appointment oversight.
- Clinical intelligence and analytical reports with printable charts.

---

## Technology Stack

- **Backend:** PHP 8.x (OOP with PDO/MySQLi Prepared Statements)
- **Database:** MySQL 8.0 (InnoDB Engine)
- **Frontend:** HTML5, CSS3, JavaScript, Bootstrap 5, Font Awesome, Chart.js
- **Environment:** XAMPP (Apache & MySQL) / PHP Built-in Server

---

## Database Overview

The system runs on the `mediconnect_db` database containing 9 interconnected relational tables:
- `users` — Authentication credentials and basic user profile information.
- `doctors` — Specialist details, qualifications, consultation fees, and schedules.
- `patients` — Patient records, blood group, and emergency contact details.
- `appointments` — Booking records, dates, time slots, payment methods, and status workflows.
- `prescriptions` — Clinical diagnoses, prescribed medications, instructions, and follow-up dates.
- `medical_history` — Diagnostic logs and records of past visits.
- `reviews` — Patient feedback and doctor ratings.
- `notifications` — System and appointment alerts.
- `contact_messages` — Inquiries sent through the contact form.

---

## How to Run the Project Locally

### Prerequisites
- XAMPP (with PHP 8.x and MySQL) installed.
- Git (optional, for cloning).

### Setup Steps

1. **Get the Repository:**
   ```bash
   git clone https://github.com/rejwana121/mediconnect.git
   ```
   *(Or place the `mediconnect` folder directly into `C:\xampp\htdocs\mediconnect`)*

2. **Set up the Database:**
   - Open your browser and go to `http://localhost/phpmyadmin`.
   - Create a new database named **`mediconnect_db`**.
   - Click **Import**, choose the file `database.sql` located inside the project root, and click **Go**.

3. **Database Configuration (Optional):**
   - Check `includes/config.php` to verify your database credentials if you use a custom MySQL user or password:
     ```php
     define('DB_HOST', 'localhost');
     define('DB_USER', 'root');
     define('DB_PASS', '');
     define('DB_NAME', 'mediconnect_db');
     ```

4. **Launch the Application:**
   - Start **Apache** and **MySQL** from the XAMPP Control Panel.
   - Open your browser and visit:
     ```text
     http://localhost/mediconnect
     ```
   *(Alternatively, you can open the project folder in VS Code, run `php -S localhost:8000` in the terminal, and visit `http://localhost:8000`)*

---

## Demo Accounts

For testing and demonstration, pre-configured accounts are available:

| Role | Email | Password |
|---|---|---|
| **Administrator** | `admin@mediconnect.com` | `password` |
| **Doctor** | `doctor@mediconnect.com` | `password` |
| **Patient** | `patient@mediconnect.com` | `password` |

---

## Security Practices Followed

- **Password Hashing:** Passwords are encrypted using PHP `password_hash()` with `PASSWORD_BCRYPT`.
- **SQL Injection Prevention:** All SQL queries use prepared statements.
- **XSS Protection:** Form data and user inputs are sanitized before rendering.
- **Session Protection:** Role-based access control prevents unauthorized page access across different user roles.

---

## Author

**Rejwana Akter**  
- GitHub: [@rejwana121](https://github.com/rejwana121)  
- Email: [rejwanashormi@gmail.com](mailto:rejwanashormi@gmail.com)
