<?php
// Simple standalone registration page that posts to user_reg.php
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Register - ReConnect</title>
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
            max-width: 500px;
            width: 100%;
            padding: 40px;
        }

        h1 {
            color: #333;
            margin-bottom: 10px;
            font-size: 2em;
        }

        .subtitle {
            color: #666;
            margin-bottom: 30px;
            font-size: 0.95em;
        }

        .form-row {
            display: flex;
            gap: 15px;
        }

        .form-row .form-group {
            flex: 1;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: #555;
            font-weight: 500;
            font-size: 14px;
        }

        input[type="text"], input[type="email"], input[type="password"], input[type="tel"], input[type="number"], select {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        input:focus, select:focus {
            outline: none;
            border-color: #667eea;
        }

        select:disabled {
            background-color: #f5f5f5;
            cursor: not-allowed;
        }

        small {
            font-size: 12px;
            color: #999;
            display: block;
            margin-top: 5px;
        }

        button {
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

        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
        }

        button:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        .message {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: none;
        }

        .message.show {
            display: block;
        }

        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 20px;
        }

        .checkbox-group input[type="checkbox"] {
            width: auto;
            margin: 0;
        }

        .checkbox-group label {
            margin: 0;
            font-weight: normal;
            font-size: 14px;
        }

        .link-text {
            text-align: center;
            margin-top: 20px;
            color: #666;
            font-size: 14px;
        }

        .link {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }

        .link:hover {
            text-decoration: underline;
        }

        #loading {
            display: none;
            text-align: center;
            color: #667eea;
            margin-top: 10px;
        }

        @media (max-width: 600px) {
            .container {
                padding: 30px 20px;
            }

            .form-row {
                flex-direction: column;
                gap: 0;
            }

            h1 {
                font-size: 1.5em;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Create Account</h1>
        <p class="subtitle">Join ReConnect and start networking with your alumni community</p>

        <div id="alert" class="message error"></div>
        <div id="success" class="message success"></div>

        <form id="regForm">
            <div class="form-row">
                <div style="flex:1" class="form-group">
                    <label for="first_name">First name</label>
                    <input id="first_name" name="first_name" type="text" required />
                </div>
                <div style="flex:1" class="form-group">
                    <label for="last_name">Last name</label>
                    <input id="last_name" name="last_name" type="text" required />
                </div>
            </div>

            <div class="form-group">
                <label for="email">Email</label>
                <input id="email" name="email" type="email" required />
            </div>

            <div class="form-group">
                <label for="phone">Phone (optional)</label>
                <input id="phone" name="phone" type="tel" />
            </div>

            <div class="form-group">
                <label for="university_id">University</label>
                <select id="university_id" name="university_id" required>
                    <option value="">Select your university</option>
                </select>
            </div>

            <div class="form-group">
                <label for="department_id">Department</label>
                <select id="department_id" name="department_id" required disabled>
                    <option value="">First select a university</option>
                </select>
            </div>

            <div class="form-group">
                <label for="major">Major/Program</label>
                <input id="major" name="major" type="text" required placeholder="e.g., Computer Science" />
            </div>

            <div class="form-group">
                <label for="year_group">Year Group</label>
                <input id="year_group" name="year_group" type="number" required placeholder="e.g., 2024" min="1950" max="2100" />
                <small>Enter your graduation year or current academic year</small>
            </div>

            <div class="form-row">
                <div style="flex:1" class="form-group">
                    <label for="password">Password</label>
                    <input id="password" name="password" type="password" required />
                </div>
                <div style="flex:1" class="form-group">
                    <label for="confirm_password">Confirm</label>
                    <input id="confirm_password" name="confirm_password" type="password" required />
                </div>
            </div>

            <div class="checkbox-group">
                <input id="auto_login" name="auto_login" type="checkbox" checked />
                <label for="auto_login">Automatically log me in after registering</label>
            </div>

            <button id="submitBtn" type="submit">Create Account</button>
            <div id="loading">Please wait...</div>

            <p class="link-text">Already have an account? <a href="log_in_and_register.php" class="link">Sign in</a></p>
        </form>
    </div>

    <script>
        const form = document.getElementById('regForm');
        const alertBox = document.getElementById('alert');
        const successBox = document.getElementById('success');
        const submitBtn = document.getElementById('submitBtn');
        const loading = document.getElementById('loading');
        const universitySelect = document.getElementById('university_id');
        const departmentSelect = document.getElementById('department_id');

        function showError(msg){ alertBox.textContent = msg; alertBox.classList.add('show'); successBox.classList.remove('show'); }
        function showSuccess(msg){ successBox.textContent = msg; successBox.classList.add('show'); alertBox.classList.remove('show'); }
        function clearMsgs(){ alertBox.classList.remove('show'); successBox.classList.remove('show'); }

        // Load universities on page load
        async function loadUniversities() {
            try {
                const resp = await fetch('../actions/get_universities_action.php');
                const data = await resp.json();
                if (data.status === 'success' && data.universities) {
                    data.universities.forEach(univ => {
                        const opt = document.createElement('option');
                        opt.value = univ.university_id;
                        opt.textContent = univ.name;
                        universitySelect.appendChild(opt);
                    });
                }
            } catch (err) {
                console.error('Failed to load universities:', err);
            }
        }

        // Load departments when university is selected
        universitySelect.addEventListener('change', async function() {
            const univId = this.value;
            departmentSelect.innerHTML = '<option value="">Select your department</option>';
            departmentSelect.disabled = true;

            if (!univId) return;

            try {
                const resp = await fetch(`../actions/get_departments_action.php?university_id=${univId}`);
                const data = await resp.json();
                if (data.status === 'success' && data.departments) {
                    data.departments.forEach(dept => {
                        const opt = document.createElement('option');
                        opt.value = dept.department_id;
                        opt.textContent = dept.department_name;
                        departmentSelect.appendChild(opt);
                    });
                    departmentSelect.disabled = false;
                }
            } catch (err) {
                console.error('Failed to load departments:', err);
            }
        });

        // Load universities when page loads
        loadUniversities();

        form.addEventListener('submit', async function(e){
            e.preventDefault();
            clearMsgs();

            // Basic client-side validation
            const first = document.getElementById('first_name').value.trim();
            const last = document.getElementById('last_name').value.trim();
            const email = document.getElementById('email').value.trim();
            const password = document.getElementById('password').value;
            const confirm = document.getElementById('confirm_password').value;

            if (first.length < 2) return showError('First name must be at least 2 characters');
            if (last.length < 2) return showError('Last name must be at least 2 characters');
            const emailRe = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRe.test(email)) return showError('Please enter a valid email');
            if (password.length < 8) return showError('Password must be at least 8 characters');
            if (password !== confirm) return showError('Passwords do not match');

            // assemble form data
            const fd = new FormData(form);
            fd.set('auto_login', document.getElementById('auto_login').checked ? 'true' : 'false');

            // submit
            submitBtn.disabled = true; loading.style.display = 'block';
            try {
                const resp = await fetch('user_reg.php', { method: 'POST', body: fd });
                const text = await resp.text();
                console.log('Raw response:', text);
                
                let data;
                try {
                    data = JSON.parse(text);
                } catch (parseErr) {
                    console.error('JSON parse error:', parseErr);
                    console.error('Response text:', text);
                    showError('Server returned invalid response. Check browser console for details.');
                    submitBtn.disabled = false; 
                    loading.style.display = 'none';
                    return;
                }

                if (data.status === 'success'){
                    showSuccess(data.message || 'Registered successfully');
                    form.reset();
                    
                    // Redirect after success
                    if (data.redirect) {
                        setTimeout(() => { 
                            window.location.href = data.redirect; 
                        }, 1500);
                    } else {
                        // Default redirect to login page
                        setTimeout(() => { 
                            window.location.href = 'log_in_and_register.php'; 
                        }, 2000);
                    }
                } else {
                    showError(data.message || 'Registration failed');
                }
            } catch (err) {
                showError('Network or server error. Check console.');
                console.error('Fetch error:', err);
            } finally {
                submitBtn.disabled = false; loading.style.display = 'none';
            }
        });
    </script>
</body>
</html>
