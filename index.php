<?php
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ReConnect - Login & Register</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
            position: relative;
            overflow-x: hidden;
        }

        body::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 600px;
            height: 600px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            z-index: 0;
        }

        body::after {
            content: '';
            position: absolute;
            bottom: -50%;
            left: -50%;
            width: 800px;
            height: 800px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 50%;
            z-index: 0;
        }

        .container {
            background: white;
            border-radius: 24px;
            box-shadow: 0 25px 80px rgba(0, 0, 0, 0.15), 0 10px 30px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            max-width: 950px;
            width: 100%;
            display: flex;
            min-height: 650px;
            position: relative;
            z-index: 1;
            animation: slideUp 0.5s ease-out;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .info-section {
            flex: 1;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 60px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }

        .info-section::before {
            content: '';
            position: absolute;
            top: -100px;
            left: -100px;
            width: 300px;
            height: 300px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
        }

        .info-section::after {
            content: '';
            position: absolute;
            bottom: -150px;
            right: -150px;
            width: 400px;
            height: 400px;
            background: rgba(255, 255, 255, 0.08);
            border-radius: 50%;
        }

        .info-section h1 {
            font-size: 2.8em;
            margin-bottom: 24px;
            font-weight: 700;
            position: relative;
            z-index: 1;
            letter-spacing: -0.5px;
        }

        .info-section p {
            font-size: 1.15em;
            line-height: 1.7;
            opacity: 0.95;
            position: relative;
            z-index: 1;
        }

        .form-section {
            flex: 1;
            padding: 60px 50px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            background: #fff;
        }

        .form-container {
            display: none;
            animation: fadeIn 0.4s ease-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateX(20px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .form-container.active {
            display: block;
        }

        h2 {
            color: #1a1a1a;
            margin-bottom: 12px;
            font-size: 2.2em;
            font-weight: 700;
            letter-spacing: -0.5px;
        }

        .subtitle {
            color: #6c757d;
            margin-bottom: 32px;
            font-size: 0.95rem;
        }

        .form-group {
            margin-bottom: 22px;
        }

        .form-row {
            display: flex;
            gap: 16px;
        }

        .form-row .form-group {
            flex: 1;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: #495057;
            font-weight: 600;
            font-size: 0.9rem;
        }

        input, textarea {
            width: 100%;
            padding: 13px 16px;
            border: 1px solid #ced4da;
            border-radius: 8px;
            font-size: 0.95rem;
            transition: all 0.2s ease-in-out;
            background-color: #fff;
            font-family: inherit;
            color: #495057;
        }

        input:focus, textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
            background-color: #fff;
        }

        input:hover, textarea:hover {
            border-color: #a8b3d4;
        }

        textarea {
            resize: vertical;
            min-height: 90px;
            line-height: 1.6;
        }

        .password-container {
            position: relative;
        }

        .toggle-password {
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #6c757d;
            user-select: none;
            transition: color 0.2s;
        }

        .toggle-password:hover {
            color: #667eea;
        }

        .error-message {
            color: #dc3545;
            font-size: 0.85rem;
            margin-top: 6px;
            display: none;
            font-weight: 500;
        }

        .error-message.show {
            display: block;
            animation: slideDown 0.3s ease-out;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-5px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .success-message {
            background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
            color: #155724;
            padding: 16px 20px;
            border-radius: 10px;
            margin-bottom: 24px;
            display: none;
            border-left: 4px solid #28a745;
            font-weight: 500;
            box-shadow: 0 4px 12px rgba(40, 167, 69, 0.1);
        }

        .success-message.show {
            display: block;
            animation: slideDown 0.3s ease-out;
        }

        .alert-message {
            background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
            color: #721c24;
            padding: 16px 20px;
            border-radius: 10px;
            margin-bottom: 24px;
            display: none;
            border-left: 4px solid #dc3545;
            font-weight: 500;
            box-shadow: 0 4px 12px rgba(220, 53, 69, 0.1);
        }

        .alert-message.show {
            display: block;
            animation: slideDown 0.3s ease-out;
        }

        .btn {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 12px;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
            letter-spacing: 0.3px;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 25px rgba(102, 126, 234, 0.4);
        }

        .btn:active {
            transform: translateY(0);
            box-shadow: 0 2px 10px rgba(102, 126, 234, 0.3);
        }

        .btn:disabled {
            opacity: 0.65;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        .switch-form {
            text-align: center;
            margin-top: 24px;
            color: #6c757d;
            font-size: 0.95rem;
        }

        .switch-form a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
            cursor: pointer;
            transition: color 0.2s;
        }

        .switch-form a:hover {
            color: #764ba2;
            text-decoration: underline;
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            margin: 18px 0;
        }

        .checkbox-group input[type="checkbox"] {
            width: 18px;
            height: 18px;
            margin-right: 10px;
            cursor: pointer;
            accent-color: #667eea;
        }

        .checkbox-group label {
            margin-bottom: 0;
            font-weight: 500;
            cursor: pointer;
            font-size: 0.9rem;
        }

        .loading {
            display: none;
            text-align: center;
            margin-top: 16px;
        }

        .loading.show {
            display: block;
        }

        .loading p {
            color: #6c757d;
            margin-top: 12px;
            font-size: 0.9rem;
        }

        .spinner {
            border: 3px solid #f3f3f3;
            border-top: 3px solid #667eea;
            border-radius: 50%;
            width: 36px;
            height: 36px;
            animation: spin 0.8s linear infinite;
            margin: 0 auto;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        @media (max-width: 768px) {
            .container {
                flex-direction: column;
                border-radius: 16px;
                min-height: auto;
            }

            .info-section {
                padding: 40px 30px;
            }

            .info-section h1 {
                font-size: 2em;
            }

            .form-section {
                padding: 40px 30px;
            }

            h2 {
                font-size: 1.8em;
            }

            .form-row {
                flex-direction: column;
                gap: 0;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="info-section">
            <h1>Welcome to ReConnect</h1>
            <p>Join our alumni network platform to connect with fellow graduates, explore career opportunities, and stay engaged with your alma mater community.</p>
        </div>

        <div class="form-section">
            <!-- Success Message -->
            <div class="success-message" id="successMessage"></div>
            
            <!-- Alert Message -->
            <div class="alert-message" id="alertMessage"></div>

            <!-- Login Form -->
            <div class="form-container active" id="loginForm">
                <h2>Login</h2>
                <p class="subtitle">Welcome back! Please login to your account.</p>

                <form id="loginFormElement">
                    <div class="form-group">
                        <label for="login_email">Email Address</label>
                        <input type="email" id="login_email" name="email" placeholder="Enter your email" required>
                        <span class="error-message" id="login_email_error"></span>
                    </div>

                    <div class="form-group">
                        <label for="login_password">Password</label>
                        <div class="password-container">
                            <input type="password" id="login_password" name="password" placeholder="Enter your password" required>
                            <span class="toggle-password" onclick="togglePassword('login_password')">👁️</span>
                        </div>
                        <span class="error-message" id="login_password_error"></span>
                    </div>

                    <div class="checkbox-group">
                        <input type="checkbox" id="remember_me" name="remember_me">
                        <label for="remember_me">Remember me</label>
                    </div>

                    <button type="submit" class="btn" id="loginBtn">Login</button>

                    <div class="loading" id="loginLoading">
                        <div class="spinner"></div>
                        <p>Logging in...</p>
                    </div>
                </form>

                <div class="switch-form">
                    Don't have an account? <a href="PHP_Files/Log In/reguser.php">Register here</a>
                </div>
            </div>

            <!-- Register Form -->
            <div class="form-container" id="registerForm">
                <h2>Register</h2>
                <p class="subtitle">Create your account to get started.</p>

                <form id="registerFormElement">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="first_name">First Name</label>
                            <input type="text" id="first_name" name="first_name" placeholder="John" required>
                            <span class="error-message" id="first_name_error"></span>
                        </div>

                        <div class="form-group">
                            <label for="last_name">Last Name</label>
                            <input type="text" id="last_name" name="last_name" placeholder="Doe" required>
                            <span class="error-message" id="last_name_error"></span>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" placeholder="john.doe@example.com" required>
                        <span class="error-message" id="email_error"></span>
                    </div>

                    <div class="form-group">
                        <label for="phone">Phone Number (Optional)</label>
                        <input type="tel" id="phone" name="phone" placeholder="+233 123 456 789">
                        <span class="error-message" id="phone_error"></span>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="password">Password</label>
                            <div class="password-container">
                                <input type="password" id="password" name="password" placeholder="Create password" required>
                                <span class="toggle-password" onclick="togglePassword('password')">👁️</span>
                            </div>
                            <span class="error-message" id="password_error"></span>
                        </div>

                        <div class="form-group">
                            <label for="confirm_password">Confirm Password</label>
                            <div class="password-container">
                                <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm password" required>
                                <span class="toggle-password" onclick="togglePassword('confirm_password')">👁️</span>
                            </div>
                            <span class="error-message" id="confirm_password_error"></span>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="bio">Bio (Optional)</label>
                        <textarea id="bio" name="bio" placeholder="Tell us a little about yourself..."></textarea>
                    </div>

                    <button type="submit" class="btn" id="registerBtn">Register</button>

                    <div class="loading" id="registerLoading">
                        <div class="spinner"></div>
                        <p>Creating your account...</p>
                    </div>
                </form>

                <div class="switch-form">
                    Already have an account? <a onclick="switchToLogin()">Login here</a>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Toggle between login and register forms
        function switchToRegister() {
            document.getElementById('loginForm').classList.remove('active');
            document.getElementById('registerForm').classList.add('active');
            clearMessages();
        }

        function switchToLogin() {
            document.getElementById('registerForm').classList.remove('active');
            document.getElementById('loginForm').classList.add('active');
            clearMessages();
        }

        // Toggle password visibility
        function togglePassword(inputId) {
            const input = document.getElementById(inputId);
            input.type = input.type === 'password' ? 'text' : 'password';
        }

        // Clear all messages
        function clearMessages() {
            document.getElementById('successMessage').classList.remove('show');
            document.getElementById('alertMessage').classList.remove('show');
            document.querySelectorAll('.error-message').forEach(el => el.classList.remove('show'));
        }

        // Show success message
        function showSuccess(message) {
            const successMsg = document.getElementById('successMessage');
            successMsg.textContent = message;
            successMsg.classList.add('show');
            document.getElementById('alertMessage').classList.remove('show');
        }

        // Show alert message
        function showAlert(message) {
            const alertMsg = document.getElementById('alertMessage');
            alertMsg.textContent = message;
            alertMsg.classList.add('show');
            document.getElementById('successMessage').classList.remove('show');
        }

        // Show field error
        function showFieldError(fieldId, message) {
            const errorElement = document.getElementById(fieldId + '_error');
            if (errorElement) {
                errorElement.textContent = message;
                errorElement.classList.add('show');
            }
        }

        // Clear field errors
        function clearFieldErrors() {
            document.querySelectorAll('.error-message').forEach(el => {
                el.classList.remove('show');
                el.textContent = '';
            });
        }

        // Login Form Submission
        document.getElementById('loginFormElement').addEventListener('submit', async function(e) {
            e.preventDefault();
            clearMessages();
            clearFieldErrors();

            const loginBtn = document.getElementById('loginBtn');
            const loginLoading = document.getElementById('loginLoading');
            
            // Disable button and show loading
            loginBtn.disabled = true;
            loginLoading.classList.add('show');

            const formData = new FormData(this);
            
            // Add remember_me value
            formData.set('remember_me', document.getElementById('remember_me').checked ? 'true' : 'false');

            try {
                const response = await fetch('PHP_Files/actions/login_user_action.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.status === 'success') {
                    showSuccess(data.message);
                    setTimeout(() => {
                        window.location.href = data.redirect || 'PHP_Files/view/homepage.php';
                    }, 1000);
                } else {
                    showAlert(data.message);
                    loginBtn.disabled = false;
                    loginLoading.classList.remove('show');
                }
            } catch (error) {
                showAlert('An error occurred. Please try again.');
                loginBtn.disabled = false;
                loginLoading.classList.remove('show');
            }
        });

        // Register Form Submission
        document.getElementById('registerFormElement').addEventListener('submit', async function(e) {
            e.preventDefault();
            clearMessages();
            clearFieldErrors();

            // Client-side validation
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const firstName = document.getElementById('first_name').value.trim();
            const lastName = document.getElementById('last_name').value.trim();
            const email = document.getElementById('email').value.trim();

            let hasError = false;

            // Validate names
            if (firstName.length < 2) {
                showFieldError('first_name', 'First name must be at least 2 characters');
                hasError = true;
            }

            if (lastName.length < 2) {
                showFieldError('last_name', 'Last name must be at least 2 characters');
                hasError = true;
            }

            // Validate email
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                showFieldError('email', 'Please enter a valid email address');
                hasError = true;
            }

            // Validate password
            if (password.length < 8) {
                showFieldError('password', 'Password must be at least 8 characters');
                hasError = true;
            } else if (!/[A-Z]/.test(password)) {
                showFieldError('password', 'Password must contain at least one uppercase letter');
                hasError = true;
            } else if (!/[a-z]/.test(password)) {
                showFieldError('password', 'Password must contain at least one lowercase letter');
                hasError = true;
            } else if (!/[0-9]/.test(password)) {
                showFieldError('password', 'Password must contain at least one number');
                hasError = true;
            }

            // Validate password confirmation
            if (password !== confirmPassword) {
                showFieldError('confirm_password', 'Passwords do not match');
                hasError = true;
            }

            if (hasError) {
                return;
            }

            const registerBtn = document.getElementById('registerBtn');
            const registerLoading = document.getElementById('registerLoading');
            
            // Disable button and show loading
            registerBtn.disabled = true;
            registerLoading.classList.add('show');

            const formData = new FormData(this);
            
            // Add auto_login parameter to automatically log in after registration
            formData.append('auto_login', 'true');

            try {
                const response = await fetch('PHP_Files/actions/register_user_action.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.status === 'success') {
                    showSuccess(data.message);
                    
                    // If auto-logged in, redirect to homepage
                    if (data.auto_logged_in) {
                        setTimeout(() => {
                            window.location.href = data.redirect || 'PHP_Files/view/homepage.php';
                        }, 1500);
                    } else {
                        // Otherwise, switch to login form
                        setTimeout(() => {
                            switchToLogin();
                            showSuccess('Registration successful! Please login.');
                        }, 1500);
                    }
                } else {
                    showAlert(data.message);
                    registerBtn.disabled = false;
                    registerLoading.classList.remove('show');
                }
            } catch (error) {
                showAlert('An error occurred. Please try again.');
                registerBtn.disabled = false;
                registerLoading.classList.remove('show');
            }
        });

        // Real-time password strength indicator
        document.getElementById('password')?.addEventListener('input', function(e) {
            const password = e.target.value;
            const errorElement = document.getElementById('password_error');
            
            if (password.length === 0) {
                errorElement.classList.remove('show');
                return;
            }

            let strength = 0;
            if (password.length >= 8) strength++;
            if (/[A-Z]/.test(password)) strength++;
            if (/[a-z]/.test(password)) strength++;
            if (/[0-9]/.test(password)) strength++;
            if (/[^A-Za-z0-9]/.test(password)) strength++;

            const messages = {
                1: 'Weak password',
                2: 'Fair password',
                3: 'Good password',
                4: 'Strong password',
                5: 'Very strong password'
            };

            if (strength < 4) {
                errorElement.textContent = messages[strength] || 'Very weak password';
                errorElement.style.color = '#e74c3c';
                errorElement.classList.add('show');
            } else {
                errorElement.textContent = messages[strength];
                errorElement.style.color = '#27ae60';
                errorElement.classList.add('show');
            }
        });

        // Real-time password confirmation check
        document.getElementById('confirm_password')?.addEventListener('input', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = e.target.value;
            const errorElement = document.getElementById('confirm_password_error');

            if (confirmPassword.length === 0) {
                errorElement.classList.remove('show');
                return;
            }

            if (password !== confirmPassword) {
                errorElement.textContent = 'Passwords do not match';
                errorElement.classList.add('show');
            } else {
                errorElement.classList.remove('show');
            }
        });
    </script>
</body>
</html>