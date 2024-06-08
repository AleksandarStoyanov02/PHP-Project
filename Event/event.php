<?php
global $conn;
session_start();
include 'db.php';

if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit();
}

$event_id = $_GET['id'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $command = $_POST['command'];

    $stmt = $conn->prepare("INSERT INTO commands (event_id, command) VALUES (?, ?)");
    $stmt->bind_param("is", $event_id, $command);
    $stmt->execute();
}

$event = $conn->query("SELECT * FROM events WHERE id = $event_id")->fetch_assoc();
$commands = $conn->query("SELECT * FROM commands WHERE event_id = $event_id ORDER BY timestamp DESC");
