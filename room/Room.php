<?php
session_start();
include '../db.php';
global $conn;
error_reporting(E_ALL);
ini_set('display_errors', 1);

$room_id = $_GET['room_id'];
$user_id = $_SESSION['id'];

// Check if the user is a participant of the room
$stmt = $conn->prepare("SELECT role FROM room_participants WHERE room_id = ? AND user_id = ?");
$stmt->bind_param("ii", $room_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "Not part of the room.";
}

if ($_SESSION['role'] == 'admin' && isset($_POST['assign_leader'])) {
    $leader_id = $_POST['leader_id'];
    // Reset any previous leader roles
    $stmt = $conn->prepare("UPDATE room_participants SET role = 'participant' WHERE room_id = ? AND role = 'leader'");
    $stmt->bind_param("i", $room_id);
    $stmt->execute();
    // Assign new leader
    $stmt = $conn->prepare("UPDATE room_participants SET role = 'leader' WHERE room_id = ? AND user_id = ?");
    $stmt->bind_param("ii", $room_id, $leader_id);
    $stmt->execute();
}

if ($_SESSION['role'] == 'admin' && isset($_POST['close_room'])) {
    // Delete all related records from the database
    $stmt = $conn->prepare("UPDATE rooms SET is_closed = 1 WHERE id = ?");
    $stmt->bind_param("i", $room_id);
    $stmt->execute([$room_id]);

    $stmt = $pdo->prepare("DELETE FROM room_participants WHERE room_id = ?");
    $stmt->execute([$room_id]);

    $stmt = $pdo->prepare("DELETE FROM points WHERE room_id = ?");
    $stmt->execute([$room_id]);

    $stmt = $pdo->prepare("DELETE FROM rooms WHERE id = ?");
    $stmt->execute([$room_id]);

    // Kick all participants to the dashboard
    header('Location: dashboard.php');
    exit();
}

// Fetch participants
$stmt = $conn->prepare("SELECT users.id, users.username, room_participants.role FROM users JOIN room_participants ON users.id = room_participants.user_id WHERE room_participants.room_id = ?");
$stmt->bind_param("i", $room_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "Error while fetching users.";
}
$participants = $result->fetch_all();
echo $_SESSION['role'];
?>

<!DOCTYPE html>
<html>
<head>
    <title>Room</title>
</head>
<body>
<h2>Room</h2>
<h3>Participants</h3>
<ul>
    <?php foreach ($participants as $participant): ?>
        <li><?php echo $participant[1] . ' (' . $participant[0] . ')'; ?></li>
    <?php endforeach; ?>
</ul>

<?php if ($_SESSION['role'] == 'admin'): ?>
    <h3>Assign Leader</h3>
    <form method="POST" action="Room.php?room_id=<?php echo $room_id; ?>">
        <label for="leader_id">Select Leader:</label>
        <select id="leader_id" name="leader_id">
            <?php foreach ($participants as $participant): ?>
                <option value="<?php echo $participant[0]; ?>"><?php echo htmlspecialchars($participant[1]); ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" name="assign_leader">Assign Leader</button>
    </form>

    <form method="POST" action="Room.php?room_id=<?php echo $room_id; ?>">
        <button type="submit" name="close_room" onclick="return confirm('Are you sure you want to close this room?')">Close Room</button>
    </form>
<?php endif; ?>
</body>
</html>


