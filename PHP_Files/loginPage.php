<?php
// Start session to check if user is already logged in
session_start();

// If user is already logged in, redirect to homepage
if (isset($_SESSION['user_id']) && isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    header('Location: homepage.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ReConnect - Elite Alumni Network</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #4a0e0e 0%, #7d1935 50%, #4a0e0e 100%);
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
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320"><path fill="%23ffffff" fill-opacity="0.05" d="M0,96L48,112C96,128,192,160,288,160C384,160,480,128,576,122.7C672,117,768,139,864,154.7C960,171,1056,181,1152,165.3C1248,149,1344,107,1392,85.3L1440,64L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path></svg>') no-repeat bottom;
            background-size: cover;
            opacity: 0.3;
        }

        .container {
            background: rgba(255, 255, 255, 0.98);
            border-radius: 25px;
            box-shadow: 0 30px 80px rgba(0, 0, 0, 0.5);
            overflow: hidden;
            max-width: 1100px;
            width: 100%;
            display: flex;
            min-height: 650px;
            position: relative;
            z-index: 1;
            backdrop-filter: blur(10px);
        }

        .info-section {
            flex: 1;
            background: linear-gradient(135deg, #6b1329 0%, #8b1538 50%, #4a0e0e 100%);
            color: white;
            padding: 60px 50px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
            overflow: hidden;
        }

        .info-section::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            animation: pulse 15s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); opacity: 0.3; }
            50% { transform: scale(1.1); opacity: 0.5; }
        }

        .logo-section {
            position: relative;
            z-index: 2;
        }

        .logo-section h1 {
            font-size: 2.8em;
            margin-bottom: 15px;
            font-weight: 700;
            letter-spacing: -1px;
            background: linear-gradient(135deg, #ffffff 0%, #f4d4d4 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .logo-section .tagline {
            font-size: 1.1em;
            opacity: 0.95;
            font-weight: 300;
            letter-spacing: 0.5px;
        }

        .alumni-images {
            position: relative;
            z-index: 2;
            margin: 30px 0;
        }

        .alumni-images h3 {
            font-size: 1.3em;
            margin-bottom: 20px;
            font-weight: 600;
            opacity: 0.95;
        }

        .image-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin-bottom: 25px;
        }

        .alumni-img {
            width: 100%;
            height: 150px;
            border-radius: 15px;
            object-fit: cover;
            border: 3px solid rgba(255, 255, 255, 0.3);
            transition: transform 0.3s, border-color 0.3s;
            cursor: pointer;
        }

        .alumni-img:hover {
            transform: scale(1.05);
            border-color: rgba(255, 255, 255, 0.6);
        }

        .features {
            position: relative;
            z-index: 2;
        }

        .features h3 {
            font-size: 1.2em;
            margin-bottom: 15px;
            font-weight: 600;
        }

        .feature-list {
            list-style: none;
        }

        .feature-list li {
            padding: 10px 0;
            padding-left: 30px;
            position: relative;
            font-size: 0.95em;
            opacity: 0.9;
        }

        .feature-list li::before {
            content: '✓';
            position: absolute;
            left: 0;
            color: #ffd700;
            font-weight: bold;
            font-size: 1.2em;
        }

        .form-section {
            flex: 1;
            padding: 60px 50px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            background: white;
        }

        .form-container {
            display: none;
            animation: fadeIn 0.5s ease-in;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .form-container.active {
            display: block;
        }

        h2 {
            color: #4a0e0e;
            margin-bottom: 10px;
            font-size: 2.2em;
            font-weight: 700;
        }

        .subtitle {
            color: #7d1935;
            margin-bottom: 30px;
            font-size: 0.95em;
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
            color: #4a0e0e;
            font-weight: 600;
            font-size: 0.9em;
        }

        input, textarea {
            width: 100%;
            padding: 13px 15px;
            border: 2px solid #e0d4d4;
            border-radius: 10px;
            font-size: 14px;
            transition: all 0.3s;
            background: #fafafa;
        }

        input:focus, textarea:focus {
            outline: none;
            border-color: #7d1935;
            background: white;
            box-shadow: 0 0 0 3px rgba(125, 25, 53, 0.1);
        }

        textarea {
            resize: vertical;
            min-height: 80px;
            font-family: inherit;
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
            color: #7d1935;
            user-select: none;
            font-size: 1.2em;
            transition: opacity 0.3s;
        }

        .toggle-password:hover {
            opacity: 0.7;
        }

        .error-message {
            color: #c41e3a;
            font-size: 0.85em;
            margin-top: 5px;
            display: none;
            font-weight: 500;
        }

        .error-message.show {
            display: block;
        }

        .success-message {
            background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
            color: #155724;
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: none;
            border-left: 4px solid #28a745;
            font-weight: 500;
        }

        .success-message.show {
            display: block;
            animation: slideDown 0.3s ease-out;
        }

        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .alert-message {
            background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
            color: #721c24;
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: none;
            border-left: 4px solid #c41e3a;
            font-weight: 500;
        }

        .alert-message.show {
            display: block;
            animation: slideDown 0.3s ease-out;
        }

        .btn {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #6b1329 0%, #8b1538 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
            box-shadow: 0 4px 15px rgba(107, 19, 41, 0.3);
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(107, 19, 41, 0.4);
            background: linear-gradient(135deg, #8b1538 0%, #6b1329 100%);
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
            margin-top: 25px;
            color: #666;
            font-size: 0.95em;
        }

        .switch-form a {
            color: #7d1935;
            text-decoration: none;
            font-weight: 700;
            cursor: pointer;
            transition: color 0.3s;
        }

        .switch-form a:hover {
            color: #6b1329;
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
            cursor: pointer;
        }

        .checkbox-group label {
            margin-bottom: 0;
            font-weight: normal;
            cursor: pointer;
            color: #666;
        }

        .loading {
            display: none;
            text-align: center;
            margin-top: 15px;
        }

        .loading.show {
            display: block;
        }

        .spinner {
            border: 3px solid #f3f3f3;
            border-top: 3px solid #7d1935;
            border-radius: 50%;
            width: 35px;
            height: 35px;
            animation: spin 1s linear infinite;
            margin: 0 auto 10px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .loading p {
            color: #7d1935;
            font-weight: