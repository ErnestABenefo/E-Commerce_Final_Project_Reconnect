<?php
/**
 * Login & Registration Page
 * Combined login and registration forms for users and universities
 */

// Start session to handle success/error messages
session_start();

// Get success or error messages from URL parameters
$error_message = isset($_GET['error']) ? htmlspecialchars($_GET['error']) : '';
$success_message = isset($_GET['success']) ? htmlspecialchars($_GET['success']) : '';

// Clear messages from session after displaying
if (isset($_SESSION['error'])) {
    $error_message = $_SESSION['error'];
    unset($_SESSION['error']);
}
if (isset($_SESSION['success'])) {
    $success_message = $_SESSION['success'];
    unset($_SESSION['success']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alumni Network - Login & Register</title>
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
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            overflow: hidden;
            max-width: 900px;
            width: 100%;
            min-height: 600px;
            display: flex;
        }

        .side-panel {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px;
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
        }

        .side-panel h1 {
            font-size: 2.5em;
            margin-bottom: 20px;
        }

        .side-panel p {
            font-size: 1.1em;
            line-height: 1.6;
            opacity: 0.9;
        }

        .form-panel {
            flex: 1.5;
            padding: 40px;
            overflow-y: auto;
            max-height: 90vh;
        }

        .tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 30px;
            border-bottom: 2px solid #e0e0e0;
        }

        .tab {
            padding: 10px 20px;
            cursor: pointer;
            border: none;
            background: none;
            font-size: 1.1em;
            color: #666;
            transition: all 0.3s;
            position: relative;
        }

        .tab.active {
            color: #667eea;
            font-weight: 600;
        }

        .tab.active::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            right: 0;
            height: 2px;
            background: #667eea;
        }

        .form-content {
            display: none;
        }

        .form-content.active {
            display: block;
            animation: fadeIn 0.3s;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        label {
            display: block;
            margin-bottom: 5px;
            color: #333;
            font-weight: 500;
        }

        input, select, textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 1em;
            transition: all 0.3s;
        }

        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: #667eea;
        }

        textarea {
            resize: vertical;
            min-height: 80px;
        }

        .btn {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1.1em;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.4);
        }

        .btn:active {
            transform: translateY(0);
        }

        .alert {
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 500;
        }

        .alert-error {
            background: #fee;
            color: #c33;
            border: 1px solid #fcc;
        }

        .alert-success {
            background: #efe;
            color: #2a6b2a;
            border: 1px solid #cfc;
        }

        .password-toggle {
            position: relative;
        }

        .password-toggle input {
            padding-right: 40px;
        }

        .password-toggle-btn {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            color: #666;
            font-size: 1.2em;
        }

        .divider {
            text-align: center;
            margin: 20px 0;
            color: #999;
            font-weight: 500;
        }

        .required {
            color: #c33;
        }

        @media (max-width: 768px) {
            .container {
                flex-direction: column;
            }

            .side-panel {
                padding: 30px;
            }

            .side-panel h1 {
                font-size: 1.8em;
            }

            .form-row {
                grid-template-columns: 1fr;
            }

            .tabs {
                flex-wrap: wrap;
            }

            .tab {
                font-size: 0.95em;
                padding: 8px 12px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="side-panel">
            <h1>🎓 Alumni Network</h1>
            <p>Connect with fellow alumni, discover opportunities, and build lasting relationships in our vibrant community.</p>
        </div>

        <div class="form-panel">
            <div class="tabs">
                <button class="tab active" onclick="showTab('login')">Login</button>
                <button class="tab" onclick="showTab('register-user')">Register as User</button>
                <button class="tab" onclick="showTab('register-university')">Register University</button>
            </div>

            <?php if ($error_message): ?>
                <div class="alert alert-error" id="global-error">
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>

            <?php if ($success_message): ?>
                <div class="alert alert-success" id="global-success">
                    <?php echo $success_message; ?>
                </div>
            <?php endif; ?>

            <!-- Login Form -->
            <div id="login" class="form-content active">
                <h2>Welcome Back!</h2>
                
                <form id="loginForm" method="POST" action="process_login.php">
                    <div class="form-group">
                        <label for="login-email">Email <span class="required">*</span></label>
                        <input type="email" id="login-email" name="email" required>
                    </div>

                    <div class="form-group password-toggle">
                        <label for="login-password">Password <span class="required">*</span></label>
                        <input type="password" id="login-password" name="password" required>
                        <button type="button" class="password-toggle-btn" onclick="togglePassword('login-password')">👁️</button>
                    </div>

                    <div class="form-group">
                        <button type="submit" class="btn">Login</button>
                    </div>
                </form>
            </div>

            <!-- User Registration Form -->
            <div id="register-user" class="form-content">
                <h2>Create Your Account</h2>
                
                <form id="registerForm" method="POST" action="process_register.php">
                    <input type="hidden" name="user_type" value="user">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="first-name">First Name <span class="required">*</span></label>
                            <input type="text" id="first-name" name="first_name" required>
                        </div>

                        <div class="form-group">
                            <label for="last-name">Last Name <span class="required">*</span></label>
                            <input type="text" id="last-name" name="last_name" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="email">Email <span class="required">*</span></label>
                        <input type="email" id="email" name="email" required>
                    </div>

                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <input type="tel" id="phone" name="phone">
                    </div>

                    <div class="form-row">
                        <div class="form-group password-toggle">
                            <label for="password">Password <span class="required">*</span></label>
                            <input type="password" id="password" name="password" required minlength="8">
                            <button type="button" class="password-toggle-btn" onclick="togglePassword('password')">👁️</button>
                        </div>

                        <div class="form-group password-toggle">
                            <label for="confirm-password">Confirm Password <span class="required">*</span></label>
                            <input type="password" id="confirm-password" name="confirm_password" required minlength="8">
                            <button type="button" class="password-toggle-btn" onclick="togglePassword('confirm-password')">👁️</button>
                        </div>
                    </div>

                    <div class="divider">— Academic Information —</div>

                    <div class="form-group">
                        <label for="university">University <span class="required">*</span></label>
                        <select id="university" name="university_id" required>
                            <option value="">Select University</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="department">Department <span class="required">*</span></label>
                        <select id="department" name="department_id" required>
                            <option value="">Select Department</option>
                        </select>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="graduation-year">Graduation Year <span class="required">*</span></label>
                            <input type="number" id="graduation-year" name="graduation_year" min="1950" max="2030" required>
                        </div>

                        <div class="form-group">
                            <label for="degree">Degree <span class="required">*</span></label>
                            <select id="degree" name="degree" required>
                                <option value="">Select Degree</option>
                                <option value="Bachelor's">Bachelor's</option>
                                <option value="Master's">Master's</option>
                                <option value="PhD">PhD</option>
                                <option value="Diploma">Diploma</option>
                                <option value="Certificate">Certificate</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="student-id">Student ID Number <span class="required">*</span></label>
                        <input type="text" id="student-id" name="student_id" required>
                    </div>

                    <div class="form-group">
                        <label for="bio">Bio</label>
                        <textarea id="bio" name="bio" placeholder="Tell us about yourself..."></textarea>
                    </div>

                    <div class="form-group">
                        <button type="submit" class="btn">Create Account</button>
                    </div>
                </form>
            </div>

            <!-- University Registration Form -->
            <div id="register-university" class="form-content">
                <h2>Register Your University</h2>
                
                <form id="universityForm" method="POST" action="process_university_register.php">
                    <input type="hidden" name="user_type" value="university">
                    
                    <div class="divider">— University Information —</div>

                    <div class="form-group">
                        <label for="uni-name">University Name <span class="required">*</span></label>
                        <input type="text" id="uni-name" name="university_name" required>
                    </div>

                    <div class="form-group">
                        <label for="uni-location">Location <span class="required">*</span></label>
                        <input type="text" id="uni-location" name="location" required placeholder="City, Country">
                    </div>

                    <div class="divider">— Administrator Account —</div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="admin-first-name">First Name <span class="required">*</span></label>
                            <input type="text" id="admin-first-name" name="first_name" required>
                        </div>

                        <div class="form-group">
                            <label for="admin-last-name">Last Name <span class="required">*</span></label>
                            <input type="text" id="admin-last-name" name="last_name" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="admin-email">Email <span class="required">*</span></label>
                        <input type="email" id="admin-email" name="email" required>
                    </div>

                    <div class="form-group">
                        <label for="admin-phone">Phone Number <span class="required">*</span></label>
                        <input type="tel" id="admin-phone" name="phone" required>
                    </div>

                    <div class="form-row">
                        <div class="form-group password-toggle">
                            <label for="admin-password">Password <span class="required">*</span></label>
                            <input type="password" id="admin-password" name="password" required minlength="8">
                            <button type="button" class="password-toggle-btn" onclick="togglePassword('admin-password')">👁️</button>
                        </div>

                        <div class="form-group password-toggle">
                            <label for="admin-confirm-password">Confirm Password <span class="required">*</span></label>
                            <input type="password" id="admin-confirm-password" name="confirm_password" required minlength="8">
                            <button type="button" class="password-toggle-btn" onclick="togglePassword('admin-confirm-password')">👁️</button>
                        </div>
                    </div>

                    <div class="form-group">
                        <button type="submit" class="btn">Register University</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Tab switching
        function showTab(tabName) {
            // Hide all tabs
            document.querySelectorAll('.form-content').forEach(content => {
                content.classList.remove('active');
            });
            document.querySelectorAll('.tab').forEach(tab => {
                tab.classList.remove('active');
            });

            // Show selected tab
            document.getElementById(tabName).classList.add('active');
            event.target.classList.add('active');

            // Hide global messages when switching tabs
            const globalError = document.getElementById('global-error');
            const globalSuccess = document.getElementById('global-success');
            if (globalError) globalError.style.display = 'none';
            if (globalSuccess) globalSuccess.style.display = 'none';
        }

        // Password toggle
        function togglePassword(fieldId) {
            const field = document.getElementById(fieldId);
            field.type = field.type === 'password' ? 'text' : 'password';
        }

        // Load universities
        async function loadUniversities() {
            try {
                const response = await fetch('get_universities.php');
                const universities = await response.json();
                
                const select = document.getElementById('university');
                universities.forEach(uni => {
                    const option = document.createElement('option');
                    option.value = uni.university_id;
                    option.textContent = uni.name + ' - ' + uni.location;
                    select.appendChild(option);
                });
            } catch (error) {
                console.error('Error loading universities:', error);
            }
        }

        // Load departments based on university
        document.getElementById('university')?.addEventListener('change', async function() {
            const universityId = this.value;
            const departmentSelect = document.getElementById('department');
            
            // Clear existing options
            departmentSelect.innerHTML = '<option value="">Select Department</option>';
            
            if (!universityId) return;
            
            try {
                const response = await fetch(`get_departments.php?university_id=${universityId}`);
                const departments = await response.json();
                
                departments.forEach(dept => {
                    const option = document.createElement('option');
                    option.value = dept.department_id;
                    option.textContent = dept.department_name + (dept.faculty ? ' (' + dept.faculty + ')' : '');
                    departmentSelect.appendChild(option);
                });
            } catch (error) {
                console.error('Error loading departments:', error);
            }
        });

        // Client-side form validation
        document.getElementById('registerForm')?.addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm-password').value;
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match!');
                return false;
            }
        });

        document.getElementById('universityForm')?.addEventListener('submit', function(e) {
            const password = document.getElementById('admin-password').value;
            const confirmPassword = document.getElementById('admin-confirm-password').value;
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match!');
                return false;
            }
        });

        // Auto-hide alerts after 5 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                alert.style.transition = 'opacity 0.5s';
                alert.style.opacity = '0';
                setTimeout(() => alert.style.display = 'none', 500);
            });
        }, 5000);

        // Load universities on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadUniversities();
        });
    </script>
</body>
</html>