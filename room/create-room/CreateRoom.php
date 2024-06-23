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
<html>
<head>
    <title>Create Room</title>
</head>
<body>
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
</body>
</html>
