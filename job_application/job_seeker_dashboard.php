<?php
session_start();

// Check if the user is logged in as a job seeker
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'job_seeker') {
    header("Location: login.php");
    exit();
}

// Database connection (adjust as per your setup)
$conn = new mysqli("127.0.0.1", "root", "", "job_portal");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

// Fetch the job seeker's details (e.g., CV path)
$seeker_id = $_SESSION['user_id'];
$sql = "SELECT cv_path FROM applicant_details WHERE seeker_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $seeker_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$has_cv = !empty($user['cv_path']);
$stmt->close();

// Fetch all active job postings
$sql = "SELECT job_id, title, description, requirements, location, job_type, shift_schedule, salary, benefits, job_categories, created_at 
        FROM job_postings 
        WHERE is_active = 1";
$jobs_result = $conn->query($sql);

// Handle job application submission (via AJAX in JavaScript)
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Job Seeker Dashboard</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Font Awesome for Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
    /* Modern Professional Base Styles */
    :root {
        --primary: #4361ee;
        --primary-dark: #3a56d4;
        --secondary: #3f37c9;
        --accent: #4895ef;
        --light: #f8f9fa;
        --dark: #212529;
        --success: #4cc9f0;
        --warning: #f8961e;
        --danger: #f72585;
        --gray: #6c757d;
        --light-gray: #e9ecef;
    }
    
    body {
        background-color: #f5f7ff;
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif;
        color: var(--dark);
        line-height: 1.6;
    }

    /* Enhanced Typography */
    h1, h2, h3, h4, h5, h6 {
        font-weight: 700;
        color: var(--dark);
        letter-spacing: -0.025em;
    }
    
    h1 {
        font-size: 2.5rem;
        background: linear-gradient(90deg, var(--primary), var(--secondary));
        -webkit-background-clip: text;
        background-clip: text;
        color: transparent;
        margin-bottom: 2rem;
        position: relative;
    }
    
    h1::after {
        content: '';
        position: absolute;
        bottom: -10px;
        left: 50%;
        transform: translateX(-50%);
        width: 80px;
        height: 4px;
        background: linear-gradient(90deg, var(--primary), var(--secondary));
        border-radius: 2px;
    }

    /* Sophisticated Navbar */
    nav {
        background: white;
        box-shadow: 0 4px 30px rgba(0, 0, 0, 0.05);
        backdrop-filter: blur(5px);
        -webkit-backdrop-filter: blur(5px);
        border-bottom: 1px solid rgba(255, 255, 255, 0.3);
        padding: 1rem 2rem;
    }
    
    .nav-brand {
        font-weight: 800;
        font-size: 1.5rem;
        background: linear-gradient(90deg, var(--primary), var(--secondary));
        -webkit-background-clip: text;
        background-clip: text;
        color: transparent;
    }

    /* Premium Job Cards */
    .job-card {
        background: white;
        border-radius: 12px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
        transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.1);
        overflow: hidden;
        position: relative;
        border: none;
    }
    
    .job-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 4px;
        height: 0;
        background: linear-gradient(to bottom, var(--primary), var(--accent));
        transition: height 0.4s ease;
    }
    
    .job-card:hover {
        transform: translateY(-8px);
        box-shadow: 0 15px 40px rgba(0, 0, 0, 0.1);
    }
    
    .job-card:hover::before {
        height: 100%;
    }
    
    .job-title {
        font-size: 1.25rem;
        font-weight: 700;
        color: var(--primary-dark);
        margin-bottom: 0.5rem;
        transition: color 0.3s ease;
    }
    
    .job-card:hover .job-title {
        color: var(--secondary);
    }
    
    .job-detail {
        font-size: 0.9rem;
        color: var(--gray);
        margin-bottom: 0.5rem;
        display: flex;
        align-items: center;
    }
    
    .job-detail i {
        margin-right: 0.5rem;
        color: var(--accent);
        width: 20px;
        text-align: center;
    }

    /* Elegant Buttons */
    .action-button {
        padding: 0.75rem 1.5rem;
        border-radius: 8px;
        font-weight: 600;
        transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
        border: none;
        position: relative;
        overflow: hidden;
        z-index: 1;
    }
    
    .action-button::after {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: linear-gradient(45deg, var(--primary), var(--accent));
        z-index: -1;
        transition: opacity 0.3s ease;
        opacity: 1;
    }
    
    .action-button:hover::after {
        opacity: 0.9;
    }
    
    .action-button:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(67, 97, 238, 0.3);
    }
    
    .action-button:active {
        transform: translateY(0);
    }
    
    .btn-primary {
        background: linear-gradient(45deg, var(--primary), var(--accent));
        color: white;
    }
    
    .btn-success {
        background: linear-gradient(45deg, var(--success), #4cc9f0);
        color: white;
    }
    
    .btn-danger {
        background: linear-gradient(45deg, var(--danger), #f72585);
        color: white;
    }

    /* Sleek Modals */
    .modal-backdrop {
        background: rgba(0, 0, 0, 0.6);
        backdrop-filter: blur(5px);
        -webkit-backdrop-filter: blur(5px);
    }
    
    .modal {
        background: white;
        border-radius: 16px;
        box-shadow: 0 25px 50px rgba(0, 0, 0, 0.15);
        transform: translateY(20px) scale(0.95);
        opacity: 0;
        transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        max-width: 800px;
        width: 90%;
        overflow: hidden;
    }
    
    .modal.show {
        transform: translateY(0) scale(1);
        opacity: 1;
    }
    
    .modal-header {
        padding: 1.5rem;
        border-bottom: 1px solid var(--light-gray);
        position: relative;
    }
    
    .modal-header h2 {
        margin: 0;
        font-size: 1.5rem;
        color: var(--primary-dark);
    }
    
    .modal-close {
        position: absolute;
        top: 1.5rem;
        right: 1.5rem;
        background: none;
        border: none;
        font-size: 1.5rem;
        color: var(--gray);
        cursor: pointer;
        transition: color 0.2s ease;
    }
    
    .modal-close:hover {
        color: var(--danger);
    }
    
    .modal-body {
        padding: 1.5rem;
        max-height: 70vh;
        overflow-y: auto;
    }
    
    .modal-footer {
        padding: 1rem 1.5rem;
        border-top: 1px solid var(--light-gray);
        display: flex;
        justify-content: flex-end;
    }

    /* Refined Form Elements */
    .form-control {
        border: 1px solid var(--light-gray);
        border-radius: 8px;
        padding: 0.75rem 1rem;
        transition: all 0.3s ease;
        width: 100%;
    }
    
    .form-control:focus {
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.1);
        outline: none;
    }
    
    .file-input {
        position: relative;
        overflow: hidden;
        display: inline-block;
        width: 100%;
    }
    
    .file-input input[type="file"] {
        position: absolute;
        left: 0;
        top: 0;
        opacity: 0;
        width: 100%;
        height: 100%;
        cursor: pointer;
    }
    
    .file-input-label {
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 1rem;
        border: 2px dashed var(--light-gray);
        border-radius: 8px;
        transition: all 0.3s ease;
        text-align: center;
        cursor: pointer;
    }
    
    .file-input-label:hover {
        border-color: var(--primary);
        background-color: rgba(67, 97, 238, 0.05);
    }
    
    .file-input-label i {
        margin-right: 0.5rem;
        color: var(--primary);
    }

    /* Enhanced Spinner */
    .spinner {
        width: 2.5rem;
        height: 2.5rem;
        border: 4px solid rgba(67, 97, 238, 0.1);
        border-top: 4px solid var(--primary);
        border-radius: 50%;
        animation: spin 1s linear infinite;
        margin: 2rem auto;
    }
    
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }

    /* Status Messages */
    .message {
        padding: 1rem;
        border-radius: 8px;
        margin: 1rem 0;
        font-weight: 500;
    }
    
    .message-success {
        background-color: rgba(76, 201, 240, 0.1);
        color: var(--success);
        border-left: 4px solid var(--success);
    }
    
    .message-error {
        background-color: rgba(247, 37, 133, 0.1);
        color: var(--danger);
        border-left: 4px solid var(--danger);
    }

    /* Responsive Grid */
    .jobs-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 2rem;
    }

    /* Micro-interactions */
    .hover-scale {
        transition: transform 0.3s ease;
    }
    
    .hover-scale:hover {
        transform: scale(1.03);
    }
    
    /* Fade-in animation for page load */
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    .fade-in {
        animation: fadeIn 0.6s ease forwards;
    }
    
    /* Delayed animations for cards */
    .job-card {
        opacity: 0;
        animation: fadeIn 0.6s ease forwards;
    }
    
    .job-card:nth-child(1) { animation-delay: 0.1s; }
    .job-card:nth-child(2) { animation-delay: 0.2s; }
    .job-card:nth-child(3) { animation-delay: 0.3s; }
    .job-card:nth-child(4) { animation-delay: 0.4s; }
    .job-card:nth-child(5) { animation-delay: 0.5s; }
    .job-card:nth-child(6) { animation-delay: 0.6s; }

    </style>
</head>
<body class="min-h-screen">
    <!-- Navbar -->
    <nav class="bg-white shadow-md p-4 flex justify-between items-center">
        <div class="text-2xl font-bold text-blue-600">Job Portal</div>
        <div>
            <span class="text-gray-700 mr-4">Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</span>
            <a href="logout.php" class="bg-red-500 text-white px-4 py-2 rounded-lg action-button">Logout</a>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container mx-auto p-6">
        <h1 class="mb-8 text-center">Available Job Postings</h1>

        <!-- Job Listings -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php if ($jobs_result->num_rows > 0): ?>
                <?php while ($job = $jobs_result->fetch_assoc()): ?>
                    <div class="job-card p-6">
                        <h2 class="job-title"><?php echo htmlspecialchars($job['title']); ?></h2>
                        <p class="job-detail mt-2"><i class="fas fa-map-marker-alt mr-2 text-gray-500"></i><?php echo htmlspecialchars($job['location']); ?></p>
                        <p class="job-detail"><i class="fas fa-briefcase mr-2 text-gray-500"></i><?php echo htmlspecialchars($job['job_type']); ?></p>
                        <p class="job-detail"><i class="fas fa-money-bill-wave mr-2 text-gray-500"></i><?php echo htmlspecialchars($job['salary'] ?: 'Not specified'); ?></p>
                        <p class="job-detail"><i class="fas fa-tag mr-2 text-gray-500"></i><?php echo htmlspecialchars($job['job_categories']); ?></p>
                        <div class="mt-4 flex space-x-3">
                            <button onclick="openJobModal(<?php echo $job['job_id']; ?>, '<?php echo htmlspecialchars($job['title'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($job['description'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($job['requirements'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($job['location'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($job['job_type'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($job['shift_schedule'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($job['salary'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($job['benefits'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($job['job_categories'], ENT_QUOTES); ?>')"
                                    class="bg-blue-500 text-white px-4 py-2 rounded-lg action-button">
                                View Details
                            </button>
                            <button onclick="openApplyModal(<?php echo $job['job_id']; ?>, '<?php echo htmlspecialchars($job['title'], ENT_QUOTES); ?>')"
                                    class="bg-green-500 text-white px-4 py-2 rounded-lg action-button">
                                Apply Now
                            </button>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p class="text-gray-600 text-center col-span-full">No active job postings available at the moment.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Job Details Modal -->
    <div id="jobModal" class="fixed inset-0 bg-black bg-opacity-50 modal-backdrop flex items-center justify-center hidden">
        <div class="bg-white rounded-lg p-6 w-full max-w-2xl modal">
            <div class="flex justify-between items-center mb-4">
                <h2 id="jobModalTitle" class="text-2xl font-bold text-gray-800"></h2>
                <button onclick="closeJobModal()" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="space-y-3 text-gray-700">
                <p><strong>Location:</strong> <span id="jobModalLocation"></span></p>
                <p><strong>Job Type:</strong> <span id="jobModalJobType"></span></p>
                <p><strong>Shift Schedule:</strong> <span id="jobModalShiftSchedule"></span></p>
                <p><strong>Salary:</strong> <span id="jobModalSalary"></span></p>
                <p><strong>Category:</strong> <span id="jobModalCategory"></span></p>
                <p><strong>Description:</strong> <span id="jobModalDescription"></span></p>
                <p><strong>Requirements:</strong> <span id="jobModalRequirements"></span></p>
                <p><strong>Benefits:</strong> <span id="jobModalBenefits"></span></p>
            </div>
        </div>
    </div>

    <!-- Apply Job Modal -->
    <div id="applyModal" class="fixed inset-0 bg-black bg-opacity-50 modal-backdrop flex items-center justify-center hidden">
        <div class="bg-white rounded-lg p-6 w-full max-w-md modal">
            <div class="flex justify-between items-center mb-4">
                <h2 id="applyModalTitle" class="text-2xl font-bold text-gray-800"></h2>
                <button onclick="closeApplyModal()" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div id="applyModalContent">
                <?php if (!$has_cv): ?>
                    <p class="text-red-500 mb-4">You need to upload a CV before applying. Please upload your CV below.</p>
                    <form id="cvUploadForm">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                        <input type="file" name="cv" accept=".pdf" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 mb-4" required>
                        <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded-lg action-button w-full">Upload CV</button>
                    </form>
                <?php else: ?>
                    <p class="text-green-500 mb-4">Your CV is already uploaded. You can apply for this job.</p>
                    <form id="applyForm">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                        <input type="hidden" name="job_id" id="applyJobId">
                        <button type="submit" class="bg-green-500 text-white px-4 py-2 rounded-lg action-button w-full">Confirm Application</button>
                    </form>
                <?php endif; ?>
            </div>
            <div id="applySpinner" class="spinner mt-4"></div>
            <p id="applyMessage" class="mt-4 hidden"></p>
        </div>
    </div>

    <!-- JavaScript for Modal Handling and AJAX -->
    <script>
        let currentJobId = null;

        // Job Details Modal
        function openJobModal(jobId, title, description, requirements, location, jobType, shiftSchedule, salary, benefits, category) {
            document.getElementById('jobModalTitle').textContent = title;
            document.getElementById('jobModalDescription').textContent = description;
            document.getElementById('jobModalRequirements').textContent = requirements;
            document.getElementById('jobModalLocation').textContent = location;
            document.getElementById('jobModalJobType').textContent = jobType;
            document.getElementById('jobModalShiftSchedule').textContent = shiftSchedule || 'Not specified';
            document.getElementById('jobModalSalary').textContent = salary || 'Not specified';
            document.getElementById('jobModalBenefits').textContent = benefits || 'Not specified';
            document.getElementById('jobModalCategory').textContent = category;
            const modal = document.getElementById('jobModal');
            modal.classList.remove('hidden');
            setTimeout(() => modal.querySelector('.modal').classList.add('show'), 10);
        }

        function closeJobModal() {
            const modal = document.getElementById('jobModal');
            modal.querySelector('.modal').classList.remove('show');
            setTimeout(() => modal.classList.add('hidden'), 300);
        }

        // Apply Job Modal
        function openApplyModal(jobId, title) {
            currentJobId = jobId;
            document.getElementById('applyModalTitle').textContent = `Apply for ${title}`;
            document.getElementById('applyJobId').value = jobId;
            document.getElementById('applyMessage').classList.add('hidden');
            document.getElementById('applySpinner').style.display = 'none';
            const modal = document.getElementById('applyModal');
            modal.classList.remove('hidden');
            setTimeout(() => modal.querySelector('.modal').classList.add('show'), 10);
        }

        function closeApplyModal() {
            const modal = document.getElementById('applyModal');
            modal.querySelector('.modal').classList.remove('show');
            setTimeout(() => modal.classList.add('hidden'), 300);
            currentJobId = null;
        }

        // Handle CV Upload via AJAX
        document.getElementById('cvUploadForm')?.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const spinner = document.getElementById('applySpinner');
            const message = document.getElementById('applyMessage');

            spinner.style.display = 'block';
            message.classList.add('hidden');

            fetch('upload_cv.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                spinner.style.display = 'none';
                if (data.success) {
                    // Update modal content to show the apply form
                    document.getElementById('applyModalContent').innerHTML = `
                        <p class="text-green-500 mb-4">Your CV has been uploaded successfully. You can now apply for this job.</p>
                        <form id="applyForm">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                            <input type="hidden" name="job_id" id="applyJobId" value="${currentJobId}">
                            <button type="submit" class="bg-green-500 text-white px-4 py-2 rounded-lg action-button w-full">Confirm Application</button>
                        </form>
                    `;
                    attachApplyFormListener();
                } else {
                    message.classList.remove('hidden');
                    message.classList.add('text-red-500');
                    message.textContent = data.error || 'Failed to upload CV.';
                }
            })
            .catch(error => {
                spinner.style.display = 'none';
                message.classList.remove('hidden');
                message.classList.add('text-red-500');
                message.textContent = 'An error occurred while uploading your CV.';
                console.error(error);
            });
        });

        // Handle Job Application via AJAX
        function attachApplyFormListener() {
            document.getElementById('applyForm')?.addEventListener('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(this);
                const spinner = document.getElementById('applySpinner');
                const message = document.getElementById('applyMessage');

                spinner.style.display = 'block';
                message.classList.add('hidden');

                fetch('apply_job.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    spinner.style.display = 'none';
                    message.classList.remove('hidden');
                    if (data.success) {
                        message.classList.add('text-green-500');
                        message.textContent = 'Thank you, we will contact you shortly.';
                        document.getElementById('applyModalContent').innerHTML = ''; // Clear form
                    } else {
                        message.classList.add('text-red-500');
                        message.textContent = data.error || 'Failed to apply for the job.';
                    }
                })
                .catch(error => {
                    spinner.style.display = 'none';
                    message.classList.remove('hidden');
                    message.classList.add('text-red-500');
                    message.textContent = 'An error occurred while applying for the job.';
                    console.error(error);
                });
            });
        }

        // Attach listener to apply form if it exists
        attachApplyFormListener();

        // Close modals when clicking outside
        window.onclick = function(event) {
            if (event.target.classList.contains('modal-backdrop')) {
                closeJobModal();
                closeApplyModal();
            }
        };
    </script>
</body>
</html>

<?php $conn->close(); ?>