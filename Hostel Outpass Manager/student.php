<?php
session_start();
include 'db.php';  // Make sure to create this file
error_reporting(E_ALL);
ini_set('display_errors', 1);
date_default_timezone_set('Asia/Kolkata');

// Enhanced session validation
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Fetch student details from session with additional validation
$student_id = $_SESSION['user_id'];
$name = $_SESSION['name'] ?? 'Unknown';
$department = $_SESSION['department'] ?? 'Not Assigned';
$email = $_SESSION['email'] ?? 'Not Available';
$roll_no = $_SESSION['roll_no'] ?? 'Not Available';
$room_no = $_SESSION['room_no'] ?? 'Not Available';
$year_of_study = $_SESSION['year_of_study'] ?? 'Not Available';
$hostel_name = $_SESSION['hostel_name'] ?? 'Not Available';

// Enhanced history query with error handling
try {
    $history_query = "SELECT * FROM outpass_requests WHERE student_id=? ORDER BY created_at DESC";
    $stmt = $conn->prepare($history_query);
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $history_result = $stmt->get_result();
} catch (Exception $e) {
    error_log("Database error: " . $e->getMessage());
    $history_result = false;
}

// Enhanced pending request check
$pending_request = false;
try {
    $pending_check_query = "SELECT COUNT(*) as count FROM outpass_requests WHERE student_id=? AND status NOT IN ('invalid', 'rejected')";
    $pending_stmt = $conn->prepare($pending_check_query);
    $pending_stmt->bind_param("i", $student_id);
    $pending_stmt->execute();
    $pending_result = $pending_stmt->get_result();
    $pending_request = $pending_result->fetch_assoc()['count'] > 0;
} catch (Exception $e) {
    error_log("Pending check error: " . $e->getMessage());
}

// Enhanced form submission handling
if ($_SERVER['REQUEST_METHOD'] == 'POST' && !$pending_request) {
    $reason = trim($_POST['reason']);
    $leave_date = $_POST['leave_date'];
    $return_date = $_POST['return_date'];
    
    // Enhanced date validation
    $leave_timestamp = strtotime($leave_date);
    $return_timestamp = strtotime($return_date);
    $current_timestamp = strtotime('today');
    
    if ($leave_timestamp < $current_timestamp) {
        $error_message = "Leave date cannot be in the past!";
    } elseif ($return_timestamp < $leave_timestamp) {
        $error_message = "Return date must be after leave date!";
    } elseif (empty($reason) || empty($leave_date) || empty($return_date)) {
        $error_message = "All fields are required!";
    } else {
        try {
            // Get teacher for the department
            $query = "SELECT id, email FROM users WHERE role='teacher' AND department=? LIMIT 1";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("s", $department);
            $stmt->execute();
            $result = $stmt->get_result();
            $teacher = $result->fetch_assoc();

            if ($teacher) {
                $teacher_id = $teacher['id'];
                $teacher_email = "23bai007@kprcas.ac.in"; // Your fixed email for testing

                // Database insertion
                $sql = "INSERT INTO outpass_requests (student_id, department, reason, leave_date, return_date, teacher_id, status) 
                        VALUES (?, ?, ?, ?, ?, ?, 'pending')";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("issssi", $student_id, $department, $reason, $leave_date, $return_date, $teacher_id);

                if ($stmt->execute()) {
                    $_SESSION['success_message'] = "✅ Outpass request submitted successfully!";
                    // The email will be handled by EmailJS
                    header("Location: student.php");
                    exit();
                } else {
                    throw new Exception($conn->error);
                }
            } else {
                $error_message = "No teacher assigned for your department.";
            }
        } catch (Exception $e) {
            error_log("Submission error: " . $e->getMessage());
            $error_message = "An error occurred while submitting your request. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard | KPRCAS Outpass</title>
    <link rel="stylesheet" href="/css/student.css">

    <!-- EmailJS CDN -->
    <script src="https://cdn.jsdelivr.net/npm/@emailjs/browser@3/dist/email.min.js"></script>
    
    <script type="text/javascript">
    // Initialize EmailJS
    (function() {
        emailjs.init("YOUR_PUBLIC_KEY");
    })();

    function validateOutpassRequest(event) {
        event.preventDefault();
        
        const form = document.getElementById('outpassForm');
        const reason = form.querySelector('[name="reason"]').value.trim();
        const leaveDate = form.querySelector('[name="leave_date"]').value;
        const returnDate = form.querySelector('[name="return_date"]').value;

        // Enhanced validation
        if (!reason || !leaveDate || !returnDate) {
            alert('Please fill in all fields');
            return false;
        }

        // Validate dates
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        const leaveDateTime = new Date(leaveDate);
        const returnDateTime = new Date(returnDate);

        if (leaveDateTime < today) {
            alert('Leave date cannot be in the past');
            return false;
        }

        if (returnDateTime < leaveDateTime) {
            alert('Return date must be after leave date');
            return false;
        }

        // Get student details
        const studentName = '<?php echo addslashes($name); ?>';
        const studentEmail = '<?php echo addslashes($email); ?>';
        const teacherEmail = 'Example mail';

        // Prepare email parameters
        const templateParams = {
            to_name: studentName,
            from_name: studentName,
            to_email: teacherEmail,
            student_name: studentName,
            student_email: studentEmail,
            reason: reason,
            leave_date: leaveDate,
            return_date: returnDate,
            message: `Outpass request from ${studentName}\nReason: ${reason}\nLeave Date: ${leaveDate}\nReturn Date: ${returnDate}`
        };

        // Show loading state
        const submitButton = form.querySelector('button[type="submit"]');
        submitButton.innerHTML = 'Sending...';
        submitButton.disabled = true;

        // Send email
        emailjs.send('YOUR_SEVICE_ID', 'YOUR_TEMPLATE_ID', templateParams)
            .then(function(response) {
                console.log('SUCCESS!', response.status, response.text);
                form.submit(); // Submit the form to process PHP side
            })
            .catch(function(error) {
                console.log('FAILED...', error);
                alert('Failed to send email notification. Error: ' + error.text);
                submitButton.innerHTML = 'Submit Request';
                submitButton.disabled = false;
            });

        return false;
    }

    // Enhanced page section handling
    function showSection(sectionId) {
        document.getElementById("request-outpass").style.display = "none";
        document.getElementById("view-outpass").style.display = "none";
        document.getElementById(sectionId).style.display = "block";
    }

    // Initialize page
    document.addEventListener("DOMContentLoaded", function() {
        showSection("view-outpass");
        
        // Set minimum dates for date inputs
        const today = new Date().toISOString().split('T')[0];
        const leaveDateInput = document.querySelector('input[name="leave_date"]');
        const returnDateInput = document.querySelector('input[name="return_date"]');
        
        if (leaveDateInput && returnDateInput) {
            leaveDateInput.min = today;
            returnDateInput.min = today;
            
            // Update return date minimum when leave date changes
            leaveDateInput.addEventListener('change', function() {
                returnDateInput.min = this.value;
            });
        }
    });
    </script>
</head>
<body>
    <div class="profile-container">
        <img src="profile.png" alt="Profile Icon" class="profile-icon">
        <div class="profile-info">
            <p><strong><?= htmlspecialchars($name) ?></strong></p>
            <p>Department: <?= htmlspecialchars($department) ?></p>
            <p>Email: <?= htmlspecialchars($email) ?></p>
            <p>Room No: <?= htmlspecialchars($room_no) ?></p>
            <p>Roll No: <?= htmlspecialchars($roll_no) ?></p>
            <p>Year: <?= htmlspecialchars($year_of_study) ?></p>
            <p>Hostel: <?= htmlspecialchars($hostel_name) ?></p>
            <a href="logout.php" class="logout-btn">Logout</a>
        </div>
    </div>

    <div class="button-container">
        <button onclick="showSection('request-outpass')">Request Outpass</button>
        <button onclick="showSection('view-outpass')">View Approved Outpasses</button>
    </div>

    <?php if (isset($error_message)): ?>
        <div class="error-message"><?= htmlspecialchars($error_message) ?></div>
    <?php endif; ?>

    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="success-message"><?= htmlspecialchars($_SESSION['success_message']) ?></div>
        <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>

    <div class="container" id="view-outpass">
        <h2>Outpass History</h2>
        <?php if ($history_result && $history_result->num_rows > 0): ?>
            <table>
                <tr>
                    <th>Reason</th>
                    <th>Leave Date</th>
                    <th>Return Date</th>
                    <th>Leave Time</th>
                    <th>Return Time</th>
                    <th>Status</th>
                    <th>Teacher Comment</th>
                    <th>Warden Comment</th>
                </tr>
                <?php while ($row = $history_result->fetch_assoc()) : ?>
                    <tr>
                        <td><?= htmlspecialchars($row['reason']); ?></td>
                        <td><?= htmlspecialchars($row['leave_date']); ?></td>
                        <td><?= htmlspecialchars($row['return_date']); ?></td>
                        <td><?= (!empty($row['leave_time'])) ? date("h:i A", strtotime($row['leave_time'])) : "<span style='color:gray;'>NOT SCANNED</span>"; ?></td>
                        <td><?= (!empty($row['return_time'])) ? date("h:i A", strtotime($row['return_time'])) : "<span style='color:gray;'>NOT SCANNED</span>"; ?></td>
                        <td><?= ucfirst(htmlspecialchars($row['status'])); ?></td>
                        <td><?= htmlspecialchars($row['teacher_comment'] ?? 'No Comment'); ?></td>
                        <td><?= htmlspecialchars($row['warden_comment'] ?? 'No Comment'); ?></td>
                    </tr>
                <?php endwhile; ?>
            </table>
        <?php else: ?>
            <p>No outpass history found.</p>
        <?php endif; ?>
    </div>

    <div class="container" id="request-outpass">
        <h2>Request Outpass</h2>
        <?php if ($pending_request): ?>
            <p class="warning-message">You already have a pending or approved outpass request.</p>
        <?php else: ?>
            <form id="outpassForm" action="" method="post" onsubmit="return validateOutpassRequest(event)">
                <label for="reason">Reason for Outpass:</label>
                <textarea name="reason" required></textarea>
                <label for="leave_date">Leave Date:</label>
                <input type="date" name="leave_date" required>
                <label for="return_date">Return Date:</label>
                <input type="date" name="return_date" required>
                <button type="submit">Submit Request</button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>