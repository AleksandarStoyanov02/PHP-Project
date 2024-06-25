<?php
session_start();
include '../db.php';
global $conn;
error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SESSION['role'] != 'admin') {
  //  header('Location: dashboard.php');
    //exit();
}

$users = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['search'])) {
        $searchTerm = '%' . $_POST['search'] . '%';
        $stmt = $conn->prepare("SELECT id, username, role, points FROM users WHERE username LIKE ?");
        $stmt->bind_param("s", $searchTerm);
        $stmt->execute();
        $result = $stmt->get_result();
        $users = $result->fetch_all(MYSQLI_ASSOC);
    } elseif (isset($_POST['user_id']) && isset($_POST['role'])) {
        $userId = $_POST['user_id'];
        $newRole = $_POST['role'];
        $stmt = $conn->prepare("UPDATE users SET role = ? WHERE id = ?");
        $stmt->bind_param("si", $newRole, $userId);
        $stmt->execute();
        header('Location: RoleManagement.php'); // Refresh to see updated roles
    } elseif (isset($_POST['user_id']) && isset($_POST['points'])) {
        $userId = $_POST['user_id'];
        $points = $_POST['points'];
        $stmt = $conn->prepare("UPDATE users SET points = points + ? WHERE id = ?");
        $stmt->bind_param("ii", $points, $userId);
        $stmt->execute();
        header('Location: RoleManagement.php'); // Refresh to see updated points
    }
} else {
    $stmt = $conn->query("SELECT id, username, role, points FROM users");
    $users = $stmt->fetch_all(MYSQLI_ASSOC);
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage User Roles and Points</title>
    <link rel="stylesheet" type="text/css" href="RoleManagement.css">
</head>
<body>
<h2>Manage User Roles and Points</h2>

<form method="POST" action="RoleManagement.php">
    <label for="search">Search Username:</label>
    <input type="text" id="search" name="search" placeholder="Enter username">
    <button type="submit">Search</button>
</form>

<table border="1">
    <tr>
        <th>Username</th>
        <th>Role</th>
        <th>Change Role</th>
        <th>Points</th>
        <th>Add Points</th>
    </tr>
    <?php foreach ($users as $user): ?>
        <tr>
            <td><?php echo htmlspecialchars($user['username']); ?></td>
            <td><?php echo htmlspecialchars($user['role']); ?></td>
            <td>
                <form method="POST" action="RoleManagement.php">
                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                    <select name="role">
                        <option value="participant" <?php if ($user['role'] == 'participant') echo 'selected'; ?>>Participant</option>
                        <option value="admin" <?php if ($user['role'] == 'admin') echo 'selected'; ?>>Admin</option>
                    </select>
                    <button type="submit">Change Role</button>
                </form>
            </td>
            <td><?php echo htmlspecialchars($user['points']); ?></td>
            <td>
                <form method="POST" action="RoleManagement.php">
                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                    <input type="number" name="points" required>
                    <button type="submit">Add Points</button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
</table>
</body>
</html>