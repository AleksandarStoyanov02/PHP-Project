<?php
session_start();
include '../db.php';
global $conn;
error_reporting(E_ALL);
ini_set('display_errors', 1);

$messages = [];

if (!$conn) {
    $messages[] = "Error connecting to the database: " . mysqli_connect_error();
    exit();
}

if (!isset($_SESSION['username'])) {
    header('Location: ../Login/login.php');
    exit();
}

function getUserByUsername($conn, $username) {
    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
    if (!$stmt) {
        return "Error preparing statement: " . $conn->error;
    }
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    if (!$result) {
        return "Error executing statement: " . $stmt->error;
    }
    if ($result->num_rows > 0) {
        return $result->fetch_assoc();
    } else {
        return "User not found.";
    }
}

function updateUserProfile($conn, $username, $name, $email, $profile_picture) {
    $stmt = $conn->prepare("UPDATE users SET name=?, email=?, profile_picture=? WHERE username=?");
    if (!$stmt) {
        return "Error preparing statement: " . $conn->error;
    }
    $stmt->bind_param("ssss", $name, $email, $profile_picture, $username);
    if ($stmt->execute()) {
        $_SESSION['email'] = $email;
        $_SESSION['profile_picture'] = $profile_picture; // Update session with new profile picture
        return "Profile updated successfully";
    } else {
        return "Error updating profile: " . $stmt->error;
    }
}

$username = $_SESSION['username'];

$user = getUserByUsername($conn, $username);
if (is_string($user)) {
    $messages[] = $user;
    $user = null;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $conn->real_escape_string($_POST['name']);
    $email = $conn->real_escape_string($_POST['email']);

    $profile_picture = $user['profile_picture']; // Default to the current profile picture
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === 0) {
        $target_dir = __DIR__ . "/../Images/"; // Use relative path
        $target_file = $target_dir . basename($_FILES['profile_picture']['name']);
        $uploadOk = 1;
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        // Check if image file is an actual image or fake image
        $check = getimagesize($_FILES['profile_picture']['tmp_name']);
        if ($check !== false) {
            $uploadOk = 1;
        } else {
            $messages[] = "File is not an image.";
            $uploadOk = 0;
        }

        // Check file size
        if ($_FILES['profile_picture']['size'] > 500000) {
            $messages[] = "Sorry, your file is too large.";
            $uploadOk = 0;
        }

        // Allow certain file formats
        if ($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg" && $imageFileType != "gif") {
            $messages[] = "Sorry, only JPG, JPEG, PNG & GIF files are allowed.";
            $uploadOk = 0;
        }

        // Check if $uploadOk is set to 0 by an error
        if ($uploadOk == 0) {
            $messages[] = "Sorry, your file was not uploaded.";
        } else {
            if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $target_file)) {
                $messages[] = "The file " . htmlspecialchars(basename($_FILES['profile_picture']['name'])) . " has been uploaded.";
                // Store only the relative path in the database
                $profile_picture = "Images/" . basename($_FILES['profile_picture']['name']);
            } else {
                $messages[] = "Sorry, there was an error uploading your file.";
            }
        }
    }

    $updateMessage = updateUserProfile($conn, $username, $name, $email, $profile_picture);
    $messages[] = $updateMessage;

    // Refresh the user data after update
    $user = getUserByUsername($conn, $username);
    if (is_string($user)) {
        $messages[] = $user;
        $user = null;
    }
}

include 'editProfile.html';
