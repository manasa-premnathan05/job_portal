<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'job_seeker') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized access.']);
    exit();
}

// Validate CSRF token
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    echo json_encode(['success' => false, 'error' => 'CSRF token validation failed.']);
    exit();
}

// Database connection
$conn = new mysqli("127.0.0.1", "root", "", "job_portal");
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed.']);
    exit();
}

$seeker_id = $_SESSION['user_id'];
$job_id = filter_input(INPUT_POST, 'job_id', FILTER_VALIDATE_INT);

if (!$job_id) {
    echo json_encode(['success' => false, 'error' => 'Invalid job ID.']);
    exit();
}

// Check if the user has already applied
$sql = "SELECT application_id FROM applications WHERE seeker_id = ? AND job_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $seeker_id, $job_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    echo json_encode(['success' => false, 'error' => 'You have already applied for this job.']);
    exit();
}

// Insert application
$sql = "INSERT INTO applications (job_id, seeker_id, application_date, status) 
        VALUES (?, ?, CURRENT_TIMESTAMP, 'Submitted')";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $job_id, $seeker_id);
if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>