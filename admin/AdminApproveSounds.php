<?php
session_start();
include '../db.php';
global $conn;
error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SESSION['role'] != 'admin') {
   // header('Location: ../Dashboard/dashboard.php');
 //   exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['approve'])) {
        $sound_id = $_POST['sound_id'];
        $stmt = $conn->prepare("SELECT * FROM sound_requests WHERE id = ?");
        $stmt->bind_param("i", $sound_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $sound_request = $result->fetch_assoc(); // Fetch single row as associative array

        $stmt = $conn->prepare("INSERT INTO sound (name, file_path, approved) VALUES (?, ?, 1)");
        $stmt->bind_param("ss", $sound_request['sound_name'], $sound_request['file_path']);
        $stmt->execute();

        $stmt = $conn->prepare("DELETE FROM sound_requests WHERE id = ?");
        $stmt->bind_param("i", $sound_id);
        $stmt->execute();

    } elseif (isset($_POST['reject'])) {
        $sound_id = $_POST['sound_id'];
        $stmt = $conn->prepare("SELECT file_path FROM sound_requests WHERE id = ?");
        $stmt->bind_param("i", $sound_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $sound_request = $result->fetch_assoc(); // Fetch single row as associative array

        unlink($sound_request['file_path']);

        $stmt = $conn->prepare("DELETE FROM sound_requests WHERE id = ?");
        $stmt->bind_param("i", $sound_id);
        $stmt->execute();
    }
}

$stmt = $conn->prepare("SELECT sound_requests.id, sound_requests.file_path, sound_requests.sound_name, users.username FROM sound_requests JOIN users ON sound_requests.user_id = users.id");
$stmt->execute();
$result = $stmt->get_result();
$sound_requests = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin - Sound Requests</title>
    <link rel="stylesheet" type="text/css" href="adminApprove.css">
</head>
<body>
<h2>Pending Sound Requests</h2>
<table border="1">
    <tr>
        <th>Username</th>
        <th>Sound Name</th>
        <th>Sound File</th>
        <th>Actions</th>
    </tr>
    <?php foreach ($sound_requests as $request): ?>
        <tr>
            <td><?php echo htmlspecialchars($request['username']); ?></td>
            <td><?php echo htmlspecialchars($request['sound_name']); ?></td>
            <td>
                <button onclick="playSound('<?php echo htmlspecialchars($request['file_path']); ?>')">Play</button>
            </td>
            <td>
                <form action="AdminApproveSounds.php" method="POST" style="display:inline;">
                    <input type="hidden" name="sound_id" value="<?php echo $request['id']; ?>">
                    <button type="submit" name="approve">Approve</button>
                </form>
                <form action="AdminApproveSounds.php" method="POST" style="display:inline;">
                    <input type="hidden" name="sound_id" value="<?php echo $request['id']; ?>">
                    <button type="submit" name="reject">Reject</button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
</table>

<script>
    function playSound(filePath) {
        var audio = new Audio(filePath);
        audio.play();
    }
</script>
</body>
</html>