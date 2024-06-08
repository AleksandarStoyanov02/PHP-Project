<?php
// Process text command
global $conn;
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Assume the form has a field named 'text_reaction'
    $text_reaction = $_POST['text_reaction'];

    // Insert text reaction into database
    $query = "INSERT INTO audience_emotions (event_id, user_id, emotion) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("iis", $event_id, $user_id, $text_reaction);
    $stmt->execute();
    $stmt->close();
}