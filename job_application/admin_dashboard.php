<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Security headers
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");
header("Referrer-Policy: strict-origin-when-cross-origin");

// Database connection
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'job_portal';

try {
    $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
    
    if ($conn->connect_error) {
        throw new Exception("Database connection failed: " . $conn->connect_error);
    }
    
    $conn->set_charset("utf8mb4");
    
    // Create job_postings table if it doesn't exist
    $table_sql = "CREATE TABLE IF NOT EXISTS `job_postings` (
        `job_id` int(11) NOT NULL AUTO_INCREMENT,
        `title` varchar(100) NOT NULL,
        `description` text NOT NULL,
        `requirements` text NOT NULL,
        `location` varchar(100) NOT NULL,
        `job_type` enum('Full-time','Part-time','Contract','Temporary') NOT NULL,
        `shift_schedule` varchar(100) DEFAULT NULL,
        `salary` varchar(50) DEFAULT NULL,
        `benefits` text DEFAULT NULL,
        `is_active` tinyint(1) DEFAULT 1,
        `posted_by` int(11) DEFAULT 1,
        `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
        `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
        `job_categories` enum('Marketing','Sales','Education','Development','Tally Experts','Other') DEFAULT NULL,
        PRIMARY KEY (`job_id`),
        CONSTRAINT `job_postings_ibfk_2` FOREIGN KEY (`posted_by`) REFERENCES `admins` (`admin_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    if (!$conn->query($table_sql)) {
        error_log("Error creating table: " . $conn->error);
        throw new Exception("Failed to create database table: " . $conn->error);
    }
    
} catch (Exception $e) {
    error_log("Database connection error: " . $e->getMessage());
    die("System maintenance in progress. Please try again later. Error: " . $e->getMessage());
}

// Security functions
function validateInput($data) {
    return htmlspecialchars(trim(stripslashes($data)));
}

function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    // CSRF Protection
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
        exit;
    }
    
    $action = $_POST['action'];
    
    try {
        switch ($action) {
            case 'create':
                $requiredFields = ['title', 'description', 'requirements', 'location', 'job_type'];
                foreach ($requiredFields as $field) {
                    if (empty($_POST[$field])) {
                        throw new Exception("Field '$field' is required");
                    }
                }
                
                $title = validateInput($_POST['title']);
                $description = validateInput($_POST['description']);
                $requirements = validateInput($_POST['requirements']);
                $location = validateInput($_POST['location']);
                $job_type = validateInput($_POST['job_type']);
                $shift_schedule = validateInput($_POST['shift_schedule'] ?? '');
                $salary = validateInput($_POST['salary'] ?? '');
                $benefits = validateInput($_POST['benefits'] ?? '');
                $job_categories = validateInput($_POST['job_categories'] ?? '');
                $posted_by = 1; // Default admin user
                
                $stmt = $conn->prepare("INSERT INTO job_postings (title, description, requirements, location, job_type, shift_schedule, salary, benefits, job_categories, posted_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                
                if (!$stmt) {
                    throw new Exception("Prepare failed: " . $conn->error);
                }
                
                $stmt->bind_param("sssssssssi", $title, $description, $requirements, $location, $job_type, $shift_schedule, $salary, $benefits, $job_categories, $posted_by);
                
                if ($stmt->execute()) {
                    echo json_encode(['success' => true, 'message' => 'Job posted successfully', 'job_id' => $conn->insert_id]);
                } else {
                    throw new Exception('Failed to create job posting: ' . $stmt->error);
                }
                $stmt->close();
                break;
                
            case 'update':
                if (empty($_POST['job_id'])) {
                    throw new Exception('Job ID is required');
                }
                
                $requiredFields = ['title', 'description', 'requirements', 'location', 'job_type'];
                foreach ($requiredFields as $field) {
                    if (empty($_POST[$field])) {
                        throw new Exception("Field '$field' is required");
                    }
                }
                
                $job_id = (int)$_POST['job_id'];
                $title = validateInput($_POST['title']);
                $description = validateInput($_POST['description']);
                $requirements = validateInput($_POST['requirements']);
                $location = validateInput($_POST['location']);
                $job_type = validateInput($_POST['job_type']);
                $shift_schedule = validateInput($_POST['shift_schedule'] ?? '');
                $salary = validateInput($_POST['salary'] ?? '');
                $benefits = validateInput($_POST['benefits'] ?? '');
                $job_categories = validateInput($_POST['job_categories'] ?? '');
                
                $stmt = $conn->prepare("UPDATE job_postings SET title = ?, description = ?, requirements = ?, location = ?, job_type = ?, shift_schedule = ?, salary = ?, benefits = ?, job_categories = ? WHERE job_id = ?");
                
                if (!$stmt) {
                    throw new Exception("Prepare failed: " . $conn->error);
                }
                
                $stmt->bind_param("sssssssssi", $title, $description, $requirements, $location, $job_type, $shift_schedule, $salary, $benefits, $job_categories, $job_id);
                
                if ($stmt->execute()) {
                    if ($stmt->affected_rows > 0) {
                        echo json_encode(['success' => true, 'message' => 'Job updated successfully']);
                    } else {
                        echo json_encode(['success' => false, 'message' => 'No changes made or job not found']);
                    }
                } else {
                    throw new Exception('Failed to update job posting: ' . $stmt->error);
                }
                $stmt->close();
                break;
                
            case 'delete':
                if (empty($_POST['job_id'])) {
                    throw new Exception('Job ID is required');
                }
                
                $job_id = (int)$_POST['job_id'];
                $stmt = $conn->prepare("DELETE FROM job_postings WHERE job_id = ?");
                
                if (!$stmt) {
                    throw new Exception("Prepare failed: " . $conn->error);
                }
                
                $stmt->bind_param("i", $job_id);
                
                if ($stmt->execute()) {
                    if ($stmt->affected_rows > 0) {
                        echo json_encode(['success' => true, 'message' => 'Job deleted successfully']);
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Job not found']);
                    }
                } else {
                    throw new Exception('Failed to delete job posting: ' . $stmt->error);
                }
                $stmt->close();
                break;
                
            case 'toggle_status':
                if (empty($_POST['job_id'])) {
                    throw new Exception('Job ID is required');
                }
                
                $job_id = (int)$_POST['job_id'];
                $stmt = $conn->prepare("UPDATE job_postings SET is_active = NOT is_active WHERE job_id = ?");
                
                if (!$stmt) {
                    throw new Exception("Prepare failed: " . $conn->error);
                }
                
                $stmt->bind_param("i", $job_id);
                
                if ($stmt->execute()) {
                    if ($stmt->affected_rows > 0) {
                        echo json_encode(['success' => true, 'message' => 'Job status updated successfully']);
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Job not found']);
                    }
                } else {
                    throw new Exception('Failed to update job status: ' . $stmt->error);
                }
                $stmt->close();
                break;
                
            case 'update_application_status':
                if (empty($_POST['application_id']) || empty($_POST['status'])) {
                    throw new Exception('Application ID and status are required');
                }
                
                $application_id = (int)$_POST['application_id'];
                $status = validateInput($_POST['status']);
                
                $stmt = $conn->prepare("UPDATE applications SET status = ? WHERE application_id = ?");
                
                if (!$stmt) {
                    throw new Exception("Prepare failed: " . $conn->error);
                }
                
                $stmt->bind_param("si", $status, $application_id);
                
                if ($stmt->execute()) {
                    echo json_encode(['success' => true, 'message' => 'Status updated successfully']);
                } else {
                    throw new Exception('Failed to update status: ' . $stmt->error);
                }
                $stmt->close();
                break;
                
            default:
                throw new Exception('Invalid action');
        }
    } catch (Exception $e) {
        error_log("Database operation error: " . $e->getMessage());
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// Handle GET requests for data
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action'])) {
    header('Content-Type: application/json');
    
    $action = $_GET['action'];
    
    try {
        switch ($action) {
            case 'get_jobs':
                $search = $_GET['search'] ?? '';
                $category = $_GET['category'] ?? '';
                $status = $_GET['status'] ?? '';
                
                $query = "SELECT * FROM job_postings WHERE 1=1";
                $params = [];
                $types = "";
                
                if (!empty($search)) {
                    $query .= " AND (title LIKE ? OR location LIKE ?)";
                    $searchTerm = "%$search%";
                    $params[] = $searchTerm;
                    $params[] = $searchTerm;
                    $types .= "ss";
                }
                
                if (!empty($category)) {
                    $query .= " AND job_categories = ?";
                    $params[] = $category;
                    $types .= "s";
                }
                
                if ($status !== '') {
                    $query .= " AND is_active = ?";
                    $params[] = (int)$status;
                    $types .= "i";
                }
                
                $query .= " ORDER BY created_at DESC";
                
                if (!empty($params)) {
                    $stmt = $conn->prepare($query);
                    if (!$stmt) {
                        throw new Exception("Prepare failed: " . $conn->error);
                    }
                    $stmt->bind_param($types, ...$params);
                    $stmt->execute();
                    $result = $stmt->get_result();
                } else {
                    $result = $conn->query($query);
                    if (!$result) {
                        throw new Exception("Query failed: " . $conn->error);
                    }
                }
                
                $jobs = [];
                while ($row = $result->fetch_assoc()) {
                    $jobs[] = $row;
                }
                
                echo json_encode(['success' => true, 'data' => $jobs]);
                if (isset($stmt)) $stmt->close();
                break;
                
            case 'get_job':
                if (empty($_GET['job_id'])) {
                    throw new Exception('Job ID is required');
                }
                
                $job_id = (int)$_GET['job_id'];
                $stmt = $conn->prepare("SELECT * FROM job_postings WHERE job_id = ?");
                
                if (!$stmt) {
                    throw new Exception("Prepare failed: " . $conn->error);
                }
                
                $stmt->bind_param("i", $job_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $job = $result->fetch_assoc();
                
                if ($job) {
                    echo json_encode(['success' => true, 'data' => $job]);
                } else {
                    throw new Exception('Job not found');
                }
                $stmt->close();
                break;
                
            case 'get_jobs_with_applications':
                $query = "SELECT 
                    j.job_id,
                    j.title,
                    COUNT(a.application_id) AS application_count
                FROM job_postings j
                LEFT JOIN applications a ON j.job_id = a.job_id
                GROUP BY j.job_id, j.title
                HAVING application_count > 0
                ORDER BY j.title";
                
                $result = $conn->query($query);
                if (!$result) {
                    throw new Exception("Query failed: " . $conn->error);
                }
                
                $jobs = [];
                while ($row = $result->fetch_assoc()) {
                    $jobs[] = $row;
                }
                
                echo json_encode(['success' => true, 'data' => $jobs]);
                break;
                
            case 'get_applications':
                $job_id = $_GET['job_id'] ?? '';
                
                $query = "SELECT 
                    a.application_id, 
                    a.job_id, 
                    a.seeker_id, 
                    a.application_date, 
                    a.cover_letter, 
                    a.status,
                    j.title AS job_title,
                    js.full_name,
                    js.email,
                    js.phone,
                    ad.cv_path
                FROM applications a
                JOIN job_postings j ON a.job_id = j.job_id
                JOIN job_seekers js ON a.seeker_id = js.seeker_id
                LEFT JOIN applicant_details ad ON a.seeker_id = ad.seeker_id
                WHERE a.job_id = ?";
                
                $stmt = $conn->prepare($query);
                if (!$stmt) {
                    throw new Exception("Prepare failed: " . $conn->error);
                }
                
                $stmt->bind_param("i", $job_id);
                $stmt->execute();
                $result = $stmt->get_result();
                
                $applications = [];
                while ($row = $result->fetch_assoc()) {
                    $applications[] = $row;
                }
                
                echo json_encode(['success' => true, 'data' => $applications]);
                $stmt->close();
                break;
                
            default:
                throw new Exception('Invalid action');
        }
    } catch (Exception $e) {
        error_log("Database query error: " . $e->getMessage());
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

$csrf_token = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HRMS - Professional Job Management Dashboard</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <style>
        :root {
            --primary: #667eea;
            --primary-dark: #764ba2;
            --secondary: #f093fb;
            --success: #4facfe;
            --danger: #f093fb;
            --warning: #ffecd2;
            --info: #a8edea;
            --light: #f8f9fa;
            --dark: #343a40;
            --gradient-primary: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --gradient-secondary: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            --gradient-success: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            --box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
            --box-shadow-hover: 0 0.25rem 2rem 0 rgba(58, 59, 69, 0.25);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
        }

        /* Navigation Styles */
        .bg-gradient-primary {
            background: var(--gradient-primary) !important;
        }

        .navbar-brand {
            font-size: 1.5rem;
            font-weight: 700;
            letter-spacing: 0.5px;
        }

        /* Sidebar Styles */
        .sidebar {
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            z-index: 100;
            padding: 48px 0 0;
            background: linear-gradient(180deg, #ffffff 0%, #f8f9fc 100%);
            box-shadow: inset -1px 0 0 rgba(0, 0, 0, 0.1);
        }

        .sidebar-link {
            color: #5a5c69;
            font-weight: 500;
            padding: 1rem 1.5rem;
            border-radius: 0.35rem;
            margin: 0.25rem 0.5rem;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            text-decoration: none;
            display: block;
        }

        .sidebar-link:hover,
        .sidebar-link.active {
            color: white;
            background: var(--gradient-primary);
            transform: translateX(5px);
            box-shadow: var(--box-shadow);
            text-decoration: none;
        }

        .sidebar-link::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s;
        }

        .sidebar-link:hover::before {
            left: 100%;
        }

        /* Main Content */
        main {
            padding-top: 60px;
        }

        .content-section {
            display: none;
            animation: fadeInUp 0.5s ease-out;
        }

        .content-section.active {
            display: block;
        }

        /* Card Animations */
        .animate-card {
            transform: translateY(20px);
            opacity: 0;
            transition: all 0.6s ease;
            animation: slideInUp 0.6s ease forwards;
        }

        .animate-card:nth-child(2) { animation-delay: 0.1s; }
        .animate-card:nth-child(3) { animation-delay: 0.2s; }
        .animate-card:nth-child(4) { animation-delay: 0.3s; }

        /* Statistics Cards */
        .border-left-primary {
            border-left: 0.25rem solid var(--primary) !important;
        }

        .border-left-success {
            border-left: 0.25rem solid #1cc88a !important;
        }

        .border-left-info {
            border-left: 0.25rem solid #36b9cc !important;
        }

        .border-left-warning {
            border-left: 0.25rem solid #f6c23e !important;
        }

        .card {
            border: none;
            border-radius: 0.75rem;
            box-shadow: var(--box-shadow);
            transition: all 0.3s ease;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: var(--box-shadow-hover);
        }

        /* Form Styles */
        .form-control-animated,
        .form-select {
            border: 2px solid #e3e6f0;
            border-radius: 0.5rem;
            padding: 0.75rem 1rem;
            transition: all 0.3s ease;
            background: #fff;
        }

        .form-control-animated:focus,
        .form-select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
            transform: translateY(-2px);
        }

        .form-label {
            font-weight: 600;
            color: #5a5c69;
            margin-bottom: 0.5rem;
        }

        /* Button Animations */
        .btn-animated {
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
            border-radius: 0.5rem;
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            letter-spacing: 0.5px;
        }

        .btn-animated:hover {
            transform: translateY(-2px);
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        }

        .btn-animated::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s;
        }

        .btn-animated:hover::before {
            left: 100%;
        }

        .btn-primary {
            background: var(--gradient-primary);
            border: none;
        }

        .btn-primary:hover {
            background: var(--gradient-primary);
            filter: brightness(1.1);
        }

        /* Table Styles */
        .table {
            background: white;
            border-radius: 0.5rem;
            overflow: hidden;
        }

        .table thead th {
            background: var(--gradient-primary);
            color: white;
            font-weight: 600;
            border: none;
            padding: 1rem;
        }

        .table tbody tr {
            transition: all 0.3s ease;
        }

        .table tbody tr:hover {
            background: rgba(102, 126, 234, 0.05);
            transform: translateX(5px);
        }

        .table td {
            padding: 1rem;
            vertical-align: middle;
            border-top: 1px solid #e3e6f0;
        }

        /* Status Badges */
        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 2rem;
            font-weight: 600;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-active {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            color: white;
        }

        .status-inactive {
            background: linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%);
            color: #8b4513;
        }

        .status-submitted {
            background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);
            color: #5a5c69;
        }

        .status-review {
            background: linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%);
            color: #8b4513;
        }

        .status-shortlisted {
            background: linear-gradient(135deg, #c2e9fb 0%, #a1c4fd 100%);
            color: #1a237e;
        }

        .status-rejected {
            background: linear-gradient(135deg, #ff9a9e 0%, #fad0c4 100%);
            color: #b71c1c;
        }

        .status-hired {
            background: linear-gradient(135deg, #a1ffce 0%, #faffd1 100%);
            color: #1b5e20;
        }

        /* Action Buttons */
        .action-btn {
            padding: 0.5rem;
            margin: 0 0.25rem;
            border-radius: 50%;
            width: 35px;
            height: 35px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            border: none;
        }

        .action-btn:hover {
            transform: scale(1.1);
            box-shadow: 0 0.25rem 0.5rem rgba(0, 0, 0, 0.2);
        }

        .btn-edit {
            background: var(--gradient-success);
            color: white;
        }

        .btn-delete {
            background: var(--gradient-secondary);
            color: white;
        }

        .btn-toggle {
            background: linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%);
            color: #8b4513;
        }

        /* Application Card */
        .application-card {
            border-left: 4px solid var(--primary);
            transition: all 0.3s ease;
            margin-bottom: 1rem;
        }

        .application-card:hover {
            transform: translateY(-3px);
            box-shadow: var(--box-shadow-hover);
        }

        .cv-preview {
            max-height: 500px;
            overflow-y: auto;
            border: 1px solid #e3e6f0;
            border-radius: 0.5rem;
            padding: 1rem;
            background: white;
        }

        /* Loading Overlay */
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.9);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 9999;
        }

        .loading-overlay.show {
            display: flex;
        }

        .spinner-border {
            color: var(--primary);
        }

        /* Toast Notifications */
        .toast {
            border-radius: 0.75rem;
            box-shadow: var(--box-shadow);
            border: none;
        }

        .toast-header {
            background: var(--gradient-primary);
            color: white;
            border: none;
        }

        /* Animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes slideInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes pulse {
            0% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.05);
            }
            100% {
                transform: scale(1);
            }
        }

        .pulse-animation {
            animation: pulse 2s infinite;
        }

        /* Form Validation */
        .is-invalid {
            border-color: #dc3545;
            animation: shake 0.5s ease-in-out;
        }

        @keyframes shake {
            0%, 20%, 40%, 60%, 80%, 100% {
                transform: translateX(0);
            }
            10%, 30%, 50%, 70%, 90% {
                transform: translateX(-5px);
            }
        }

        /* Success States */
        .form-control.is-valid,
        .form-select.is-valid {
            border-color: #28a745;
            box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.25);
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }
            
            .sidebar.show {
                transform: translateX(0);
            }
            
            main {
                margin-left: 0 !important;
            }
            
            .animate-card {
                margin-bottom: 1rem;
            }
            
            .table-responsive {
                font-size: 0.875rem;
            }
            
            .action-btn {
                width: 30px;
                height: 30px;
                font-size: 0.75rem;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation Header -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-gradient-primary shadow-lg">
        <div class="container-fluid">
            <a class="navbar-brand fw-bold" href="#">
                <i class="fas fa-briefcase me-2"></i>
                HRMS Portal
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link text-light" href="#">
                    <i class="fas fa-user-circle me-1"></i>
                    Admin Dashboard
                </a>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse">
                <div class="position-sticky pt-3">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link active sidebar-link" href="#" data-section="dashboard">
                                <i class="fas fa-tachometer-alt me-2"></i>
                                Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link sidebar-link" href="#" data-section="create-job">
                                <i class="fas fa-plus-circle me-2"></i>
                                Create Job
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link sidebar-link" href="#" data-section="manage-jobs">
                                <i class="fas fa-list-ul me-2"></i>
                                Manage Jobs
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link sidebar-link" href="#" data-section="view-applications">
                                <i class="fas fa-users me-2"></i>
                                View Applications
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <!-- Main Content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <!-- Dashboard Section -->
                <div id="dashboard" class="content-section active">
                    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                        <h1 class="h2 fw-bold">Job Posting Dashboard</h1>
                    </div>
                    
                    <!-- Stats Cards -->
                    <div class="row mb-4">
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-left-primary shadow h-100 py-2 animate-card">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                                Total Jobs Posted
                                            </div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="total-jobs">0</div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-briefcase fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-left-success shadow h-100 py-2 animate-card">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                                Active Positions
                                            </div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="active-jobs">0</div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-left-info shadow h-100 py-2 animate-card">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                                Categories
                                            </div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">6</div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-tags fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-left-warning shadow h-100 py-2 animate-card">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                                This Month
                                            </div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="monthly-jobs">0</div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-calendar fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Jobs Table -->
                    <div class="card shadow mb-4 animate-card">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Recent Job Postings</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" id="recent-jobs-table">
                                    <thead>
                                        <tr>
                                            <th>Title</th>
                                            <th>Category</th>
                                            <th>Location</th>
                                            <th>Type</th>
                                            <th>Status</th>
                                            <th>Created</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- Populated by JavaScript -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Create Job Section -->
                <div id="create-job" class="content-section">
                    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                        <h1 class="h2 fw-bold">Create New Job Posting</h1>
                    </div>
                    
                    <div class="card shadow animate-card">
                        <div class="card-body">
                            <form id="job-form" class="needs-validation" novalidate>
                                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                <input type="hidden" name="action" value="create">
                                <input type="hidden" name="job_id" id="edit-job-id">
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="title" class="form-label">Job Title *</label>
                                        <input type="text" class="form-control form-control-animated" id="title" name="title" required>
                                        <div class="invalid-feedback">Please provide a valid job title.</div>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label for="location" class="form-label">Location *</label>
                                        <input type="text" class="form-control form-control-animated" id="location" name="location" required>
                                        <div class="invalid-feedback">Please provide a valid location.</div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label for="job_type" class="form-label">Job Type *</label>
                                        <select class="form-select form-control-animated" id="job_type" name="job_type" required>
                                            <option value="">Select Job Type</option>
                                            <option value="Full-time">Full-time</option>
                                            <option value="Part-time">Part-time</option>
                                            <option value="Contract">Contract</option>
                                            <option value="Temporary">Temporary</option>
                                        </select>
                                        <div class="invalid-feedback">Please select a job type.</div>
                                    </div>
                                    
                                    <div class="col-md-4 mb-3">
                                        <label for="job_categories" class="form-label">Category</label>
                                        <select class="form-select form-control-animated" id="job_categories" name="job_categories">
                                            <option value="">Select Category</option>
                                            <option value="Marketing">Marketing</option>
                                            <option value="Sales">Sales</option>
                                            <option value="Education">Education</option>
                                            <option value="Development">Development</option>
                                            <option value="Tally Experts">Tally Experts</option>
                                            <option value="Other">Other</option>
                                        </select>
                                    </div>
                                    
                                    <div class="col-md-4 mb-3">
                                        <label for="salary" class="form-label">Salary Range</label>
                                        <input type="text" class="form-control form-control-animated" id="salary" name="salary" placeholder="e.g., ₹50,000 - ₹70,000">
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="shift_schedule" class="form-label">Shift Schedule</label>
                                    <input type="text" class="form-control form-control-animated" id="shift_schedule" name="shift_schedule" placeholder="e.g., 9 AM - 5 PM, Monday to Friday">
                                </div>
                                
                                <div class="mb-3">
                                    <label for="description" class="form-label">Job Description *</label>
                                    <textarea class="form-control form-control-animated" id="description" name="description" rows="5" required placeholder="Describe the role, responsibilities, and company culture..."></textarea>
                                    <div class="invalid-feedback">Please provide a job description.</div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="requirements" class="form-label">Requirements *</label>
                                    <textarea class="form-control form-control-animated" id="requirements" name="requirements" rows="4" required placeholder="List the required skills, experience, and qualifications..."></textarea>
                                    <div class="invalid-feedback">Please provide job requirements.</div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="benefits" class="form-label">Benefits & Perks</label>
                                    <textarea class="form-control form-control-animated" id="benefits" name="benefits" rows="3" placeholder="Health insurance, paid time off, retirement plans, etc..."></textarea>
                                </div>
                                
                                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                    <button type="button" class="btn btn-secondary me-md-2" id="cancel-btn">Cancel</button>
                                    <button type="submit" class="btn btn-primary btn-animated">
                                        <i class="fas fa-save me-2"></i>
                                        <span id="submit-text">Create Job Posting</span>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Manage Jobs Section -->
                <div id="manage-jobs" class="content-section">
                    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                        <h1 class="h2 fw-bold">Manage Job Postings</h1>
                        <button class="btn btn-primary btn-animated" onclick="showCreateForm()">
                            <i class="fas fa-plus me-2"></i>
                            New Job Posting
                        </button>
                    </div>
                    
                    <!-- Filters -->
                    <div class="card shadow mb-4 animate-card">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label for="search-filter" class="form-label">Search</label>
                                    <input type="text" class="form-control form-control-animated" id="search-filter" placeholder="Search by title or location...">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="category-filter" class="form-label">Category</label>
                                    <select class="form-select form-control-animated" id="category-filter">
                                        <option value="">All Categories</option>
                                        <option value="Marketing">Marketing</option>
                                        <option value="Sales">Sales</option>
                                        <option value="Education">Education</option>
                                        <option value="Development">Development</option>
                                        <option value="Tally Experts">Tally Experts</option>
                                        <option value="Other">Other</option>
                                    </select>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="status-filter" class="form-label">Status</label>
                                    <select class="form-select form-control-animated" id="status-filter">
                                        <option value="">All Status</option>
                                        <option value="1">Active</option>
                                        <option value="0">Inactive</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Jobs Table -->
                    <div class="card shadow mb-4 animate-card">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">All Job Postings</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" id="jobs-table">
                                    <thead>
                                        <tr>
                                            <th>Title</th>
                                            <th>Category</th>
                                            <th>Location</th>
                                            <th>Type</th>
                                            <th>Status</th>
                                            <th>Created</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- Populated by JavaScript -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- View Applications Section -->
                <div id="view-applications" class="content-section">
                    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                        <h1 class="h2 fw-bold">Job Applications Management</h1>
                    </div>
                    
                    <!-- Job Selection Card -->
                    <div class="card shadow mb-4 animate-card">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Select Job to View Applications</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-8">
                                    <select class="form-select form-control-animated" id="job-selector">
                                        <option value="">-- Select a Job --</option>
                                        <!-- Populated by JavaScript -->
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <button class="btn btn-primary btn-animated w-100" id="load-applications-btn">
                                        <i class="fas fa-search me-2"></i>
                                        View Applications
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Applications Summary Card -->
                    <div class="card shadow mb-4 animate-card" id="applications-summary-card" style="display: none;">
                        <div class="card-header py-3 d-flex justify-content-between align-items-center">
                            <h6 class="m-0 font-weight-bold text-primary" id="job-title-header">Applications for: </h6>
                            <div>
                                <span class="badge bg-primary me-2" id="total-applications">0 Total</span>
                                <span class="badge bg-success me-2" id="new-applications">0 New</span>
                                <span class="badge bg-info" id="hired-applications">0 Hired</span>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" id="applications-table">
                                    <thead>
                                        <tr>
                                            <th>Applicant</th>
                                            <th>Contact</th>
                                            <th>Applied On</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- Populated by JavaScript -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Application Details Modal -->
                    <div class="modal fade" id="applicationDetailsModal" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <div class="modal-header bg-gradient-primary text-white">
                                    <h5 class="modal-title" id="applicantNameModal">Application Details</h5>
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="row mb-4">
                                        <div class="col-md-6">
                                            <h6 class="fw-bold">Personal Information</h6>
                                            <p><i class="fas fa-user me-2"></i> <span id="modalFullName"></span></p>
                                            <p><i class="fas fa-envelope me-2"></i> <span id="modalEmail"></span></p>
                                            <p><i class="fas fa-phone me-2"></i> <span id="modalPhone"></span></p>
                                        </div>
                                        <div class="col-md-6">
                                            <h6 class="fw-bold">Application Details</h6>
                                            <p><i class="fas fa-briefcase me-2"></i> <span id="modalJobTitle"></span></p>
                                            <p><i class="fas fa-calendar me-2"></i> Applied on: <span id="modalAppliedDate"></span></p>
                                            <p><i class="fas fa-tag me-2"></i> Status: <span id="modalStatus"></span></p>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-4">
                                        <h6 class="fw-bold">Cover Letter</h6>
                                        <div class="card p-3 bg-light" id="modalCoverLetter">
                                            No cover letter provided.
                                        </div>
                                    </div>
                                    
                                    <div>
                                        <h6 class="fw-bold">CV/Resume</h6>
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <a href="#" class="btn btn-primary btn-animated" id="downloadCvBtn" target="_blank">
                                                <i class="fas fa-download me-2"></i>
                                                Download CV
                                            </a>
                                            <select class="form-select form-control-animated w-auto" id="statusSelector">
                                                <option value="Submitted">Submitted</option>
                                                <option value="Under Review">Under Review</option>
                                                <option value="Shortlisted">Shortlisted</option>
                                                <option value="Rejected">Rejected</option>
                                                <option value="Hired">Hired</option>
                                            </select>
                                            <button class="btn btn-success btn-animated" id="updateStatusBtn">
                                                <i class="fas fa-save me-2"></i>
                                                Update Status
                                            </button>
                                        </div>
                                        <div class="cv-preview" id="cvPreviewContainer">
                                            <p class="text-muted">CV preview will appear here</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loading">
        <div class="text-center">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-3 text-muted">Processing...</p>
        </div>
    </div>

    <!-- Toast Notification -->
    <div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 10000;">
        <div id="notification-toast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header">
                <i class="fas fa-info-circle me-2"></i>
                <strong class="me-auto">HRMS Notification</strong>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body" id="toast-message">
                Message goes here
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- PDF.js for CV preview -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.11.338/pdf.min.js"></script>
    
    <script>
    // Global variables
    let currentJobs = [];
    let editingJobId = null;
    let currentApplications = [];
    let currentJobId = null;
    let currentApplicationId = null;
    let eventListenersAdded = false; // Prevent duplicate listeners

    // Set PDF.js worker path
    pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.11.338/pdf.worker.min.js';

    // Initialize dashboard
    document.addEventListener('DOMContentLoaded', function() {
        console.log('DOM loaded, initializing dashboard');
        initializeDashboard();
    });

    // Initialize dashboard components
    function initializeDashboard() {
        console.log('Initializing dashboard');
        showSection('dashboard');
        
        // Initialize tooltips
        try {
            const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
            tooltipTriggerList.forEach(el => new bootstrap.Tooltip(el));
        } catch (error) {
            console.error('Error initializing tooltips:', error);
        }
        
        // Setup event listeners only once
        if (!eventListenersAdded) {
            setupEventListeners();
            eventListenersAdded = true;
        }
        
        loadJobs();
        updateDashboardStats();
    }

    // Setup event listeners
    function setupEventListeners() {
        console.log('Setting up event listeners');
        
        // Sidebar navigation
        document.querySelectorAll('.sidebar-link').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const section = this.getAttribute('data-section');
                console.log(`Sidebar link clicked: ${section}`);
                showSection(section);
                
                document.querySelectorAll('.sidebar-link').forEach(l => l.classList.remove('active'));
                this.classList.add('active');
            });
        });
        
        // Job form submission
        const jobForm = document.getElementById('job-form');
        if (jobForm) {
            jobForm.addEventListener('submit', handleJobSubmit);
        } else {
            console.error('Job form not found');
        }
        
        // Cancel button
        const cancelBtn = document.getElementById('cancel-btn');
        if (cancelBtn) {
            cancelBtn.addEventListener('click', function() {
                console.log('Cancel button clicked');
                resetForm();
                showSection('manage-jobs');
            });
        }
        
        // Filter inputs
        const searchFilter = document.getElementById('search-filter');
        const categoryFilter = document.getElementById('category-filter');
        const statusFilter = document.getElementById('status-filter');
        
        if (searchFilter) searchFilter.addEventListener('input', debounce(filterJobs, 300));
        if (categoryFilter) categoryFilter.addEventListener('change', filterJobs);
        if (statusFilter) statusFilter.addEventListener('change', filterJobs);
        
        // View Applications buttons
        const loadApplicationsBtn = document.getElementById('load-applications-btn');
        if (loadApplicationsBtn) {
            loadApplicationsBtn.addEventListener('click', function() {
                console.log('Load Applications button clicked');
                const jobId = document.getElementById('job-selector')?.value;
                if (jobId) {
                    loadApplications(jobId);
                } else {
                    showNotification('Please select a job first', 'error');
                }
            });
        }
        
        const jobSelector = document.getElementById('job-selector');
        if (jobSelector) {
            jobSelector.addEventListener('change', function() {
                console.log('Job selector changed');
                const jobId = this.value;
                if (jobId) {
                    currentJobId = jobId;
                    const selectedOption = this.options[this.selectedIndex];
                    document.getElementById('job-title-header').textContent = `Applications for: ${selectedOption.text}`;
                }
            });
        }
        
        const updateStatusBtn = document.getElementById('updateStatusBtn');
        if (updateStatusBtn) {
            updateStatusBtn.addEventListener('click', function() {
                console.log('Update Status button clicked');
                updateApplicationStatus();
            });
        }
        
        // Setup form validation
        setupFormValidation();
    }

    // Show specific section
    function showSection(sectionId) {
        console.log(`Showing section: ${sectionId}`);
        const section = document.getElementById(sectionId);
        if (!section) {
            console.error(`Section ${sectionId} not found`);
            showNotification(`Section ${sectionId} not found`, 'error');
            return;
        }
        
        document.querySelectorAll('.content-section').forEach(s => s.classList.remove('active'));
        section.classList.add('active');
        
        if (sectionId === 'manage-jobs') {
            loadJobs();
        } else if (sectionId === 'dashboard') {
            updateDashboardStats();
            loadRecentJobs();
        } else if (sectionId === 'view-applications') {
            loadJobSelector();
        }
    }

    // Load job selector dropdown
    async function loadJobSelector() {
        showLoading(true);
        console.log('Loading job selector');
        try {
            const response = await fetch(window.location.pathname + '?action=get_jobs_with_applications');
            if (!response.ok) throw new Error(`HTTP ${response.status}`);
            const data = await response.json();
            
            if (data.success) {
                const selector = document.getElementById('job-selector');
                if (selector) {
                    selector.innerHTML = '<option value="">-- Select a Job --</option>';
                    data.data.forEach(job => {
                        const option = document.createElement('option');
                        option.value = job.job_id;
                        option.textContent = `${job.title} (${job.application_count} applications)`;
                        selector.appendChild(option);
                    });
                }
                showNotification('Jobs loaded successfully', 'success');
            } else {
                showNotification('Error loading jobs: ' + data.message, 'error');
            }
        } catch (error) {
            console.error('Error loading job selector:', error);
            showNotification('Failed to load jobs', 'error');
        } finally {
            showLoading(false);
        }
    }

    // Load applications
    async function loadApplications(jobId) {
        showLoading(true);
        console.log(`Loading applications for job ID: ${jobId}`);
        try {
            const response = await fetch(window.location.pathname + `?action=get_applications&job_id=${jobId}`);
            if (!response.ok) throw new Error(`HTTP ${response.status}`);
            const data = await response.json();
            
            if (data.success) {
                currentApplications = data.data;
                renderApplicationsTable();
                updateApplicationsSummary();
                document.getElementById('applications-summary-card').style.display = 'block';
                showNotification('Applications loaded successfully', 'success');
            } else {
                showNotification('Error: ' + data.message, 'error');
            }
        } catch (error) {
            console.error('Error loading applications:', error);
            showNotification('Failed to load applications', 'error');
        } finally {
            showLoading(false);
        }
    }

    // Render applications table
    function renderApplicationsTable() {
        const tbody = document.querySelector('#applications-table tbody');
        if (!tbody) return;
        
        tbody.innerHTML = '';
        if (currentApplications.length === 0) {
            tbody.innerHTML = '<tr><td colspan="5" class="text-center">No applications found</td></tr>';
            return;
        }
        
        currentApplications.forEach(app => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${escapeHtml(app.full_name)}</td>
                <td>
                    <div>${escapeHtml(app.email)}</div>
                    <small>${escapeHtml(app.phone || 'N/A')}</small>
                </td>
                <td>${formatDate(app.application_date)}</td>
                <td><span class="status-badge ${getStatusClass(app.status)}">${app.status}</span></td>
                <td>
                    <button class="btn action-btn btn-edit" onclick="viewApplicationDetails(${app.application_id})">
                        <i class="fas fa-eye"></i>
                    </button>
                </td>
            `;
            tbody.appendChild(row);
        });
    }

    // Update applications summary
    function updateApplicationsSummary() {
        const total = currentApplications.length;
        const newCount = currentApplications.filter(app => app.status === 'Submitted').length;
        const hiredCount = currentApplications.filter(app => app.status === 'Hired').length;
        
        const totalEl = document.getElementById('total-applications');
        const newEl = document.getElementById('new-applications');
        const hiredEl = document.getElementById('hired-applications');
        
        if (totalEl) totalEl.textContent = `${total} Total`;
        if (newEl) newEl.textContent = `${newCount} New`;
        if (hiredEl) hiredEl.textContent = `${hiredCount} Hired`;
    }

    // View application details
    async function viewApplicationDetails(applicationId) {
        showLoading(true);
        console.log(`Viewing application ID: ${applicationId}`);
        try {
            const app = currentApplications.find(a => a.application_id == applicationId);
            if (!app) throw new Error('Application not found');
            
            currentApplicationId = applicationId;
            
            // Populate modal
            const modal = document.getElementById('applicationDetailsModal');
            if (!modal) throw new Error('Modal not found');
            
            document.getElementById('applicantNameModal').textContent = app.full_name;
            document.getElementById('modalFullName').textContent = app.full_name;
            document.getElementById('modalEmail').textContent = app.email;
            document.getElementById('modalPhone').textContent = app.phone || 'N/A';
            document.getElementById('modalJobTitle').textContent = app.job_title;
            document.getElementById('modalAppliedDate').textContent = formatDate(app.application_date);
            document.getElementById('modalStatus').innerHTML = `<span class="status-badge ${getStatusClass(app.status)}">${app.status}</span>`;
            
            const coverLetterEl = document.getElementById('modalCoverLetter');
            if (coverLetterEl) {
                coverLetterEl.innerHTML = app.cover_letter ? escapeHtml(app.cover_letter) : '<em>No cover letter</em>';
            }
            
            const downloadBtn = document.getElementById('downloadCvBtn');
            const cvPreview = document.getElementById('cvPreviewContainer');
            if (downloadBtn && cvPreview) {
                if (app.cv_path) {
                    downloadBtn.href = app.cv_path;
                    downloadBtn.style.display = 'inline-block';
                    if (app.cv_path.toLowerCase().endsWith('.pdf')) {
                        loadPdfPreview(app.cv_path);
                    } else {
                        cvPreview.innerHTML = '<div class="alert alert-info">Preview not available for this file type</div>';
                    }
                } else {
                    downloadBtn.style.display = 'none';
                    cvPreview.innerHTML = '<div class="alert alert-warning">No CV uploaded</div>';
                }
            }
            
            const statusSelector = document.getElementById('statusSelector');
            if (statusSelector) statusSelector.value = app.status;
            
            new bootstrap.Modal(modal).show();
        } catch (error) {
            console.error('Error viewing application:', error);
            showNotification('Failed to load application details', 'error');
        } finally {
            showLoading(false);
        }
    }

    // Load PDF preview
    async function loadPdfPreview(pdfUrl) {
        const container = document.getElementById('cvPreviewContainer');
        if (!container) return;
        
        try {
            const pdf = await pdfjsLib.getDocument(pdfUrl).promise;
            const page = await pdf.getPage(1);
            const scale = 1.5;
            const viewport = page.getViewport({ scale });
            
            const canvas = document.createElement('canvas');
            const context = canvas.getContext('2d');
            canvas.height = viewport.height;
            canvas.width = viewport.width;
            
            await page.render({ canvasContext: context, viewport }).promise;
            container.innerHTML = '';
            container.appendChild(canvas);
            
            const pageInfo = document.createElement('p');
            pageInfo.textContent = `Page 1 of ${pdf.numPages}`;
            container.appendChild(pageInfo);
        } catch (error) {
            console.error('Error loading PDF:', error);
            container.innerHTML = '<div class="alert alert-danger">Failed to load PDF preview</div>';
        }
    }

    // Update application status
    async function updateApplicationStatus() {
        console.log('Updating application status');
        const statusSelector = document.getElementById('statusSelector');
        if (!statusSelector || !currentApplicationId) return;
        
        const newStatus = statusSelector.value;
        showLoading(true);
        
        try {
            const formData = new FormData();
            formData.append('action', 'update_application_status');
            formData.append('application_id', currentApplicationId);
            formData.append('status', newStatus);
            formData.append('csrf_token', document.querySelector('input[name="csrf_token"]').value);
            
            const response = await fetch(window.location.pathname, {
                method: 'POST',
                body: formData
            });
            if (!response.ok) throw new Error(`HTTP ${response.status}`);
            const data = await response.json();
            
            if (data.success) {
                const app = currentApplications.find(a => a.application_id == currentApplicationId);
                if (app) app.status = newStatus;
                renderApplicationsTable();
                updateApplicationsSummary();
                document.getElementById('modalStatus').innerHTML = `<span class="status-badge ${getStatusClass(newStatus)}">${newStatus}</span>`;
                showNotification('Status updated successfully', 'success');
            } else {
                showNotification('Error: ' + data.message, 'error');
            }
        } catch (error) {
            console.error('Error updating status:', error);
            showNotification('Failed to update status', 'error');
        } finally {
            showLoading(false);
        }
    }

    // Get status class
    function getStatusClass(status) {
        return {
            'Submitted': 'status-submitted',
            'Under Review': 'status-review',
            'Shortlisted': 'status-shortlisted',
            'Rejected': 'status-rejected',
            'Hired': 'status-hired'
        }[status] || 'status-submitted';
    }

    // Load jobs
    async function loadJobs() {
        showLoading(true);
        console.log('Loading jobs');
        try {
            const response = await fetch(window.location.pathname + '?action=get_jobs&t=' + Date.now());
            if (!response.ok) throw new Error(`HTTP ${response.status}`);
            const data = await response.json();
            
            if (data.success) {
                currentJobs = data.data;
                renderJobsTable();
                showNotification('Jobs loaded successfully', 'success');
            } else {
                showNotification('Error: ' + data.message, 'error');
            }
        } catch (error) {
            console.error('Error loading jobs:', error);
            showNotification('Failed to load jobs', 'error');
        } finally {
            showLoading(false);
        }
    }

    // Render jobs table
    function renderJobsTable() {
        const tbody = document.querySelector('#jobs-table tbody');
        if (!tbody) return;
        
        tbody.innerHTML = '';
        if (currentJobs.length === 0) {
            tbody.innerHTML = '<tr><td colspan="7" class="text-center">No jobs found</td></tr>';
            return;
        }
        
        currentJobs.forEach(job => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${escapeHtml(job.title)}</td>
                <td><span class="badge bg-info">${escapeHtml(job.job_categories || 'N/A')}</span></td>
                <td><i class="fas fa-map-marker-alt me-1"></i>${escapeHtml(job.location)}</td>
                <td><span class="badge bg-secondary">${job.job_type}</span></td>
                <td><span class="status-badge ${job.is_active == 1 ? 'status-active' : 'status-inactive'}">${job.is_active == 1 ? 'Active' : 'Inactive'}</span></td>
                <td>${formatDate(job.created_at)}</td>
                <td>
                    <button class="btn action-btn btn-edit" onclick="editJob(${job.job_id})"><i class="fas fa-edit"></i></button>
                    <button class="btn action-btn btn-toggle" onclick="toggleJobStatus(${job.job_id})"><i class="fas fa-toggle-${job.is_active == 1 ? 'on' : 'off'}"></i></button>
                    <button class="btn action-btn btn-delete" onclick="deleteJob(${job.job_id})"><i class="fas fa-trash"></i></button>
                </td>
            `;
            tbody.appendChild(row);
        });
    }

    // Handle job form submission
    async function handleJobSubmit(e) {
        e.preventDefault();
        console.log('Job form submitted');
        
        if (!validateForm()) return;
        
        const form = e.target;
        const submitBtn = form.querySelector('button[type="submit"]');
        const submitText = submitBtn.querySelector('#submit-text');
        
        submitBtn.disabled = true;
        submitText.textContent = 'Processing...';
        
        try {
            const response = await fetch(window.location.pathname, {
                method: 'POST',
                body: new FormData(form)
            });
            if (!response.ok) throw new Error(`HTTP ${response.status}`);
            const data = await response.json();
            
            if (data.success) {
                showNotification(data.message, 'success');
                resetForm();
                loadJobs();
                showSection('manage-jobs');
            } else {
                showNotification('Error: ' + data.message, 'error');
            }
        } catch (error) {
            console.error('Error submitting form:', error);
            showNotification('Failed to save job', 'error');
        } finally {
            submitBtn.disabled = false;
            submitText.textContent = editingJobId ? 'Update Job Posting' : 'Create Job Posting';
        }
    }

    // Edit job
    async function editJob(jobId) {
        console.log(`Editing job ID: ${jobId}`);
        try {
            const response = await fetch(window.location.pathname + `?action=get_job&job_id=${jobId}`);
            if (!response.ok) throw new Error(`HTTP ${response.status}`);
            const data = await response.json();
            
            if (data.success) {
                const job = data.data;
                populateForm(job);
                editingJobId = jobId;
                document.querySelector('input[name="action"]').value = 'update';
                document.getElementById('edit-job-id').value = jobId;
                document.getElementById('submit-text').textContent = 'Update Job Posting';
                showSection('create-job');
            } else {
                showNotification('Error: ' + data.message, 'error');
            }
        } catch (error) {
            console.error('Error editing job:', error);
            showNotification('Failed to load job details', 'error');
        }
    }

    // Populate form
    function populateForm(job) {
        console.log('Populating form');
        document.getElementById('title').value = job.title || '';
        document.getElementById('location').value = job.location || '';
        document.getElementById('job_type').value = job.job_type || '';
        document.getElementById('job_categories').value = job.job_categories || '';
        document.getElementById('salary').value = job.salary || '';
        document.getElementById('shift_schedule').value = job.shift_schedule || '';
        document.getElementById('description').value = job.description || '';
        document.getElementById('requirements').value = job.requirements || '';
        document.getElementById('benefits').value = job.benefits || '';
    }

    // Delete job
    async function deleteJob(jobId) {
        console.log(`Deleting job ID: ${jobId}`);
        if (!confirm('Are you sure you want to delete this job?')) return;
        
        showLoading(true);
        try {
            const formData = new FormData();
            formData.append('action', 'delete');
            formData.append('job_id', jobId);
            formData.append('csrf_token', document.querySelector('input[name="csrf_token"]').value);
            
            const response = await fetch(window.location.pathname, {
                method: 'POST',
                body: formData
            });
            if (!response.ok) throw new Error(`HTTP ${response.status}`);
            const data = await response.json();
            
            if (data.success) {
                showNotification(data.message, 'success');
                loadJobs();
            } else {
                showNotification('Error: ' + data.message, 'error');
            }
        } catch (error) {
            console.error('Error deleting job:', error);
            showNotification('Failed to delete job', 'error');
        } finally {
            showLoading(false);
        }
    }

    // Toggle job status
    async function toggleJobStatus(jobId) {
        console.log(`Toggling job ID: ${jobId}`);
        showLoading(true);
        try {
            const formData = new FormData();
            formData.append('action', 'toggle_status');
            formData.append('job_id', jobId);
            formData.append('csrf_token', document.querySelector('input[name="csrf_token"]').value);
            
            const response = await fetch(window.location.pathname, {
                method: 'POST',
                body: formData
            });
            if (!response.ok) throw new Error(`HTTP ${response.status}`);
            const data = await response.json();
            
            if (data.success) {
                showNotification(data.message, 'success');
                loadJobs();
            } else {
                showNotification('Error: ' + data.message, 'error');
            }
        } catch (error) {
            console.error('Error toggling status:', error);
            showNotification('Failed to toggle status', 'error');
        } finally {
            showLoading(false);
        }
    }

    // Load recent jobs
    async function loadRecentJobs() {
        showLoading(true);
        console.log('Loading recent jobs');
        try {
            const response = await fetch(window.location.pathname + '?action=get_jobs&t=' + Date.now());
            if (!response.ok) throw new Error(`HTTP ${response.status}`);
            const data = await response.json();
            
            if (data.success) {
                const recentJobs = data.data.slice(0, 5);
                const tbody = document.querySelector('#recent-jobs-table tbody');
                if (tbody) {
                    tbody.innerHTML = recentJobs.length ? '' : '<tr><td colspan="6" class="text-center">No recent jobs</td></tr>';
                    recentJobs.forEach(job => {
                        const row = document.createElement('tr');
                        row.innerHTML = `
                            <td>${escapeHtml(job.title)}</td>
                            <td><span class="badge bg-info">${escapeHtml(job.job_categories || 'N/A')}</span></td>
                            <td><i class="fas fa-map-marker-alt me-1"></i>${escapeHtml(job.location)}</td>
                            <td><span class="badge bg-secondary">${job.job_type}</span></td>
                            <td><span class="status-badge ${job.is_active == 1 ? 'status-active' : 'status-inactive'}">${job.is_active == 1 ? 'Active' : 'Inactive'}</span></td>
                            <td>${formatDate(job.created_at)}</td>
                        `;
                        tbody.appendChild(row);
                    });
                }
            } else {
                showNotification('Error: ' + data.message, 'error');
            }
        } catch (error) {
            console.error('Error loading recent jobs:', error);
            showNotification('Failed to load recent jobs', 'error');
        } finally {
            showLoading(false);
        }
    }

    // Update dashboard stats
    async function updateDashboardStats() {
        showLoading(true);
        console.log('Updating stats');
        try {
            const response = await fetch(window.location.pathname + '?action=get_jobs&t=' + Date.now());
            if (!response.ok) throw new Error(`HTTP ${response.status}`);
            const data = await response.json();
            
            if (data.success) {
                const jobs = data.data;
                const totalJobs = jobs.length;
                const activeJobs = jobs.filter(job => job.is_active == 1).length;
                const monthlyJobs = jobs.filter(job => {
                    const createdDate = new Date(job.created_at);
                    return createdDate.getMonth() === new Date().getMonth() && createdDate.getFullYear() === new Date().getFullYear();
                }).length;
                
                const totalEl = document.getElementById('total-jobs');
                const activeEl = document.getElementById('active-jobs');
                const monthlyEl = document.getElementById('monthly-jobs');
                
                if (totalEl) totalEl.textContent = totalJobs;
                if (activeEl) activeEl.textContent = activeJobs;
                if (monthlyEl) monthlyEl.textContent = monthlyJobs;
            } else {
                showNotification('Error: ' + data.message, 'error');
            }
        } catch (error) {
            console.error('Error updating stats:', error);
            showNotification('Failed to update stats', 'error');
        } finally {
            showLoading(false);
        }
    }

    // Filter jobs
    function filterJobs() {
        console.log('Filtering jobs');
        const search = document.getElementById('search-filter')?.value.toLowerCase() || '';
        const category = document.getElementById('category-filter')?.value || '';
        const status = document.getElementById('status-filter')?.value || '';
        
        let url = window.location.pathname + '?action=get_jobs';
        if (search) url += `&search=${encodeURIComponent(search)}`;
        if (category) url += `&category=${encodeURIComponent(category)}`;
        if (status !== '') url += `&status=${status}`;
        
        fetch(url)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    currentJobs = data.data;
                    renderJobsTable();
                } else {
                    showNotification('Error: ' + data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error filtering jobs:', error);
                showNotification('Failed to filter jobs', 'error');
            });
    }

    // Show create form
    function showCreateForm() {
        console.log('Showing create job form');
        resetForm();
        showSection('create-job');
    }

    // Reset form
    function resetForm() {
        console.log('Resetting form');
        const form = document.getElementById('job-form');
        if (form) {
            form.reset();
            document.querySelector('input[name="action"]').value = 'create';
            document.getElementById('edit-job-id').value = '';
            document.getElementById('submit-text').textContent = 'Create Job Posting';
            editingJobId = null;
            
            form.querySelectorAll('.form-control, .form-select').forEach(input => {
                input.classList.remove('is-valid', 'is-invalid');
            });
        }
    }

    // Setup form validation
    function setupFormValidation() {
        console.log('Setting up form validation');
        const form = document.getElementById('job-form');
        if (!form) return;
        
        form.addEventListener('submit', e => {
            if (!form.checkValidity()) {
                e.preventDefault();
                e.stopPropagation();
            }
            form.classList.add('was-validated');
        });
        
        form.querySelectorAll('.form-control-animated, .form-select').forEach(input => {
            input.addEventListener('input', () => {
                input.classList.toggle('is-valid', input.checkValidity());
                input.classList.toggle('is-invalid', !input.checkValidity());
            });
        });
    }

    // Validate form
    function validateForm() {
        console.log('Validating form');
        const form = document.getElementById('job-form');
        if (!form) return false;
        
        let isValid = form.checkValidity();
        form.querySelectorAll('.form-control-animated, .form-select').forEach(input => {
            input.classList.toggle('is-invalid', !input.checkValidity());
            input.classList.toggle('is-valid', input.checkValidity());
        });
        return isValid;
    }

    // Show notification
    function showNotification(message, type = 'info') {
        console.log(`Notification: ${message} (${type})`);
        const toastEl = document.getElementById('notification-toast');
        const toastBody = document.getElementById('toast-message');
        
        if (!toastEl || !toastBody) return;
        
        toastBody.textContent = message;
        toastEl.classList.remove('bg-primary', 'bg-success', 'bg-danger', 'bg-warning');
        toastEl.classList.add(`bg-${type === 'error' ? 'danger' : type}`);
        
        new bootstrap.Toast(toastEl).show();
    }

    // Show loading
    function showLoading(show) {
        console.log(`Loading: ${show}`);
        const loading = document.getElementById('loading');
        if (loading) loading.classList.toggle('show', show);
    }

    // Format date
    function formatDate(dateString) {
        try {
            const date = new Date(dateString);
            return date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
        } catch {
            return 'Invalid Date';
        }
    }

    // Escape HTML
    function escapeHtml(unsafe) {
        if (typeof unsafe !== 'string') return unsafe;
        return unsafe.replace(/[&<"']/g, m => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[m]);
    }

    // Debounce
    function debounce(func, wait) {
        let timeout;
        return function(...args) {
            clearTimeout(timeout);
            timeout = setTimeout(() => func(...args), wait);
        };
    }
</script>
</body>
</html>
                
                