# Student Hostel Outpass Management System

## 📌 Project Overview
The **Student Outpass Management System** is a web-based application designed to automate and streamline the student outpass request process. It ensures a secure multi-step approval workflow involving students, teachers (HODs), wardens, and gate security.

---

## ⚡ Features
- **Student Panel**
  - Request outpass with leave and return details
  - View history of past requests
  - Restrictions on multiple active requests
  
- **Teacher Panel**
  - Approve or reject student requests with optional comments
  - View approved and rejected requests history
  - Search and filter requests by student name or department

- **Warden Panel**
  - Approve or reject teacher-approved requests with comments
  - View all previous approvals and history
  - Search requests by department

- **Gate Security Panel**
  - Verify and scan student outpasses
  - Update leave and return time upon scanning
  - Maintain history of valid and invalid outpasses
  
- **Email Notifications**
  - Students receive updates at each approval stage
  - Teachers and wardens receive email alerts for pending approvals
  - Uses **EmailJS** for sending notifications

---

## 📂 Tech Stack
- **Frontend**: HTML, CSS, JavaScript
- **Backend**: PHP, MySQL
- **Database**: MySQL with structured tables for users, requests, and approvals
- **Email Integration**: EmailJS for notifications

---

## 🚀 Installation Guide

### 1️⃣ Clone the Repository
```sh
 git clone https://github.com/aswinlegarcon/hostel_outpass_manager.git
 cd hostel_outpass_manager
```

### 2️⃣ Setup Database
- Import the `db/db.sql` file into MySQL.
- Ensure the `users`, `outpass_requests`, and `gate_approvals` tables exist.

### 3️⃣ Configure `db.php`
Modify `db.php` with your database credentials:
```php
$host = "localhost";
$user = "root";
$password = "";
$database = "outpass_system";
$conn = new mysqli($host, $user, $password, $database);
```

### 4️⃣ Setup EmailJS
- Create an account at [EmailJS](https://www.emailjs.com/)
- Add your `service ID`, `template ID`, and `public key` in `student.php`, `teacher.php`, and `warden.php`.
```js
emailjs.init("your_public_key");
```

### 5️⃣ Run the Project
- Start your Apache & MySQL server (XAMPP/WAMP/LAMP) or any server
- Open `http://localhost/foldername/`

---

## 🛠 Usage Instructions
1. **Students**: Login → Request Outpass → Wait for approval → Scan at the gate
2. **Teachers**: Login → View pending requests → Approve/Reject with comments
3. **Wardens**: Login → View teacher-approved requests → Approve/Reject with comments
4. **Gate Security**: Login → Scan approved outpasses → Log leave & return times

---

## 📜 Database Schema
### 🔹 `users` Table
| Column       | Type          | Description |
|-------------|--------------|-------------|
| `id`        | INT (AUTO_INCREMENT) | Unique user ID |
| `name`      | VARCHAR(255)  | User's full name |
| `email`     | VARCHAR(255)  | Login email |
| `password`  | VARCHAR(255)  | Hashed password |
| `role`      | ENUM('student', 'teacher', 'warden', 'gate_security') | User role |
| `department`| VARCHAR(100) | Applicable for students and teachers |
| `roll_no`   | VARCHAR(50)  | Student roll number |
| `room_no`   | VARCHAR(50)  | Student's hostel room |
| `year_of_study` | INT | Year of study |
| `hostel_name` | VARCHAR(100) | Hostel Name |

### 🔹 `outpass_requests` Table
| Column       | Type         | Description |
|-------------|-------------|-------------|
| `id`        | INT (AUTO_INCREMENT) | Unique request ID |
| `student_id`| INT         | Reference to `users.id` |
| `department`| VARCHAR(100)| Department of student |
| `reason`    | TEXT        | Reason for outpass |
| `leave_date`| DATE        | Date of leaving |
| `leave_time`| TIME NULL DEFAULT NULL | Time of leaving (updated at gate) |
| `return_date`| DATE       | Expected return date |
| `return_time`| TIME NULL DEFAULT NULL | Time of return (updated at gate) |
| `status`    | ENUM('pending', 'teacher_approved', 'warden_approved', 'invalid', 'rejected') | Current status |
| `teacher_comment` | TEXT NULL | Optional comment from teacher |
| `warden_comment` | TEXT NULL | Optional comment from warden |

### 🔹 `gate_approvals` Table
| Column       | Type         | Description |
|-------------|-------------|-------------|
| `id`        | INT (AUTO_INCREMENT) | Unique gate approval ID |
| `outpass_id`| INT         | Reference to `outpass_requests.id` |
| `student_id`| INT         | Reference to `users.id` |
| `exit_time` | DATETIME NULL DEFAULT NULL | Time of exit scan |
| `return_time`| DATETIME NULL DEFAULT NULL | Time of re-entry scan |
| `status`    | ENUM('pending', 'completed') | Scan status |

---

## 🔥 Future Enhancements
- **QR Code-Based Scanning**
- **Admin Dashboard for Monitoring**
- **Push Notifications via Firebase**
- **Multi-Role Authentication Enhancements**

---

---

## 📞 Support
For any issues or feature requests, contact:
📧 Email: `aswinkirubanantham@gmail.com`  
🔗 Linked In: https://www.linkedin.com/in/aswin-kirubanantham-356867282/
