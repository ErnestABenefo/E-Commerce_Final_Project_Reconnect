<?php
session_start();

// Set user as global admin for testing
$_SESSION['user_id'] = 1;

// Test data
$testData = [
    'university_id' => 1,
    'name' => 'Test University',
    'location' => 'Test Location',
    'university_type' => 'public',
    'website' => 'https://test.edu',
    'contact_email' => 'test@test.edu',
    'contact_phone' => '1234567890',
    'address' => '123 Test St',
    'established_year' => 2000,
    'description' => 'Test description'
];

// Simulate POST data
file_put_contents('php://input', json_encode($testData));

echo "Testing update_university.php...\n";
echo "Test data: " . json_encode($testData) . "\n\n";

// Include the update script
ob_start();
include 'update_university.php';
$output = ob_get_clean();

echo "Output:\n";
echo $output;
