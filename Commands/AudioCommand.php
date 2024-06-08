<?php
// Process audio command
global $conn;
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Handle file upload
    if (isset($_FILES['audio_file'])) {
        // Define upload directory and file path
        $upload_dir = __DIR__ . "/uploads/";
        $audio_path = $upload_dir . basename($_FILES['audio_file']['name']);

        // Move uploaded file to designated directory
        if (move_uploaded_file($_FILES['audio_file']['tmp_name'], $audio_path)) {
            // Insert audio reaction into database
            $query = "INSERT INTO audience_audio_reactions (event_id, user_id, audio_path) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("iis", $event_id, $user_id, $audio_path);
            $stmt->execute();
            $stmt->close();
        } else {
            // Handle upload failure
            echo "Sorry, there was an error uploading your file.";
        }
    }
}
