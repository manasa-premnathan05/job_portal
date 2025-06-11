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
            WHERE is_active = 1 
            ORDER BY created_at DESC";
    $jobs_result = $conn->query($sql);
    ?>

    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Job Seeker Dashboard - Professional Portal</title>
        <script src="https://cdn.tailwindcss.com"></script>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
        <style>
    :root {
        --primary: #4a6bff;
        --primary-dark: #3a56d4;
        --secondary: #3f37c9;
        --light: #f8f9fa;
        --dark: #2d3748;
        --gray: #718096;
        --light-gray: #e2e8f0;
        --danger: #e53e3e;
        --success: #38a169;
        --transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.1);
    }

    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
    }

    body {
        background-color: #f7fafc;
        min-height: 100vh;
        color: var(--dark);
        background-image: url('data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIxMDAlIiBoZWlnaHQ9IjEwMCUiPjxkZWZzPjxwYXR0ZXJuIGlkPSJwYXR0ZXJuIiB3aWR0aD0iNDAiIGhlaWdodD0iNDAiIHBhdHRlcm5Vbml0cz0idXNlclNwYWNlT25Vc2UiIHBhdHRlcm5UcmFuc2Zvcm09InJvdGF0ZSg0NSkiPjxyZWN0IHdpZHRoPSIyMCIgaGVpZ2h0PSIyMCIgZmlsbD0icmdiYSgyMzgsMjQyLDI1NSwwLjAzKSIvPjwvcGF0dGVybj48L2RlZnM+PHJlY3Qgd2lkdGg9IjEwMCUiIGhlaWdodD9iMCUiIGZpbGw9InVybCgjcGF0dGVybikiLz48L3N2Zz4=');
    }

    /* Navigation */
    .navbar {
        background: white;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        position: sticky;
        top: 0;
        z-index: 1000;
    }

    .nav-brand {
        font-weight: 700;
        font-size: 1.5rem;
        color: var(--primary);
    }

    /* Main Content */
    .main-container {
        background: white;
        border-radius: 16px;
        margin: 2rem auto;
        padding: 2rem;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
        max-width: 1200px;
    }

    .main-title {
        font-size: 2rem;
        font-weight: 700;
        text-align: center;
        margin-bottom: 2rem;
        color: var(--dark);
        position: relative;
    }

    .main-title::after {
        content: '';
        position: absolute;
        bottom: -10px;
        left: 50%;
        transform: translateX(-50%);
        width: 80px;
        height: 3px;
        background: var(--primary);
    }

    /* Job Cards */
    .jobs-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
        gap: 1.5rem;
        margin-top: 2rem;
    }

    .job-card {
        background: white;
        border-radius: 12px;
        padding: 1.5rem;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        border: 1px solid var(--light-gray);
        transition: var(--transition);
    }

    .job-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 15px rgba(0, 0, 0, 0.1);
        border-color: var(--primary);
    }

    .job-title {
        font-size: 1.25rem;
        font-weight: 600;
        color: var(--dark);
        margin-bottom: 1rem;
    }

    .job-detail {
        display: flex;
        align-items: center;
        margin-bottom: 0.75rem;
        color: var(--gray);
        font-size: 0.95rem;
    }

    .job-detail i {
        width: 20px;
        margin-right: 0.75rem;
        color: var(--primary);
    }

    /* Buttons */
    .btn-group {
        display: flex;
        gap: 1rem;
        margin-top: 1.5rem;
    }

    .btn {
        padding: 0.75rem 1.5rem;
        border-radius: 8px;
        font-weight: 600;
        font-size: 0.95rem;
        border: none;
        cursor: pointer;
        transition: var(--transition);
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    .btn-primary {
        background: var(--primary);
        color: white;
    }

    .btn-primary:hover {
        background: var(--primary-dark);
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(74, 107, 255, 0.3);
    }

    .btn-success {
        background: var(--success);
        color: white;
    }

    .btn-success:hover {
        background: #2d9a5e;
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(56, 161, 105, 0.3);
    }

    .btn-danger {
        background: var(--danger);
        color: white;
    }

    .btn-danger:hover {
        background: #c53030;
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(229, 62, 62, 0.3);
    }

    /* Modals */
    .modal-backdrop {
        background: rgba(0, 0, 0, 0.5);
        backdrop-filter: blur(3px);
    }

    .modal {
        background: white;
        border-radius: 16px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.15);
        max-width: 800px;
        width: 95%;
    }

    .modal-header {
        padding: 1.5rem;
        border-bottom: 1px solid var(--light-gray);
        position: relative;
    }

    .modal-title {
        font-size: 1.5rem;
        font-weight: 600;
        color: var(--dark);
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
    }

    .modal-body {
        padding: 1.5rem;
        max-height: 60vh;
        overflow-y: auto;
    }

    .modal-footer {
        padding: 1rem 1.5rem;
        border-top: 1px solid var(--light-gray);
        display: flex;
        justify-content: flex-end;
        gap: 1rem;
    }

    /* Messages */
    .message {
        padding: 1rem;
        border-radius: 8px;
        margin: 1rem 0;
        font-weight: 500;
        display: flex;
        align-items: center;
    }

    .message i {
        margin-right: 0.75rem;
    }

    .message-success {
        background: rgba(56, 161, 105, 0.1);
        color: var(--success);
        border-left: 4px solid var(--success);
    }

    .message-error {
        background: rgba(229, 62, 62, 0.1);
        color: var(--danger);
        border-left: 4px solid var(--danger);
    }

    /* Form Elements */
    .form-control {
        width: 100%;
        padding: 0.875rem 1rem;
        border: 1px solid var(--light-gray);
        border-radius: 8px;
        font-size: 1rem;
        transition: var(--transition);
    }

    .form-control:focus {
        outline: none;
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(74, 107, 255, 0.2);
    }

    .file-upload {
        position: relative;
        display: inline-block;
        width: 100%;
    }

    .file-upload-label {
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 1.5rem;
        border: 2px dashed var(--light-gray);
        border-radius: 8px;
        cursor: pointer;
        transition: var(--transition);
        text-align: center;
    }

    .file-upload-label:hover {
        border-color: var(--primary);
        background: rgba(74, 107, 255, 0.05);
    }

    /* Responsive Design */
    @media (max-width: 768px) {
        .main-container {
            margin: 1rem;
            padding: 1rem;
        }

        .jobs-grid {
            grid-template-columns: 1fr;
        }

        .btn-group {
            flex-direction: column;
        }

        .btn {
            width: 100%;
        }
    }

    /* No jobs state */
    .no-jobs {
        text-align: center;
        padding: 3rem 1rem;
        color: var(--gray);
        grid-column: 1 / -1;
    }

    .no-jobs i {
        font-size: 3rem;
        margin-bottom: 1rem;
        color: var(--light-gray);
    }

    .no-jobs h3 {
        font-size: 1.25rem;
        margin-bottom: 0.5rem;
        color: var(--dark);
    }
</style>
    </head>
    <body>
       
        
        <!-- Navigation -->
        <nav class="navbar">
            <div class="max-w-7xl mx-auto px-6 py-4 flex justify-between items-center">
                <div class="nav-brand">JobPortal</div>
                <div class="flex items-center space-x-4">
                    <span class="text-gray-700 font-medium">Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</span>
                    <a href="logout.php" class="btn btn-danger">
                        <i class="fas fa-sign-out-alt mr-2"></i>Logout
                    </a>
                </div>
            </div>
        </nav>

        <!-- Main Content -->
        <div class="main-container">
            <h1 class="main-title">Discover Your Next Opportunity</h1>

            <!-- Job Listings -->
            <div class="jobs-grid">
                <?php if ($jobs_result->num_rows > 0): ?>
                    <?php while ($job = $jobs_result->fetch_assoc()): ?>
                        <div class="job-card">
                            <h2 class="job-title"><?php echo htmlspecialchars($job['title']); ?></h2>
                            
                            <div class="job-detail">
                                <i class="fas fa-map-marker-alt"></i>
                                <span><?php echo htmlspecialchars($job['location']); ?></span>
                            </div>
                            
                            <div class="job-detail">
                                <i class="fas fa-briefcase"></i>
                                <span><?php echo htmlspecialchars($job['job_type']); ?></span>
                            </div>
                            
                            <div class="job-detail">
                                <i class="fas fa-dollar-sign"></i>
                                <span><?php echo htmlspecialchars($job['salary'] ?: 'Competitive'); ?></span>
                            </div>
                            
                            <div class="job-detail">
                                <i class="fas fa-tag"></i>
                                <span><?php echo htmlspecialchars($job['job_categories']); ?></span>
                            </div>
                            
                            <div class="job-detail">
                                <i class="fas fa-clock"></i>
                                <span><?php echo date('M j, Y', strtotime($job['created_at'])); ?></span>
                            </div>

                            <div class="btn-group">
                                <button onclick="openJobModal(<?php echo $job['job_id']; ?>, '<?php echo addslashes(htmlspecialchars($job['title'])); ?>', '<?php echo addslashes(htmlspecialchars($job['description'])); ?>', '<?php echo addslashes(htmlspecialchars($job['requirements'])); ?>', '<?php echo addslashes(htmlspecialchars($job['location'])); ?>', '<?php echo addslashes(htmlspecialchars($job['job_type'])); ?>', '<?php echo addslashes(htmlspecialchars($job['shift_schedule'])); ?>', '<?php echo addslashes(htmlspecialchars($job['salary'])); ?>', '<?php echo addslashes(htmlspecialchars($job['benefits'])); ?>', '<?php echo addslashes(htmlspecialchars($job['job_categories'])); ?>')"
                                        class="btn btn-primary">
                                    <i class="fas fa-eye mr-2"></i>View Details
                                </button>
                                <button onclick="openApplyModal(<?php echo $job['job_id']; ?>, '<?php echo addslashes(htmlspecialchars($job['title'])); ?>')"
                                        class="btn btn-success">
                                    <i class="fas fa-paper-plane mr-2"></i>Apply Now
                                </button>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="no-jobs">
                        <i class="fas fa-search"></i>
                        <h3>No Jobs Available</h3>
                        <p>Check back later for new opportunities!</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Job Details Modal -->
        <div id="jobModal" class="fixed inset-0 modal-backdrop flex items-center justify-center hidden z-50">
            <div class="modal">
                <div class="modal-header">
                    <h2 id="jobModalTitle" class="modal-title"></h2>
                    <button onclick="closeJobModal()" class="modal-close">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div class="job-detail">
                            <i class="fas fa-map-marker-alt"></i>
                            <span id="jobModalLocation"></span>
                        </div>
                        <div class="job-detail">
                            <i class="fas fa-briefcase"></i>
                            <span id="jobModalJobType"></span>
                        </div>
                        <div class="job-detail">
                            <i class="fas fa-clock"></i>
                            <span id="jobModalShiftSchedule"></span>
                        </div>
                        <div class="job-detail">
                            <i class="fas fa-dollar-sign"></i>
                            <span id="jobModalSalary"></span>
                        </div>
                        <div class="job-detail">
                            <i class="fas fa-tag"></i>
                            <span id="jobModalCategory"></span>
                        </div>
                    </div>
                    
                    <div class="space-y-4">
                        <div>
                            <h3 class="text-lg font-semibold mb-2 text-gray-800">Job Description</h3>
                            <p id="jobModalDescription" class="text-gray-600 leading-relaxed"></p>
                        </div>
                        
                        <div>
                            <h3 class="text-lg font-semibold mb-2 text-gray-800">Requirements</h3>
                            <p id="jobModalRequirements" class="text-gray-600 leading-relaxed"></p>
                        </div>
                        
                        <div>
                            <h3 class="text-lg font-semibold mb-2 text-gray-800">Benefits</h3>
                            <p id="jobModalBenefits" class="text-gray-600 leading-relaxed"></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Apply Job Modal -->
        <div id="applyModal" class="fixed inset-0 modal-backdrop flex items-center justify-center hidden z-50">
            <div class="modal">
                <div class="modal-header">
                    <h2 id="applyModalTitle" class="modal-title"></h2>
                    <button onclick="closeApplyModal()" class="modal-close">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="modal-body">
                    <div id="applyModalContent">
                        <?php if (!$has_cv): ?>
                            <div class="message message-error">
                                <i class="fas fa-exclamation-triangle"></i>
                                <span>You need to upload a CV before applying. Please upload your CV below.</span>
                            </div>
                            <form id="cvUploadForm" class="space-y-4">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                                <div class="file-upload">
                                    <input type="file" name="cv" accept=".pdf" required>
                                    <div class="file-upload-label">
                                        <div>
                                            <i class="fas fa-cloud-upload-alt"></i>
                                            <div class="font-semibold">Upload your CV</div>
                                            <div class="text-sm text-gray-500">PDF format, max 2MB</div>
                                        </div>
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-primary w-full">
                                    <i class="fas fa-upload mr-2"></i>Upload CV
                                </button>
                            </form>
                        <?php else: ?>
                            <div class="message message-success">
                                <i class="fas fa-check-circle"></i>
                                <span>Your CV is ready! You can apply for this position.</span>
                            </div>
                            <form id="applyForm" class="space-y-4">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                                <input type="hidden" name="job_id" id="applyJobId">
                                <button type="submit" class="btn btn-success w-full">
                                    <i class="fas fa-paper-plane mr-2"></i>Confirm Application
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                    <div id="applySpinner" class="spinner hidden"></div>
                    <div id="applyMessage" class="hidden"></div>
                </div>
            </div>
        </div>

        <script>
            let currentJobId = null;

            // Navbar scroll effect
            window.addEventListener('scroll', () => {
                const navbar = document.querySelector('.navbar');
                if (window.scrollY > 50) {
                    navbar.classList.add('scrolled');
                } else {
                    navbar.classList.remove('scrolled');
                }
            });

            // Job Details Modal
            function openJobModal(jobId, title, description, requirements, location, jobType, shiftSchedule, salary, benefits, category) {
                console.log('Opening job modal for job ID:', jobId);
                
                document.getElementById('jobModalTitle').textContent = title;
                document.getElementById('jobModalDescription').textContent = description;
                document.getElementById('jobModalRequirements').textContent = requirements;
                document.getElementById('jobModalLocation').textContent = location;
                document.getElementById('jobModalJobType').textContent = jobType;
                document.getElementById('jobModalShiftSchedule').textContent = shiftSchedule || 'Not specified';
                document.getElementById('jobModalSalary').textContent = salary || 'Competitive';
                document.getElementById('jobModalBenefits').textContent = benefits || 'Standard benefits package';
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
                console.log('Opening apply modal for job ID:', jobId, 'Title:', title);
                
                currentJobId = jobId;
                document.getElementById('applyModalTitle').textContent = `Apply for ${title}`;
                
                // Set job ID in the form if it exists
                const jobIdInput = document.getElementById('applyJobId');
                if (jobIdInput) {
                    jobIdInput.value = jobId;
                }
                
                // Reset message states
                const message = document.getElementById('applyMessage');
                const spinner = document.getElementById('applySpinner');
                
                message.classList.add('hidden');
                spinner.classList.add('hidden');
                
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
            const cvUploadForm = document.getElementById('cvUploadForm');
            if (cvUploadForm) {
                cvUploadForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    console.log('CV Upload form submitted');
                    
                    const formData = new FormData(this);
                    const spinner = document.getElementById('applySpinner');
                    const message = document.getElementById('applyMessage');

                    spinner.classList.remove('hidden');
                    message.classList.add('hidden');

                    fetch('upload_cv.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => {
                        console.log('CV Upload response status:', response.status);
                        return response.json();
                    })
                    .then(data => {
                        console.log('CV Upload response data:', data);
                        spinner.classList.add('hidden');
                        
                        if (data.success) {
                            // Update modal content to show the apply form
                            document.getElementById('applyModalContent').innerHTML = `
                                <div class="message message-success">
                                    <i class="fas fa-check-circle"></i>
                                    <span>Your CV has been uploaded successfully! You can now apply for this position.</span>
                                </div>
                                <form id="applyForm" class="space-y-4">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                                    <input type="hidden" name="job_id" id="applyJobId" value="${currentJobId}">
                                    <button type="submit" class="btn btn-success w-full">
                                        <i class="fas fa-paper-plane mr-2"></i>Confirm Application
                                    </button>
                                </form>
                            `;
                            attachApplyFormListener();
                        } else {
                            message.classList.remove('hidden');
                            message.className = 'message message-error';
                            message.innerHTML = `<i class="fas fa-exclamation-triangle"></i><span>${data.error || 'Failed to upload CV.'}</span>`;
                        }
                    })
                    .catch(error => {
                        console.error('CV Upload error:', error);
                        spinner.classList.add('hidden');
                        message.classList.remove('hidden');
                        message.className = 'message message-error';
                        message.innerHTML = '<i class="fas fa-exclamation-triangle"></i><span>An error occurred while uploading your CV.</span>';
                    });
                });
            }

            // Handle Job Application via AJAX
            function attachApplyFormListener() {
                const applyForm = document.getElementById('applyForm');
                if (applyForm) {
                    applyForm.addEventListener('submit', function(e) {
                        e.preventDefault();
                        console.log('Apply form submitted');
                        
                        const formData = new FormData(this);
                        const spinner = document.getElementById('applySpinner');
                        const message = document.getElementById('applyMessage');

                        // Debug: Log form data
                        for (let [key, value] of formData.entries()) {
                            console.log('Form data:', key, value);
                        }

                        spinner.classList.remove('hidden');
                        message.classList.add('hidden');

                        fetch('apply_job.php', {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => {
                            console.log('Apply response status:', response.status);
                            return response.json();
                        })
                        .then(data => {
                            console.log('Apply response data:', data);
                            spinner.classList.add('hidden');
                            message.classList.remove('hidden');
                            
                            if (data.success) {
                                message.className = 'message message-success';
                                message.innerHTML = '<i class="fas fa-check-circle"></i><span>Application submitted successfully! We will contact you shortly.</span>';
                                document.getElementById('applyModalContent').innerHTML = ''; // Clear form
                                
                                // Auto-close modal after 3 seconds
                                setTimeout(() => {
                                    closeApplyModal();
                                }, 3000);
                            } else {
                                message.className = 'message message-error';
                                message.innerHTML = `<i class="fas fa-exclamation-triangle"></i><span>${data.error || 'Failed to apply for the job.'}</span>`;
                            }
                        })
                        .catch(error => {
                            console.error('Apply error:', error);
                            spinner.classList.add('hidden');
                            message.classList.remove('hidden');
                            message.className = 'message message-error';
                            message.innerHTML = '<i class="fas fa-exclamation-triangle"></i><span>An error occurred while applying for the job.</span>';
                        });
                    });
                }
            }

            // Attach listener to apply form if it exists on page load
            attachApplyFormListener();

            // Close modals when clicking outside
            window.onclick = function(event) {
                if (event.target.classList.contains('modal-backdrop')) {
                    closeJobModal();
                    closeApplyModal();
                }
            };

            // Keyboard navigation
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    closeJobModal();
                    closeApplyModal();
                }
            });
            // Add this to the file upload form handling
const fileInput = document.querySelector('input[type="file"]');
if (fileInput) {
    fileInput.addEventListener('change', function() {
        const file = this.files[0];
        if (file) {
            const fileSizeMB = file.size / (1024 * 1024);
            if (fileSizeMB > 2) {
                alert('File size must be less than 2MB');
                this.value = '';
            }
            
            const fileType = file.type;
            if (fileType !== 'application/pdf') {
                alert('Only PDF files are allowed');
                this.value = '';
            }
        }
    });
}

            // Add loading states to buttons
            document.querySelectorAll('.btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    if (this.type === 'submit') {
                        this.classList.add('pulse');
                        setTimeout(() => {
                            this.classList.remove('pulse');
                        }, 2000);
                    }
                });
            });

            // Debug: Log when page loads
            console.log('Page loaded, current user has CV:', <?php echo $has_cv ? 'true' : 'false'; ?>);
        </script>
    </body>
    </html>

    <?php $conn->close(); ?>
