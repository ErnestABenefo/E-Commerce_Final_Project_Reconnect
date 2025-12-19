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
            display: flex;
            min-height: 600px;
        }

        .info-section {
            flex: 1;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 50px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .info-section h1 {
            font-size: 2.5em;
            margin-bottom: 20px;
        }

        .info-section p {
            font-size: 1.1em;
            line-height: 1.6;
            opacity: 0.9;
        }

        .form-section {
            flex: 1;
            padding: 50px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .form-container {
            display: none;
        }

        .form-container.active {
            display: block;
        }

        h2 {
            color: #333;
            margin-bottom: 10px;
            font-size: 2em;
        }

        .subtitle {
            color: #666;
            margin-bottom: 30px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-row {
            display: flex;
            gap: 15px;
        }

        .form-row .form-group {
            flex: 1;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: #555;
            font-weight: 500;
        }

        input, textarea {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s;
        }

        input:focus, textarea:focus {
            outline: none;
            border-color: #667eea;
        }

        textarea {
            resize: vertical;
            min-height: 80px;
        }

        .password-container {
            position: relative;
        }

        .toggle-password {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #666;
            user-select: none;
        }

        .error-message {
            color: #e74c3c;
            font-size: 0.85em;
            margin-top: 5px;
            display: none;
        }

        .error-message.show {
            display: block;
        }

        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: none;
        }

        .success-message.show {
            display: block;
        }

        .alert-message {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: none;
        }

        .alert-message.show {
            display: block;
        }

        .btn {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
            margin-top: 10px;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
        }

        .btn:active {
            transform: translateY(0);
        }

        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        .switch-form {
            text-align: center;
            margin-top: 20px;
            color: #666;
        }

        .switch-form a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
            cursor: pointer;
        }

        .switch-form a:hover {
            text-decoration: underline;
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            margin: 15px 0;
        }

        .checkbox-group input[type="checkbox"] {
            width: auto;
            margin-right: 8px;
        }

        .checkbox-group label {
            margin-bottom: 0;
            font-weight: normal;
        }

        .loading {
            display: none;
            text-align: center;
            margin-top: 10px;
        }

        .loading.show {
            display: block;
        }

        .spinner {
            border: 3px solid #f3f3f3;
            border-top: 3px solid #667eea;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            animation: spin 1s linear infinite;
            margin: 0 auto;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        @media (max-width: 768px) {
            .container {
                flex-direction: column;
            }

            .info-section {
                padding: 30px;
            }

            .form-section {
                padding: 30px;
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
                    Don't have an account? <a onclick="switchToRegister()">Register here</a>
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

    <!-- University Registration Link -->
    <div style="position: fixed; bottom: 20px; right: 20px; background: white; padding: 15px 20px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);">
        <p style="margin: 0 0 10px 0; color: #333; font-size: 0.9rem; font-weight: 600;">Are you a university?</p>
        <a href="register_university.php" style="display: inline-block; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 8px 16px; border-radius: 6px; text-decoration: none; font-weight: 600; font-size: 0.9rem;">Register Your University</a>
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

        // Resolve an action URL by probing likely locations and returning the first reachable one.
        // Tries in order: root `/actions/...`, project-root (derived from path), parent folders.
        async function resolveActionUrl(filename) {
            const origin = window.location.origin;
            const pathname = window.location.pathname || '';

            // derive projectRoot if path contains "Log In" or its encoded form
            const markers = ['/Log In/', '/Log%20In/'];
            let projectRoot = '';
            for (const m of markers) {
                const idx = pathname.indexOf(m);
                if (idx !== -1) {
                    projectRoot = pathname.substring(0, idx);
                    break;
                }
            }

            // helper to attempt a HEAD request and return true if reachable
            async function reachable(url) {
                try {
                    const resp = await fetch(url, { method: 'HEAD' });
                    return resp && (resp.ok || resp.status === 405 || resp.status === 403 || resp.status === 401);
                } catch (e) {
                    return false;
                }
            }

            const candidates = [];

            // root actions
            candidates.push(origin + '/actions/' + filename);

            // projectRoot/actions
            if (projectRoot) {
                if (projectRoot[0] !== '/') projectRoot = '/' + projectRoot;
                candidates.push(origin + projectRoot + '/actions/' + filename);
            }

            // parent folder of current file: replace last segment with 'actions'
            const parts = pathname.split('/').filter(Boolean);
            if (parts.length >= 1) {
                const parent = '/' + parts.slice(0, parts.length - 1).join('/');
                candidates.push(origin + parent + '/actions/' + filename);
            }

            // Try candidates sequentially and return first reachable one
            for (const c of candidates) {
                if (await reachable(c)) return c;
            }

            // Fallback to root actions path (will likely 404 but at least we tried)
            return origin + '/actions/' + filename;
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
                const loginUrl = await resolveActionUrl('login_user_action.php');
                console.log('Login action URL:', loginUrl);
                const response = await fetch(loginUrl, {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.status === 'success') {
                    showSuccess(data.message);
                    setTimeout(() => {
                        window.location.href = data.redirect || '../view/dashboard.php';
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
                // Post directly to local endpoint in the same folder
                const response = await fetch('user_reg.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.status === 'success') {
                    showSuccess(data.message);
                    
                    // If auto-logged in, redirect to homepage
                    if (data.auto_logged_in) {
                        setTimeout(() => {
                            window.location.href = data.redirect || '../view/dashboard.php';
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