<?php
session_start();
include '../db.php';
global $conn;
error_reporting(E_ALL);
ini_set('display_errors', 1);
$room_id = $_GET['room_id'];
$user_id = $_SESSION['id'];
$username = $_SESSION['username'];
$stmt = $conn->prepare("SELECT room_participants.role FROM room_participants WHERE room_participants.user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$role = $result->fetch_assoc()['role'];

// Fetch room configurations
$stmt = $conn->prepare("SELECT * FROM rooms WHERE id = ?");
$stmt->bind_param("i", $room_id);
$stmt->execute();
$result = $stmt->get_result();
$room_config = $result->fetch_all(MYSQLI_ASSOC)[0];

// Fetch participants
$stmt = $conn->prepare("SELECT users.id, users.username, room_participants.role, room_participants.row_position, room_participants.column_position FROM users JOIN room_participants ON users.id = room_participants.user_id WHERE room_participants.room_id = ?");
$stmt->bind_param("i", $room_id);
$stmt->execute();
$result = $stmt->get_result();
$participants = $result->fetch_all(MYSQLI_ASSOC);

// Handle leave room request
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['leave_room'])) {
    $stmt = $conn->prepare("DELETE FROM room_participants WHERE room_id = ? AND user_id = ?");
    $stmt->bind_param("ii", $room_id, $user_id);
    $stmt->execute();
    header('Location: ../Dashboard/Dashboard.php');
    exit();
}

// Handle role change request
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_role'])) {
    $participant_id = $_POST['participant_id'];
    $new_role = $_POST['new_role'];

    // If the new role is "leader", set the current leader to "participant"
    if ($new_role == 'leader') {
        $stmt = $conn->prepare("UPDATE room_participants SET role = 'participant' WHERE room_id = ? AND role = 'leader'");
        $stmt->bind_param("i", $room_id);
        $stmt->execute();
    }

    $stmt = $conn->prepare("UPDATE room_participants SET role = ? WHERE room_id = ? AND user_id = ?");
    $stmt->bind_param("sii", $new_role, $room_id, $participant_id);
    $stmt->execute();
    header("Location: Room.php?room_id=$room_id");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Room</title>
    <style>
        #chat-box {
            border: 1px solid #ccc;
            padding: 10px;
            width: 100%;
            height: 300px;
            overflow-y: scroll;
            margin-bottom: 10px;
        }
        #timer {
            font-size: 2em;
            margin-bottom: 10px;
        }
    </style>
    <script>
        let conn;
        let roomId = <?php echo $room_id; ?>;
        let userId = <?php echo $user_id; ?>;
        let username = '<?php echo $username; ?>';
        let mode = '<?php echo $room_config['mode']; ?>';
        let soundMode = '<?php echo $room_config['sound_mode']; ?>';
        let delay = <?php echo $room_config['delay']; ?>;
        let rows = <?php echo $room_config['rows_count']; ?>;
        let columns = <?php echo $room_config['columns_count']; ?>;

        function connect() {
            console.log("Attempting to connect to WebSocket server...");
            conn = new WebSocket('ws://localhost:8000');
            conn.onopen = function(e) {
                console.log("Connection established!");
                conn.send(JSON.stringify({ type: 'join', room_id: roomId, user_id: userId }));
            };

            conn.onmessage = function(e) {
                let data = JSON.parse(e.data);
                if (data.type === 'start_timer') {
                    startCountdown(data.duration, data.start_time);
                } else if (data.type === 'play_sound') {
                    playSound(data.sound);
                }
            };

            conn.onclose = function(e) {
                console.log("Connection closed!");
            };
        }

        function startCountdown(duration, startTime) {
            let timer = duration - (Math.floor(Date.now() / 1000) - startTime);
            let display = document.getElementById('timer');

            let countdown = setInterval(function() {
                if (timer <= 0) {
                    clearInterval(countdown);
                    display.textContent = "Time's up!";
                    if (mode === 'auto') {
                        triggerSoundWave();
                    }
                } else {
                    let minutes = parseInt(timer / 60, 10);
                    let seconds = parseInt(timer % 60, 10);

                    minutes = minutes < 10 ? "0" + minutes : minutes;
                    seconds = seconds < 10 ? "0" + seconds : seconds;

                    display.textContent = minutes + ":" + seconds;
                    timer--;
                }
            }, 1000);
        }

        function triggerSoundWave() {
            let rowDelay = delay * 1000;
            let colDelay = delay * 1000;
            let currentRow = 0;
            let currentCol = 0;

            function triggerRow() {
                for (let i = 0; i < columns; i++) {
                    conn.send(JSON.stringify({ type: 'play_sound', room_id: roomId, row: currentRow, col: i }));
                }
                currentRow++;
                if (currentRow < rows) {
                    setTimeout(triggerRow, rowDelay);
                }
            }

            function triggerCol() {
                for (let i = 0; i < rows; i++) {
                    conn.send(JSON.stringify({ type: 'play_sound', room_id: roomId, row: i, col: currentCol }));
                }
                currentCol++;
                if (currentCol < columns) {
                    setTimeout(triggerCol, colDelay);
                }
            }

            if (soundMode === 'rows') {
                triggerRow();
            } else {
                triggerCol();
            }
        }

        function playSound(sound) {
            let audio = new Audio(sound.file_path);
            audio.play();
        }

        document.addEventListener("DOMContentLoaded", function() {
            connect();

            document.getElementById('timer-form').onsubmit = function(event) {
                event.preventDefault();
                let duration = parseInt(document.getElementById('duration').value, 10);
                conn.send(JSON.stringify({ type: 'start_timer', room_id: roomId, duration: duration, start_time: Math.floor(Date.now() / 1000) }));
                startCountdown(duration, Math.floor(Date.now() / 1000));
            };
        });
    </script>
</head>
<body>
<h2>Room</h2>
<h3>Participants</h3>
<ul>
    <?php foreach ($participants as $participant): ?>
        <li>
            <?php echo htmlspecialchars($participant['username']) . ' (' . $participant['role'] . ')'; ?>
            <?php if ($role == 'admin' && $participant['username'] != $username): ?>
                <form method="POST" action="Room.php?room_id=<?php echo $room_id; ?>" style="display: inline;">
                    <input type="hidden" name="participant_id" value="<?php echo $participant['id']; ?>">
                    <input type="hidden" name="new_role" value="<?php echo $participant['role'] == 'leader' ? 'participant' : 'leader'; ?>">
                    <button type="submit" name="change_role">
                        <?php echo $participant['role'] == 'leader' ? 'Make Participant' : 'Make Leader'; ?>
                    </button>
                </form>
            <?php endif; ?>
        </li>
    <?php endforeach; ?>
</ul>

<?php if ($role == 'leader' || $role == 'admin'): ?>
    <h3>Start Timer</h3>
    <form id="timer-form" method="POST">
        <label for="duration">Duration (seconds):</label>
        <input type="number" id="duration" name="duration" required>
        <button type="submit">Start Timer</button>
    </form>
<?php endif; ?>

<h3>Countdown Timer</h3>
<div id="timer">00:00</div>

<form method="POST" action="Room.php?room_id=<?php echo $room_id; ?>">
    <input type="hidden" name="leave_room" value="1">
    <button type="submit">Leave Room</button>
</form>

</body>
</html>