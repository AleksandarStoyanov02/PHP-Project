<?php
session_start();
include '../db.php';
global $conn;
error_reporting(E_ALL);
ini_set('display_errors', 1);
$room_id = $_GET['room_id'];
$user_id = $_SESSION['id'];
$username = $_SESSION['username'];

// Fetch user role
$stmt = $conn->prepare("SELECT room_participants.role, room_participants.row_position, room_participants.column_position FROM room_participants WHERE room_participants.user_id = ? AND room_participants.room_id = ?");
$stmt->bind_param("ii", $user_id, $room_id);
$stmt->execute();
$result = $stmt->get_result();
$roomParticipant = $result->fetch_assoc();
$role = $roomParticipant['role'];
$rowPosition = $roomParticipant['row_position'];
$columnPosition = $roomParticipant['column_position'];

// Fetch user points
$stmt = $conn->prepare("SELECT points FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$userPoints = $result->fetch_assoc();

// Fetch room configurations
$stmt = $conn->prepare("SELECT * FROM rooms WHERE id = ?");
$stmt->bind_param("i", $room_id);
$stmt->execute();
$result = $stmt->get_result();
$room_config = $result->fetch_assoc();
$stmt = $conn->prepare("SELECT file_path FROM sound WHERE id = ?");
$stmt->bind_param("i", $room_config['sound_id']);
$stmt->execute();
$result = $stmt->get_result();
$auto_sound = $result->fetch_assoc()['file_path'];

// Fetch participants
$stmt = $conn->prepare("SELECT users.id, users.username, room_participants.role FROM users JOIN room_participants ON users.id = room_participants.user_id WHERE room_participants.room_id = ?");
$stmt->bind_param("i", $room_id);
$stmt->execute();
$result = $stmt->get_result();
$participants = $result->fetch_all(MYSQLI_ASSOC);

// Fetch available sounds
$stmt = $conn->prepare("SELECT * FROM sound");
$stmt->execute();
$result = $stmt->get_result();
$sounds = $result->fetch_all(MYSQLI_ASSOC);

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

// Handle configuration change request
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_config'])) {
    $new_mode = $_POST['mode'];
    $new_sound_mode = $_POST['sound_mode'];
    $new_delay = $_POST['delay'];
    $new_rows_count = $_POST['rows_count'];
    $new_columns_count = $_POST['columns_count'];
    $auto_sound_id = $_POST['auto_sound'];

    $stmt = $conn->prepare("UPDATE rooms SET mode = ?, sound_mode = ?, delay = ?, rows_count = ?, columns_count = ?, sound_id = ? WHERE id = ?");
    $stmt->bind_param("ssiiiii", $new_mode, $new_sound_mode, $new_delay, $new_rows_count, $new_columns_count, $auto_sound_id, $room_id);
    $stmt->execute();
    header("Location: Room.php?room_id=$room_id");
    exit();
}

// Handle position update request
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_position'])) {
    $new_row = $_POST['row_position'];
    $new_column = $_POST['column_position'];
    $stmt = $conn->prepare("UPDATE room_participants SET row_position = ?, column_position = ? WHERE user_id = ? AND room_id = ?");
    $stmt->bind_param("iiii", $new_row, $new_column, $user_id, $room_id);
    $stmt->execute();
    header("Location: Room.php?room_id=$room_id");
    exit();
}

// Handle user points
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['sound_price'])) {
    $newPoints = $userPoints['points'] - intval($_POST['sound_price']);
    $stmt = $conn->prepare("UPDATE users SET points = ? WHERE id = ?");
    $stmt->bind_param("ii", $newPoints, $user_id);
    $stmt->execute();
    header("Location: Room.php?room_id=$room_id");
}

// Handle adding points to all participants
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_points'])) {
    $points_to_add = intval($_POST['points_to_add']);

    // Fetch all participants in the room
    $stmt = $conn->prepare("SELECT user_id FROM room_participants WHERE room_id = ?");
    $stmt->bind_param("i", $room_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $participants = $result->fetch_all(MYSQLI_ASSOC);

    // Update points for each participant
    $stmt = $conn->prepare("UPDATE users SET points = points + ? WHERE id = ?");
    foreach ($participants as $participant) {
        $stmt->bind_param("ii", $points_to_add, $participant['user_id']);
        $stmt->execute();
    }

    header("Location: Room.php?room_id=$room_id");
    exit();
}

?>

<!DOCTYPE html>
<html>
<head>
    <title>Room</title>
    <style>
<<<<<<< Updated upstream
        /* Your CSS styles */
=======
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
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            max-width: 800px;
            width: 100%;
            margin: 20px;
            text-align: center;
        }

        .section {
            margin-bottom: 20px;
        }

        h2, h3 {
            color: #333;
            border-bottom: 2px solid #5CACEE;
            padding-bottom: 10px;
            margin-bottom: 20px;
            text-align: center;
        }

        ul {
            list-style-type: none;
            padding: 0;
            text-align: center;
            margin-bottom: 20px;
        }

        li {
            margin-bottom: 10px;
        }

        form {
            text-align: center;
            margin-top: 20px;
        }

        button[type=submit] {
            background-color: #5CACEE;
            color: #FFF;
            border: none;
            padding: 10px;
            border-radius: 5px;
            cursor: pointer;
            margin-top: 10px;
        }

        button[type=submit]:hover {
            background-color: #007BFF;
        }

        #timer {
            font-size: 2em;
            margin-bottom: 10px;
        }

        #chat-box {
            border: 1px solid #ccc;
            padding: 10px;
            width: 100%;
            height: 300px;
            overflow-y: scroll;
            margin-bottom: 10px;
        }
>>>>>>> Stashed changes
    </style>

</head>
<body>
<h2>Room</h2>
<div>User Points: <span id="user-points"></span></div>

<?php if ($role == 'admin' || $role == 'leader'): ?>
    <h3>Add Points to All Participants</h3>
    <form method="POST" action="Room.php?room_id=<?php echo $room_id; ?>">
        <label for="points_to_add">Points to Add:</label>
        <input type="number" id="points_to_add" name="points_to_add" required><br>
        <button type="submit" name="add_points">Add Points</button>
    </form>
<?php endif; ?>

<h3>Participants</h3>
<ul id="participants-list">
    <?php foreach ($participants as $participant): ?>
        <li>
            <?php echo htmlspecialchars($participant['username']) . ' (' . $participant['role'] . ')'; ?>
            <?php if ($role == 'admin' && $participant['username'] != $username): ?>
                <form method="POST" action="Room.php?room_id=<?php echo $room_id; ?>" style="display: inline;">
                    <input type="hidden" name="participant_id" value="<?php echo $participant['id']; ?>">
                    <input type="hidden" name="new_role"
                           value="<?php echo $participant['role'] == 'leader' ? 'participant' : 'leader'; ?>">
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
    <form id="timer-form">
        <label for="duration">Duration (seconds):</label>
        <input type="number" id="duration" name="duration" required>
        <button type="submit">Start Timer</button>
    </form>

    <h3>Room Configuration</h3>
    <form method="POST" action="Room.php?room_id=<?php echo $room_id; ?>">
        <label for="mode">Mode:</label>
        <select id="mode" name="mode" required>
            <option value="auto" <?php echo $room_config['mode'] == 'auto' ? 'selected' : ''; ?>>Auto</option>
            <option value="manual" <?php echo $room_config['mode'] == 'manual' ? 'selected' : ''; ?>>Manual</option>
        </select><br>
        <label for="sound_mode">Sound Mode:</label>
        <input type="text" id="sound_mode" name="sound_mode" value="<?php echo $room_config['sound_mode']; ?>" required><br>
        <label for="delay">Delay (seconds):</label>
        <input type="number" id="delay" name="delay" value="<?php echo $room_config['delay']; ?>" required><br>
        <label for="rows_count">Rows Count:</label>
        <input type="number" id="rows_count" name="rows_count" value="<?php echo $room_config['rows_count']; ?>"
               required><br>
        <label for="columns_count">Columns Count:</label>
        <input type="number" id="columns_count" name="columns_count"
               value="<?php echo $room_config['columns_count']; ?>" required><br>
        <label for="auto_sound">Auto Mode Sound:</label>
        <select id="auto_sound" name="auto_sound" required>
            <?php foreach ($sounds as $sound): ?>
                <option value="<?php echo $sound['id']; ?>" <?php echo $sound['id'] == $room_config['sound_id'] ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($sound['name']); ?>
                </option>
            <?php endforeach; ?>
        </select><br>
        <button type="submit" name="update_config">Update Configuration</button>
    </form>
<?php endif; ?>

<h3>Update Position</h3>
<form method="POST" action="Room.php?room_id=<?php echo $room_id; ?>">
    <label for="row_position">Row Position:</label>
    <input type="number" id="row_position" name="row_position" value="<?php echo $rowPosition; ?>" required><br>
    <label for="column_position">Column Position:</label>
    <input type="number" id="column_position" name="column_position" value="<?php echo $columnPosition; ?>"
           required><br>
    <button type="submit" name="update_position">Update Position</button>
</form>

<h3>Countdown Timer</h3>
<div id="timer">00:00</div>

<h3>Sounds</h3>
<div id="sounds">
    <?php foreach ($sounds as $sound): ?>
        <form id="<?php echo htmlspecialchars($sound['name']); ?>" method="POST"
              action="Room.php?room_id=<?php echo $room_id; ?>">
            <input type="hidden" value="<?php echo htmlspecialchars($sound['price']); ?>" name="sound_price"/>
            <button type="submit" class="sound-button" data-cost="<?php echo $sound['price']; ?>" disabled>
                <?php echo htmlspecialchars($sound['name']) . ' - ' . $sound['price'] . ' points'; ?>
            </button>
        </form>
    <?php endforeach; ?>
</div>

<form method="POST" action="Room.php?room_id=<?php echo $room_id; ?>">
    <input type="hidden" name="leave_room" value="1">
    <button type="submit">Leave Room</button>
</form>
</body>
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
    let userPoints = <?php echo $userPoints['points']; ?>;
    let autoSound = '<?php echo $auto_sound; ?>';
    let rowPosition = <?php echo $rowPosition; ?>;
    let columnPosition = <?php echo $columnPosition; ?>;
    let timerExpired = false; // Track timer state

    function connect() {
        conn = new WebSocket('ws://localhost:8000');
        conn.onopen = function (e) {
            conn.send(JSON.stringify({type: 'join', room_id: roomId, user_id: userId}));
            conn.send(JSON.stringify({type: 'get_timer_state', room_id: roomId, user_id: userId}));
            updatePointsDisplay();
        };

        conn.onmessage = function (e) {
            let data = JSON.parse(e.data);
            console.log(data);

            if (data.type === 'start_timer') {
                timerExpired = false;
                startCountdown(data.duration, data.start_time);
                disableSounds();
            } else if (data.type === 'timer_state') {
                disableSounds();
                startCountdown(data.duration, data.start_time);
            } else if (data.type === 'timer_expired') {
                timerExpired = true;
                if (mode === 'manual') {
                    enableSounds();
                }
            } else if (data.type === 'play_sound') {
                console.log(data.sound);
                playSound(data.sound);
            } else if (data.type === 'refresh_page') {
                location.reload();
            }
        };

        conn.onclose = function (e) {
            console.log("Connection closed!");
        };
    }

    function updatePointsDisplay() {
        document.getElementById('user-points').innerText = userPoints;
    }

    function startCountdown(duration, startTime) {
        let timer = duration - (Math.floor(Date.now() / 1000) - startTime);
        let display = document.getElementById('timer');

        let countdown = setInterval(function () {
            if (timer <= 0) {
                clearInterval(countdown);
                timerExpired = true;
                display.textContent = "Time's up!";
                if (mode === 'auto') {
                    triggerSoundWave();
                } else {
                    enableSounds();
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

    function playSound(sound) {
        let audio = new Audio(sound);
        audio.play();
        disableSounds();
    }

    function playSoundFromButton(form, sound, price) {
        let audio = new Audio(sound);
        audio.play();
        disableSounds();
        userPoints -= price;
        updatePointsDisplay();
        audio.onended = function() {
            form.submit()
        };
    }

    function refreshUsersPages() {
        conn.send(JSON.stringify({type: 'refresh_pages'}));
    }

    function triggerSoundWave() {
        let rowDelay = delay * 1000;
        let colDelay = delay * 1000;

        function triggerRow() {
            setTimeout(() => {
                playSound(autoSound)
            }, rowDelay * rowPosition);
        }

        function triggerCol() {
            console.log(columnPosition);
            console.log(rowPosition);
            setTimeout(() => {
                playSound(autoSound)
            }, colDelay * columnPosition);
        }

        if (soundMode === 'rows') {
            triggerRow();
        } else {
            triggerCol();
        }
    }

    function enableSounds() {
        let soundButtons = document.querySelectorAll('.sound-button');
        soundButtons.forEach(button => {
            let soundCost = parseInt(button.getAttribute('data-cost'));
            if (timerExpired && userPoints >= soundCost) {
                button.disabled = false;
            } else {
                button.disabled = true;
            }
        });
    }

    function disableSounds() {
        let soundButtons = document.querySelectorAll('.sound-button');
        soundButtons.forEach(button => {
            button.disabled = true;
        });
    }

    document.addEventListener("DOMContentLoaded", function () {
        connect();

        document.getElementById('timer-form').onsubmit = function (event) {
            event.preventDefault();
            let duration = parseInt(document.getElementById('duration').value, 10);
            conn.send(JSON.stringify({
                type: 'start_timer',
                room_id: roomId,
                duration: duration,
                start_time: Math.floor(Date.now() / 1000)
            }));
            startCountdown(duration, Math.floor(Date.now() / 1000));
        };
    });

    <?php foreach ($sounds as $sound): ?>
    var form = document.getElementById('<?php echo htmlspecialchars($sound['name']); ?>');
    form.addEventListener("submit", event => {
        event.preventDefault();
        playSoundFromButton(form, '<?php echo htmlspecialchars($sound['file_path']); ?>', <?php echo htmlspecialchars($sound['price']); ?>)
    })
    <?php endforeach; ?>
</script>
</html>