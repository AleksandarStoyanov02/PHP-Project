<?php
session_start();
include 'login.html';
include '../db.php';
global $conn;

error_reporting(E_ALL);
ini_set('display_errors', 1);
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Prepare and bind
    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();

        // Verify password
        if (password_verify($password, $user['password'])) {
            $_SESSION['username'] = $username;
            $_SESSION['role'] = $user['role'];
            $_SESSION['id'] = $user['id'];
            header('Location: ../Dashboard/dashboard.php');
            exit();
        } else {
            echo "Invalid credentials.";
        }
    } else {
        echo "No user found with that username.";
    }
    $stmt->close();
    $conn->close();
}