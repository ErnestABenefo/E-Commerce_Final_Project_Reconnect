<?php
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register University - ReConnect</title>
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

        .btn.secondary {
            background: #6c757d;
            width: auto;
            margin-top: 0;
            display: inline-block;
            padding: 10px 20px;
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
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="info-section">
            <h1>Register Your University</h1>
            <p>Join ReConnect as an educational institution. Register your university to connect with alumni, host events, and build a strong community of graduates.</p>
        </div>

        <div class="form-section">
            <!-- Success Message -->
            <div class="success-message" id="successMessage"></div>
            
            <!-- Alert Message -->
            <div class="alert-message" id="alertMessage"></div>

            <!-- University Registration Form -->
            <div id="registerForm">
                <h2>Register University</h2>
                <p class="subtitle">Add your institution to the ReConnect network.</p>

                <form id="universityFormElement">
                    <div class="form-group">
                        <label for="university_name">University Name</label>
                        <input type="text" id="university_name" name="university_name" placeholder="e.g., University of Ghana" required>
                        <span class="error-message" id="university_name_error"></span>
                    </div>

                    <div class="form-group">
                        <label for="university_location">Location</label>
                        <input type="text" id="university_location" name="university_location" placeholder="e.g., Accra, Ghana" required>
                        <span class="error-message" id="university_location_error"></span>
                    </div>

                    <button type="submit" class="btn" id="registerBtn">Register University</button>

                    <div class="loading" id="registerLoading">
                        <div class="spinner"></div>
                        <p>Registering your university...</p>
                    </div>
                </form>

                <div class="switch-form">
                    <a href="../log_in_and_register.php">Back to Login/Register</a>
                </div>
            </div>
        </div>
    </div>

    <script>
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

        // Form Submission
        document.getElementById('universityFormElement').addEventListener('submit', async function(e) {
            e.preventDefault();
            clearMessages();
            clearFieldErrors();

            // Client-side validation
            const universityName = document.getElementById('university_name').value.trim();
            const universityLocation = document.getElementById('university_location').value.trim();

            let hasError = false;

            // Validate name
            if (universityName.length < 2) {
                showFieldError('university_name', 'University name must be at least 2 characters');
                hasError = true;
            }

            // Validate location
            if (universityLocation.length < 2) {
                showFieldError('university_location', 'Location must be at least 2 characters');
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

            try {
                const response = await fetch('../actions/register_university_action.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.status === 'success') {
                    showSuccess(data.message + ' University ID: ' + data.university_id);
                    document.getElementById('universityFormElement').reset();
                    
                    // Redirect after 2 seconds
                    setTimeout(() => {
                        window.location.href = '../log_in_and_register.php';
                    }, 2000);
                } else {
                    showAlert(data.message);
                    registerBtn.disabled = false;
                    registerLoading.classList.remove('show');
                }
            } catch (error) {
                showAlert('An error occurred. Please try again.');
                console.error('Error:', error);
                registerBtn.disabled = false;
                registerLoading.classList.remove('show');
            }
        });
    </script>
</body>
</html>
