<?php
// Process emote command
global $conn;
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Assume the form has a field named 'selected_emotion'
    $selected_emotion = $_POST['selected_emotion'];

    // Insert selected emotion into database
    $query = "INSERT INTO audience_emotions (event_id, user_id, emotion) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("iis", $event_id, $user_id, $selected_emotion);
    $stmt->execute();
    $stmt->close();
}