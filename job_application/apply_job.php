<?php
session_start();
header('Content-Type: application/json');

// Check if the user is logged in as a job seeker
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'job_seeker') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit();
}

// Verify CSRF token
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    echo json_encode(['success' => false, 'error' => 'Invalid CSRF token']);
    exit();
}

// Validate job_id
if (!isset($_POST['job_id']) || !is_numeric($_POST['job_id'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid job ID']);
    exit();
}

// Database connection
$conn = new mysqli("127.0.0.1", "root", "", "job_portal");
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit();
}

$seeker_id = $_SESSION['user_id'];
$job_id = intval($_POST['job_id']);

try {
    // Check if user has a CV
    $cv_check_sql = "SELECT cv_path FROM applicant_details WHERE seeker_id = ? AND cv_path IS NOT NULL";
    $cv_stmt = $conn->prepare($cv_check_sql);
    $cv_stmt->bind_param("i", $seeker_id);
    $cv_stmt->execute();
    $cv_result = $cv_stmt->get_result();
    
    if ($cv_result->num_rows === 0) {
        echo json_encode(['success' => false, 'error' => 'Please upload your CV before applying']);
        exit();
    }
    $cv_stmt->close();
    
    // Check if job exists and is active
    $job_check_sql = "SELECT job_id FROM job_postings WHERE job_id = ? AND is_active = 1";
    $job_stmt = $conn->prepare($job_check_sql);
    $job_stmt->bind_param("i", $job_id);
    $job_stmt->execute();
    $job_result = $job_stmt->get_result();
    
    if ($job_result->num_rows === 0) {
        echo json_encode(['success' => false, 'error' => 'Job not found or no longer active']);
        exit();
    }
    $job_stmt->close();
    
    // Check if user has already applied for this job
    $existing_check_sql = "SELECT application_id FROM applications WHERE job_id = ? AND seeker_id = ?";
    $existing_stmt = $conn->prepare($existing_check_sql);
    $existing_stmt->bind_param("ii", $job_id, $seeker_id);
    $existing_stmt->execute();
    $existing_result = $existing_stmt->get_result();
    
    if ($existing_result->num_rows > 0) {
        echo json_encode(['success' => false, 'error' => 'You have already applied for this job']);
        exit();
    }
    $existing_stmt->close();
    
    // Insert application
    $apply_sql = "INSERT INTO applications (job_id, seeker_id, application_date, status) VALUES (?, ?, CURRENT_TIMESTAMP, 'Submitted')";
    $apply_stmt = $conn->prepare($apply_sql);
    $apply_stmt->bind_param("ii", $job_id, $seeker_id);
    
    if ($apply_stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Application submitted successfully']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to submit application']);
    }
    
    $apply_stmt->close();
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}

$conn->close();
?>