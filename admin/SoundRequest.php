<?php
session_start();
include '../db.php';
global $conn;
error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['sound_file'])) {
    $user_id = $_SESSION['id'];
    $file = $_FILES['sound_file'];
    $sound_name = $_POST['sound_name'];

    // Ensure the target directory exists
    $target_dir = "../sound-requests/";
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    // Move the uploaded file to the target directory
    $target_file = $target_dir . basename($file["name"]);
    if (move_uploaded_file($file["tmp_name"], $target_file)) {
        $stmt = $conn->prepare("INSERT INTO sound_requests (user_id, file_path, sound_name) VALUES (?, ?, ?)");
        $stmt->execute([$user_id, $target_file, $sound_name]);

        echo "Sound request submitted successfully.";
    } else {
        echo "There was an error uploading your file.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Sound Request</title>
</head>
<body>
<h2>Request a New Sound</h2>
<form action="SoundRequest.php" method="POST" enctype="multipart/form-data">
    <label for="sound_name">Sound Name:</label>
    <input type="text" name="sound_name" id="sound_name" required>
    <br>
    <label for="sound_file">Select sound file (MP3 only):</label>
    <input type="file" name="sound_file" id="sound_file" accept=".mp3" required>
    <br>
    <button type="submit">Upload</button>
</form>
</body>
</html>