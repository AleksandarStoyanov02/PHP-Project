<?php
session_start();
include '../db.php';
global $conn;
error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SESSION['role'] != 'admin') {
    header('Location: dashboard.php');
    exit();
}

$users = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['search'])) {
        $searchTerm = '%' . $_POST['search'] . '%';
        $stmt = $conn->prepare("SELECT id, username, role FROM users WHERE username LIKE ?");
        $stmt->bind_param("s", $searchTerm);
        $stmt->execute();
        $result = $stmt->get_result();
        $users = $result->fetch_all();
    } elseif (isset($_POST['user_id']) && isset($_POST['role'])) {
        $userId = $_POST['user_id'];
        $newRole = $_POST['role'];
        $stmt = $conn->prepare("UPDATE users SET role = ? WHERE id = ?");
        $stmt->bind_param("si", $newRole, $userId);
        $stmt->execute();
        header('Location: RoleManagement.php'); // Refresh to see updated roles
    }
} else {
    $stmt = $conn->query("SELECT id, username, role FROM users");
    $users = $stmt->fetch_all();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage User Roles</title>
</head>
<body>
<h2>Manage User Roles</h2>

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
    </tr>
    <?php foreach ($users as $user): ?>
        <tr>
            <td><?php echo htmlspecialchars($user[1]); ?></td>
            <td><?php echo htmlspecialchars($user[2]); ?></td>
            <td>
                <form method="POST" action="RoleManagement.php">
                    <input type="hidden" name="user_id" value="<?php echo $user[0]; ?>">
                    <select name="role">
                        <option value="participant" <?php if ($user[2] == 'participant') echo 'selected'; ?>>Participant</option>
                        <option value="admin" <?php if ($user[2] == 'admin') echo 'selected'; ?>>Admin</option>
                    </select>
                    <button type="submit">Change Role</button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
</table>
</body>
</html>