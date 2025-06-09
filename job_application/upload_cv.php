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

if (isset($_FILES['cv'])) {
    $seeker_id = $_SESSION['user_id'];
    $upload_dir = "uploads/cvs/";
    
    // Create upload directory if it doesn't exist
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    $cv_file = $upload_dir . $seeker_id . "_" . basename($_FILES['cv']['name']);
    
    // Validate file
    $file_type = strtolower(pathinfo($cv_file, PATHINFO_EXTENSION));
    $file_size = $_FILES['cv']['size'];
    if ($file_type !== 'pdf') {
        echo json_encode(['success' => false, 'error' => 'Only PDF files are allowed.']);
        exit();
    }
    if ($file_size > 2 * 1024 * 1024) { // 2MB limit
        echo json_encode(['success' => false, 'error' => 'File size exceeds 2MB limit.']);
        exit();
    }

    // Move the uploaded file
    if (move_uploaded_file($_FILES['cv']['tmp_name'], $cv_file)) {
        // Update cv_path in applicant_details
        $sql = "UPDATE applicant_details SET cv_path = ? WHERE seeker_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $cv_file, $seeker_id);
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Database error: ' . $stmt->error]);
        }
        $stmt->close();
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to upload CV to server.']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'No file uploaded.']);
}

$conn->close();
?>