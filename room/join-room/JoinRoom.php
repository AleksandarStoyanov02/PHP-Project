<?php
session_start();
include '../../db.php';
require '../../util/util.php';

global $conn;
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Fetch all rooms
$stmt = $conn->prepare("SELECT id, name, rows_count, columns_count FROM rooms");
$stmt->execute();
$result = $stmt->get_result();
$rooms = $result->fetch_all(MYSQLI_ASSOC);

$error_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $room_id = $_POST['room_id'];
    $code = $_POST['code'];
    $row = $_POST['row'];
    $column = $_POST['column'];
    $user_id = $_SESSION['id'];
    $role = $_SESSION['role'];

    if (isNullOrEmptyString($code)) {
        $error_message = "Code must not be empty";
    } else {
        $stmt = $conn->prepare("SELECT id FROM rooms WHERE id = ? AND code = ?");
        $stmt->bind_param("is", $room_id, $code);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            $error_message = "Room not found or invalid code.";
        } else {
            $room = $result->fetch_assoc();
            // Check if already joined
            $stmt = $conn->prepare("SELECT * FROM room_participants WHERE room_id = ? AND user_id = ?");
            $stmt->bind_param("ii", $room['id'], $user_id);
            $stmt->execute();
            if (!$stmt->fetch()) {
                // Join the room
                echo $role;
                $stmt = $conn->prepare("INSERT INTO room_participants (room_id, user_id, role, row_position, column_position) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("iisii", $room['id'], $user_id, $role, $row, $column);
                $stmt->execute();
            }
            header('Location: ../Room.php?room_id=' . $room['id']);
            exit();
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Join Room</title>
    <script>
        function selectRoom() {
            const roomData = document.getElementById('room_id').value.split(',');
            const rows = parseInt(roomData[1]);
            const columns = parseInt(roomData[2]);

            let rowSelect = document.getElementById('row');
            rowSelect.innerHTML = '';
            for (let i = 1; i <= rows; i++) {
                let option = document.createElement('option');
                option.value = i;
                option.text = i;
                rowSelect.add(option);
            }

            let colSelect = document.getElementById('column');
            colSelect.innerHTML = '';
            for (let i = 1; i <= columns; i++) {
                let option = document.createElement('option');
                option.value = i;
                option.text = i;
                colSelect.add(option);
            }

            document.getElementById('room_details').style.display = 'block';
        }
    </script>
</head>
<body>
<h2>Join Room</h2>

<?php if ($error_message): ?>
    <p style="color: red;"><?php echo $error_message; ?></p>
<?php endif; ?>

<h3>Select a Room</h3>
<form method="POST" action="JoinRoom.php">
    <label for="room_id">Room:</label>
    <select id="room_id" name="room_id" onchange="selectRoom()" required>
        <option value="">Select Room</option>
        <?php foreach ($rooms as $room): ?>
            <option value="<?php echo $room['id'] . ',' . $room['rows_count'] . ',' . $room['columns_count']; ?>">
                <?php echo htmlspecialchars($room['name']); ?>
            </option>
        <?php endforeach; ?>
    </select>

    <div id="room_details" style="display:none;">
        <label for="code">Room Code:</label>
        <input type="text" id="code" name="code" required><br>

        <label for="row">Row:</label>
        <select id="row" name="row" required>
            <!-- Options will be added dynamically -->
        </select><br>

        <label for="column">Column:</label>
        <select id="column" name="column" required>
            <!-- Options will be added dynamically -->
        </select><br>

        <button type="submit">Join Room</button>
    </div>
</form>
</body>
</html>