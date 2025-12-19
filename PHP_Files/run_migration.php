<?php
// This script runs the migration to add university content support

require_once 'settings/db_class.php';

// Create database connection
$db_obj = new db_connection();
$db_obj->db_connect();
$db = $db_obj->db;

echo "<h2>Running University Content Migration</h2>\n";
echo "<pre>\n";

// Read the migration SQL file
$migration_sql = file_get_contents('migrations_university_content.sql');

// Remove comments and split by semicolon
$lines = explode("\n", $migration_sql);
$clean_sql = '';
foreach ($lines as $line) {
    $line = trim($line);
    if (!empty($line) && !str_starts_with($line, '--')) {
        $clean_sql .= $line . ' ';
    }
}

// Split by semicolon to get individual statements
$statements = array_filter(array_map('trim', explode(';', $clean_sql)));

$errors = [];
$success = 0;

foreach ($statements as $statement) {
    if (empty($statement)) continue;
    
    echo "Executing: " . substr($statement, 0, 60) . "...\n";
    
    if (mysqli_query($db, $statement)) {
        $success++;
        echo "  ✓ Success\n\n";
    } else {
        $error_msg = mysqli_error($db);
        // Ignore "duplicate column" and "duplicate key" errors (they mean already migrated)
        if (strpos($error_msg, 'Duplicate column') !== false || 
            strpos($error_msg, 'Duplicate key') !== false ||
            strpos($error_msg, "check constraint 'fk_") !== false ||
            strpos($error_msg, 'Duplicate foreign key') !== false) {
            echo "  ⚠ Already exists (skipped)\n\n";
            $success++;
        } else {
            $errors[] = "✗ Failed: " . substr($statement, 0, 50) . "...\n  Error: " . $error_msg . "\n";
            echo "  ✗ Error: " . $error_msg . "\n\n";
        }
    }
}

echo "\n=== Migration Results ===\n";
echo "Successful queries: $success\n";
if (!empty($errors)) {
    echo "Failed queries: " . count($errors) . "\n\n";
    foreach ($errors as $error) {
        echo $error . "\n";
    }
} else {
    echo "✅ All migrations completed successfully!\n";
}

echo "\n</pre>\n";
echo "<p><a href='view/university_admin_panel.php'>Go to University Admin Panel</a></p>\n";
?>
