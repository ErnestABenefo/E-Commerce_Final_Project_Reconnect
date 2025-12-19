<?php
session_start();

// Check if user is logged in
$user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
if (!$user_id || !isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../log_in_and_register.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register University - ReConnect</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 40px 20px;
        }

        .container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            overflow: hidden;
        }

        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px;
            text-align: center;
        }

        .header h1 {
            font-size: 2.5em;
            margin-bottom: 10px;
        }

        .header p {
            font-size: 1.1em;
            opacity: 0.95;
        }

        .form-container {
            padding: 40px;
        }

        .form-section {
            margin-bottom: 35px;
        }

        .form-section h2 {
            font-size: 1.4em;
            color: #333;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #667eea;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .form-section h2 i {
            color: #667eea;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
            font-size: 0.95em;
        }

        .form-group label .required {
            color: #e74c3c;
        }

        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 1em;
            font-family: inherit;
            transition: border-color 0.3s;
        }

        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: #667eea;
        }

        .form-group textarea {
            min-height: 100px;
            resize: vertical;
        }

        .form-group small {
            display: block;
            color: #666;
            margin-top: 5px;
            font-size: 0.85em;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
        }

        .btn-container {
            display: flex;
            gap: 15px;
            margin-top: 30px;
            padding-top: 30px;
            border-top: 2px solid #f0f0f0;
        }

        .btn {
            flex: 1;
            padding: 15px 30px;
            border: none;
            border-radius: 8px;
            font-size: 1.1em;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
        }

        .btn-secondary {
            background: #f0f0f0;
            color: #333;
        }

        .btn-secondary:hover {
            background: #e0e0e0;
        }

        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: none;
        }

        .alert.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .alert.show {
            display: block;
        }

        .info-box {
            background: #e3f2fd;
            border-left: 4px solid #2196f3;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 25px;
        }

        .info-box i {
            color: #2196f3;
            margin-right: 10px;
        }

        .loading {
            display: none;
            text-align: center;
            padding: 20px;
        }

        .loading.show {
            display: block;
        }

        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #667eea;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto 15px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .department-entry {
            background: #f8f9fa;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 15px;
        }

        .department-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #ddd;
        }

        .btn-remove-dept {
            background: #e74c3c;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.9em;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .btn-remove-dept:hover {
            background: #c0392b;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <i class="fas fa-university" style="font-size: 3em; margin-bottom: 15px;"></i>
            <h1>Register Your University</h1>
            <p>Connect with alumni and build a thriving educational community</p>
        </div>

        <div class="form-container">
            <div class="info-box">
                <i class="fas fa-info-circle"></i>
                <strong>Important:</strong> By registering your university, you will become its administrator and can manage alumni verifications, create posts, and organize events on behalf of the institution.
            </div>

            <div class="alert" id="alertBox"></div>
            <div class="loading" id="loadingBox">
                <div class="spinner"></div>
                <p>Registering university...</p>
            </div>

            <form id="universityForm">
                <!-- Basic Information -->
                <div class="form-section">
                    <h2><i class="fas fa-info-circle"></i> Basic Information</h2>
                    
                    <div class="form-group">
                        <label for="university_name">
                            University Name <span class="required">*</span>
                        </label>
                        <input 
                            type="text" 
                            id="university_name" 
                            name="university_name" 
                            required 
                            placeholder="e.g., University of Ghana">
                        <small>Enter the official name of the university</small>
                    </div>

                    <div class="form-group">
                        <label for="university_location">
                            Location <span class="required">*</span>
                        </label>
                        <input 
                            type="text" 
                            id="university_location" 
                            name="university_location" 
                            required 
                            placeholder="e.g., Accra, Ghana">
                        <small>City and country where the university is located</small>
                    </div>

                    <div class="form-group">
                        <label for="university_website">
                            Official Website
                        </label>
                        <input 
                            type="url" 
                            id="university_website" 
                            name="university_website" 
                            placeholder="https://www.university.edu">
                        <small>The university's official website URL</small>
                    </div>
                </div>

                <!-- Contact Information -->
                <div class="form-section">
                    <h2><i class="fas fa-address-book"></i> Contact Information</h2>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="contact_email">
                                Contact Email
                            </label>
                            <input 
                                type="email" 
                                id="contact_email" 
                                name="contact_email" 
                                placeholder="info@university.edu">
                            <small>General contact email</small>
                        </div>

                        <div class="form-group">
                            <label for="contact_phone">
                                Contact Phone
                            </label>
                            <input 
                                type="tel" 
                                id="contact_phone" 
                                name="contact_phone" 
                                placeholder="+233 XX XXX XXXX">
                            <small>Main phone number</small>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="address">
                            Physical Address
                        </label>
                        <textarea 
                            id="address" 
                            name="address" 
                            placeholder="Enter the complete physical address"></textarea>
                        <small>Full address including street, city, and postal code</small>
                    </div>
                </div>

                <!-- Additional Details -->
                <div class="form-section">
                    <h2><i class="fas fa-graduation-cap"></i> Additional Details</h2>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="established_year">
                                Year Established
                            </label>
                            <input 
                                type="number" 
                                id="established_year" 
                                name="established_year" 
                                min="1000" 
                                max="2025" 
                                placeholder="e.g., 1948">
                            <small>Year the university was founded</small>
                        </div>

                        <div class="form-group">
                            <label for="university_type">
                                University Type
                            </label>
                            <select id="university_type" name="university_type">
                                <option value="">Select type</option>
                                <option value="public">Public</option>
                                <option value="private">Private</option>
                                <option value="religious">Religious</option>
                                <option value="technical">Technical</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="description">
                            Description
                        </label>
                        <textarea 
                            id="description" 
                            name="description" 
                            placeholder="Brief description of the university, its mission, and values"></textarea>
                        <small>A brief overview of the university (optional)</small>
                    </div>
                </div>

                <!-- Departments Section -->
                <div class="form-section">
                    <h2><i class="fas fa-building"></i> Departments & Faculties</h2>
                    <p style="color:#666;margin-bottom:20px;">Add the academic departments available at your university. You can add more departments later.</p>
                    
                    <div id="departmentsContainer">
                        <!-- Department Entry Template -->
                        <div class="department-entry" data-index="0">
                            <div class="department-header">
                                <strong>Department 1</strong>
                                <button type="button" class="btn-remove-dept" onclick="removeDepartment(0)" style="display:none;">
                                    <i class="fas fa-times"></i> Remove
                                </button>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="faculty_0">Faculty/School</label>
                                    <input 
                                        type="text" 
                                        id="faculty_0" 
                                        name="departments[0][faculty]" 
                                        placeholder="e.g., Faculty of Science">
                                    <small>The faculty or school this department belongs to</small>
                                </div>
                                <div class="form-group">
                                    <label for="department_name_0">Department Name</label>
                                    <input 
                                        type="text" 
                                        id="department_name_0" 
                                        name="departments[0][department_name]" 
                                        placeholder="e.g., Computer Science">
                                    <small>The name of the department</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <button type="button" class="btn btn-secondary" onclick="addDepartment()" style="margin-top:15px;">
                        <i class="fas fa-plus"></i> Add Another Department
                    </button>
                </div>

                <!-- Submit Buttons -->
                <div class="btn-container">
                    <button type="button" class="btn btn-secondary" onclick="window.location.href='dashboard.php'">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                    <button type="submit" class="btn btn-primary" id="submitBtn">
                        <i class="fas fa-check"></i> Register University
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const form = document.getElementById('universityForm');
        const alertBox = document.getElementById('alertBox');
        const loadingBox = document.getElementById('loadingBox');
        const submitBtn = document.getElementById('submitBtn');
        let departmentIndex = 1;

        function showAlert(message, type) {
            alertBox.textContent = message;
            alertBox.className = `alert ${type} show`;
            alertBox.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            
            if (type === 'success') {
                setTimeout(() => {
                    window.location.href = 'dashboard.php';
                }, 2000);
            }
        }

        function hideAlert() {
            alertBox.classList.remove('show');
        }

        function showLoading() {
            loadingBox.classList.add('show');
            submitBtn.disabled = true;
        }

        function hideLoading() {
            loadingBox.classList.remove('show');
            submitBtn.disabled = false;
        }

        function addDepartment() {
            const container = document.getElementById('departmentsContainer');
            const newEntry = document.createElement('div');
            newEntry.className = 'department-entry';
            newEntry.setAttribute('data-index', departmentIndex);
            
            newEntry.innerHTML = `
                <div class="department-header">
                    <strong>Department ${departmentIndex + 1}</strong>
                    <button type="button" class="btn-remove-dept" onclick="removeDepartment(${departmentIndex})">
                        <i class="fas fa-times"></i> Remove
                    </button>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="faculty_${departmentIndex}">Faculty/School</label>
                        <input 
                            type="text" 
                            id="faculty_${departmentIndex}" 
                            name="departments[${departmentIndex}][faculty]" 
                            placeholder="e.g., Faculty of Science">
                        <small>The faculty or school this department belongs to</small>
                    </div>
                    <div class="form-group">
                        <label for="department_name_${departmentIndex}">Department Name</label>
                        <input 
                            type="text" 
                            id="department_name_${departmentIndex}" 
                            name="departments[${departmentIndex}][department_name]" 
                            placeholder="e.g., Computer Science">
                        <small>The name of the department</small>
                    </div>
                </div>
            `;
            
            container.appendChild(newEntry);
            departmentIndex++;
            
            // Show remove button on first entry if more than one exists
            updateRemoveButtons();
        }

        function removeDepartment(index) {
            const entry = document.querySelector(`.department-entry[data-index="${index}"]`);
            if (entry) {
                entry.remove();
                updateRemoveButtons();
                updateDepartmentNumbers();
            }
        }

        function updateRemoveButtons() {
            const entries = document.querySelectorAll('.department-entry');
            const removeButtons = document.querySelectorAll('.btn-remove-dept');
            
            if (entries.length === 1) {
                removeButtons.forEach(btn => btn.style.display = 'none');
            } else {
                removeButtons.forEach(btn => btn.style.display = 'inline-flex');
            }
        }

        function updateDepartmentNumbers() {
            const entries = document.querySelectorAll('.department-entry');
            entries.forEach((entry, idx) => {
                const header = entry.querySelector('.department-header strong');
                if (header) {
                    header.textContent = `Department ${idx + 1}`;
                }
            });
        }

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            hideAlert();

            // Basic validation
            const universityName = document.getElementById('university_name').value.trim();
            const universityLocation = document.getElementById('university_location').value.trim();

            if (!universityName || !universityLocation) {
                showAlert('Please fill in all required fields (marked with *)', 'error');
                return;
            }

            showLoading();

            try {
                const formData = new FormData(form);
                
                const response = await fetch('../actions/register_university_action.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                hideLoading();

                if (data.status === 'success') {
                    showAlert(data.message || 'University registered successfully!', 'success');
                    form.reset();
                } else {
                    let errorMsg = data.message || 'Failed to register university. Please try again.';
                    if (data.debug_error) {
                        errorMsg += ' (Debug: ' + data.debug_error + ')';
                    }
                    showAlert(errorMsg, 'error');
                }
            } catch (error) {
                hideLoading();
                showAlert('An error occurred. Please try again later.', 'error');
                console.error('Error:', error);
            }
        });

        // Auto-hide alerts after 5 seconds (except success)
        let alertTimeout;
        const observer = new MutationObserver(() => {
            if (alertBox.classList.contains('show') && !alertBox.classList.contains('success')) {
                clearTimeout(alertTimeout);
                alertTimeout = setTimeout(() => {
                    hideAlert();
                }, 5000);
            }
        });
        observer.observe(alertBox, { attributes: true, attributeFilter: ['class'] });
    </script>
</body>
</html>
