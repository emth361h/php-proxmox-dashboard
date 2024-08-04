<?php
session_start();
require_once 'config.php';

// ログインしているか確認
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$conn = get_db_connection();
$user_id = $_SESSION['user_id'];

// ユーザーのコンテナ情報を取得
$sql = "SELECT id, name, status, cpu, memory, disk, created_at FROM Containers WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$containers = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$conn->close();
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="dashboard">
        <h2>Dashboard</h2>
        <h3>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></h3>
        <p><a href="make-ct.php">Create New Container</a></p>
        <h3>Your Containers</h3>
        <?php if (count($containers) > 0): ?>
            <table>
                <tr>
                    <th>Name</th>
                    <th>Status</th>
                    <th>CPU</th>
                    <th>Memory (MB)</th>
                    <th>Disk (GB)</th>
                    <th>Created At</th>
                    <th>Actions</th>
                </tr>
                <?php foreach ($containers as $container): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($container['name']); ?></td>
                        <td><?php echo htmlspecialchars($container['status']); ?></td>
                        <td><?php echo htmlspecialchars($container['cpu']); ?></td>
                        <td><?php echo htmlspecialchars($container['memory']); ?></td>
                        <td><?php echo htmlspecialchars($container['disk']); ?></td>
                        <td><?php echo htmlspecialchars($container['created_at']); ?></td>
                        <td>
                            <!-- コンテナ操作のリンクを追加 -->
                            <a href="start-ct.php?id=<?php echo $container['id']; ?>">Start</a> |
                            <a href="stop-ct.php?id=<?php echo $container['id']; ?>">Stop</a> |
                            <a href="delete-ct.php?id=<?php echo $container['id']; ?>">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
        <?php else: ?>
            <p>You have no containers.</p>
        <?php endif; ?>
        <p><a href="logout.php">Logout</a></p>
    </div>
</body>
</html>
