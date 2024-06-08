<?php
global $conn;
session_start();
include 'db.php';

if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit();
}

$event_id = $_GET['id'];

$event = $conn->query("SELECT * FROM events WHERE id = $event_id")->fetch_assoc();
$commands = $conn->query("SELECT * FROM commands WHERE event_id = $event_id ORDER BY timestamp DESC LIMIT 1")->fetch_assoc();

