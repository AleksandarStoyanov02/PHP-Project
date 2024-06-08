<?php
session_start();
include '../db.php';
global $conn;
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['username'])) {
    header('Location: ../Login/login.php');
    exit();
}

$username = $_SESSION['username'];

// Prepare and bind
$stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
} else {
    echo "User not found.";
    exit();
}
$stmt->close();

// Default values if not set
$profile_picture = isset($user['profile_picture']) ? $user['profile_picture'] : 'Images/default_profile_picture.png';
$points = isset($user['points']) ? $user['points'] : 0;

// Read the HTML template
$html = file_get_contents('dashboard.html');

// Replace placeholders with actual data
$html = str_replace('{{username}}', htmlspecialchars($user['username']), $html);
$html = str_replace('{{profile_picture}}', htmlspecialchars($profile_picture), $html);
$html = str_replace('{{email}}', htmlspecialchars($user['email']), $html);
$html = str_replace('{{points}}', htmlspecialchars($points), $html);

// Output the final HTML
echo $html;

$conn->close();
