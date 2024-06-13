<?php
session_start();
include '../../db.php';
require '../../util/util.php';
include 'JoinRoom.html';
global $conn;
error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $code = $_POST['code'];
    $user_id = $_SESSION['id'];

    if (isNullOrEmptyString($code)) {
        echo "Code must not be empty";
    }

    $stmt = $conn->prepare("SELECT id FROM rooms WHERE code = ?");
    $stmt->bind_param("s", $code);
    $stmt->execute([$code]);
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo "Room not found.";
    }
    $room = $result->fetch_assoc();

    if ($room) {
        // Check if already joined
        $stmt = $conn->prepare("SELECT * FROM room_participants WHERE room_id = ? AND user_id = ?");
        $stmt->execute([$room['id'], $user_id]);
    if (!$stmt->fetch()) {
        // Join the room
        $stmt = $conn->prepare("INSERT INTO room_participants (room_id, user_id) VALUES (?, ?)");
        $stmt->execute([$room['id'], $user_id]);
    }
    header('Location: ../Room.php?room_id=' . $room['id']);
    } else {
        echo "Room not found.";
    }
}
?>