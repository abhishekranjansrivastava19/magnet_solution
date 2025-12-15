<?php
// submit.php - handle contact form submissions
// NOTE: Configure DB credentials and admin email below before use.

// Configuration - replace with your real values
$db_host = 'localhost';
$db_user = 'db_user';
$db_pass = 'db_pass';
$db_name = 'magnet_db';
$admin_email = 'admin@yourdomain.com';
$from_email = 'no-reply@yourdomain.com';

// Simple helper to send mail (uses mail()). Configure SMTP on server for reliability.
function send_mail($to, $subject, $body, $from) {
    $headers  = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8" . "\r\n";
    $headers .= "From: " . $from . "\r\n";
    return mail($to, $subject, $body, $headers);
}

// Validate POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo 'Method not allowed';
    exit;
}

$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$service = trim($_POST['service'] ?? '');
$message = trim($_POST['message'] ?? '');

if (empty($name) || empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL) || empty($service)) {
    http_response_code(400);
    echo 'Please provide name, valid email and select a service.';
    exit;
}

// Save to database
$mysqli = new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($mysqli->connect_errno) {
    error_log('DB connection error: '.$mysqli->connect_error);
    // continue - still try to send email
} else {
    $stmt = $mysqli->prepare("INSERT INTO contacts (name, email, phone, service, message, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
    if ($stmt) {
        $stmt->bind_param('sssss', $name, $email, $phone, $service, $message);
        $stmt->execute();
        $stmt->close();
    } else {
        error_log('DB prepare failed: '.$mysqli->error);
    }
    $mysqli->close();
}

// Prepare emails
$subject_admin = "New contact form submission: " . htmlspecialchars($service);
$body_admin = "<p>New contact submission:</p>".
    "<p><strong>Name:</strong> ".htmlspecialchars($name)."</p>".
    "<p><strong>Email:</strong> ".htmlspecialchars($email)."</p>".
    "<p><strong>Phone:</strong> ".htmlspecialchars($phone)."</p>".
    "<p><strong>Service:</strong> ".htmlspecialchars($service)."</p>".
    "<p><strong>Message:</strong><br>".nl2br(htmlspecialchars($message))."</p>";

$subject_user = "We've received your request";
$body_user = "<p>Hi ".htmlspecialchars($name).",</p>".
    "<p>Thanks for contacting Magnet Solutions regarding <strong>".htmlspecialchars($service)."</strong>. We have received your message and will respond within 1-2 business days.</p>".
    "<p>Summary of your message:</p>".
    "<p>".nl2br(htmlspecialchars($message))."</p>".
    "<p>Regards,<br>Magnet Solutions Team</p>";

$mail_admin_ok = send_mail($admin_email, $subject_admin, $body_admin, $from_email);
$mail_user_ok = send_mail($email, $subject_user, $body_user, $from_email);

// Redirect or show a simple message
if ($mail_admin_ok) {
    header('Location: contact.html?success=1');
    exit;
} else {
    // fallback message
    echo 'Submission received. Unable to send notification email at this time.';
}

/*
SQL to create table (run once):

CREATE TABLE contacts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  email VARCHAR(255) NOT NULL,
  phone VARCHAR(64) DEFAULT NULL,
  service VARCHAR(255) DEFAULT NULL,
  message TEXT,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

Configuration notes:
- Replace $db_host/$db_user/$db_pass/$db_name with real database credentials.
- Set $admin_email and $from_email to valid addresses. For reliable delivery configure SMTP on the server or use an SMTP library.
*/

?>
