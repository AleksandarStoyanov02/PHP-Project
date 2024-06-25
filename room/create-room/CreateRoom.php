<?php
session_start();
include '../../db.php';
require '../../util/util.php';
global $conn;
error_reporting(E_ALL);
ini_set('display_errors', 1);

$stmt = $conn->prepare("SELECT id, name FROM sound");
$stmt->execute();
$result = $stmt->get_result();
$sounds = $result->fetch_all();
function validateName($name): void
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
    $room_name = $_POST['room_name'];
    $mode = $_POST['mode'];
    $code = substr(md5(uniqid(rand(), true)), 0, 6);
    $rows = $_POST['rows'];
    $columns = $_POST['columns'];
    $delay = $_POST['delay'];
    $sound_mode = $_POST['sound_mode'];
    $sound_id = $_POST['sound_id'];
    echo $sound_id;

    $stmt = $conn->prepare("INSERT INTO rooms (name, code, mode, rows_count, columns_count, delay, sound_mode, sound_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$room_name, $code, $mode, $rows, $columns, $delay, $sound_mode, $sound_id]);
    header('Location: ../../Dashboard/dashboard.php');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Room</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #87CEEB;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        .container {
            background-color: #F5F5F5;
            border: 3px solid #5CACEE;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 10px #F5F5F5;
            max-width: 600px;
            width: 100%;
        }
        h2 {
            color: #333;
            text-align: center;
            padding-bottom: 10px;
            margin-bottom: 20px;
            border-bottom: 2px solid #5CACEE;
        }
        form {
            width: 100%;
        }
        label {
            font-weight: bold;
            display: block;
            margin-bottom: 5px;
        }
        input[type=text], input[type=number], select {
            width: 100%;
            padding: 8px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
        }
        button[type=submit] {
            background-color: #5CACEE;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }
        button[type=submit]:hover {
            background-color: #007BFF;
        }
    </style>
</head>
<body>
<div class="container">
    <h2>Create Room</h2>
    <form action="CreateRoom.php" method="POST">
        <label for="room_name">Room Name:</label>
        <input type="text" id="room_name" name="room_name" required><br>

        <label for="mode">Mode:</label>
        <select id="mode" name="mode" required>
            <option value="auto">Auto</option>
            <option value="manual">Manual</option>
        </select><br>

        <label for="rows">Rows:</label>
        <input type="number" id="rows" name="rows" required><br>

        <label for="columns">Columns:</label>
        <input type="number" id="columns" name="columns" required><br>

        <label for="delay">Delay (ms):</label>
        <input type="number" id="delay" name="delay" required><br>

        <label for="sound_mode">Sound Mode:</label>
        <select id="sound_mode" name="sound_mode" required>
            <option value="rows">Rows</option>
            <option value="columns">Columns</option>
        </select><br>

        <label for="sound_id">Sound:</label>
        <select id="sound_id" name="sound_id" required>
            <?php foreach ($sounds as $sound): ?>
                <option value="<?php echo $sound[0]; ?>"><?php echo htmlspecialchars($sound[1]); ?></option>
            <?php endforeach; ?>
        </select><br>

        <button type="submit">Create Room</button>
    </form>
</div>
</body>
</html>

