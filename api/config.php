<?php
/**
 * Database Configuration File for Vercel Deployment
 * 
 * This file handles the database connection for the Library Management System.
 * It uses MySQLi (MySQL Improved) for secure database operations.
 * 
 * For Vercel deployment, you can use:
 * - PlanetScale (Free MySQL compatible database)
 * - Railway (Free MySQL database)
 * - Any external MySQL host
 */

// Get database credentials from environment variables (Vercel)
// Or use default localhost for local development
$db_host = getenv('DB_HOST') ?: 'localhost';
$db_user = getenv('DB_USER') ?: 'root';
$db_pass = getenv('DB_PASS') ?: '';
$db_name = getenv('DB_NAME') ?: 'library_management';
$db_port = getenv('DB_PORT') ?: 3306;

// Create database connection
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name, $db_port);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset to UTF-8 for proper character encoding
$conn->set_charset("utf8");

/**
 * Function to sanitize user input to prevent SQL injection
 * 
 * @param string $data - Raw input data
 * @return string - Sanitized data
 */
function clean_input($data) {
    global $conn;
    $data = trim($data);                    // Remove whitespace
    $data = stripslashes($data);            // Remove backslashes
    $data = htmlspecialchars($data);        // Convert special characters to HTML entities
    $data = $conn->real_escape_string($data); // Escape SQL special characters
    return $data;
}

/**
 * Function to display success/error messages
 * 
 * @param string $message - Message to display
 * @param string $type - Type of message ('success', 'error', 'warning', 'info')
 * @return string - HTML formatted alert
 */
function show_message($message, $type = 'info') {
    $alert_class = [
        'success' => 'alert-success',
        'error' => 'alert-danger',
        'warning' => 'alert-warning',
        'info' => 'alert-info'
    ];
    
    $class = $alert_class[$type] ?? 'alert-info';
    return "<div class='alert $class alert-dismissible fade show' role='alert'>
                $message
                <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
            </div>";
}
?>
