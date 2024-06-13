<?php
session_start();
include '../../db.php';
require '../../util/util.php';
include 'CreateRoom.html';
global $conn;
error_reporting(E_ALL);
ini_set('display_errors', 1);

function validateName($name)
{
    global $conn;
    if (IsNullOrEmptyString($name)) {
        echo "Name is required";
    }
    $stmt = $conn->prepare("SELECT * FROM rooms WHERE name = ?");
    $stmt->bind_param("s", $name);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        echo "Room already exists.";
        exit();
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $code = substr(md5(uniqid(rand(), true)), 0, 6);
    $created_by = $_SESSION['id'];

    validateName($name);
    $stmt = $conn->prepare("INSERT INTO rooms (code, name, created_by) VALUES (?, ?, ?)");
    $stmt->execute([$code, $name, $created_by]);

    header('Location: ../../Dashboard/dashboard.php');
}
?>