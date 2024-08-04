<?php
session_start();
require_once 'config.php';

// エラーログの設定
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// ログファイルへのエラーログ出力を設定
ini_set('log_errors', 1);
ini_set('error_log', '/var/www/html/error.log');

// ログインしているか確認
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$conn = get_db_connection();

// ユーザーの現在のコンテナ数を取得
$user_id = $_SESSION['user_id'];
$sql = "SELECT COUNT(*) AS container_count FROM Containers WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($container_count);
$stmt->fetch();
$stmt->close();

// コンテナ作成処理
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'];
    $cpu = (int)$_POST['cpu'];
    $memory = (int)$_POST['memory'];
    $disk = (int)$_POST['disk'];

    // リソースとコンテナ数の制限を確認
    if ($container_count >= MAX_CONTAINERS) {
        $error = "You have reached the maximum number of containers.";
    } elseif ($cpu > MAX_CPU || $memory > MAX_MEMORY || $disk > MAX_DISK) {
        $error = "Resource limits exceeded.";
    } else {

        // Proxmox APIに接続してコンテナを作成
        $vmid = 1000 + $container_count; // VMIDの生成方法を適宜調整
        $url = PROXMOX_HOST . "/api2/json/nodes/" . PROXMOX_NODE . "/lxc";
        $params = array(
            'vmid' => $vmid,
            'hostname' => $name,
            'ostemplate' => 'local:vztmpl/ubuntu-23.04-standard_23.04-1_amd64.tar.zst', // テンプレートのパス
            'password' => 'password', // デフォルトのrootパスワード
            'memory' => $memory,
            'swap' => $memory,
            'cores' => $cpu,
            'rootfs' => 'local-lvm:' . $disk . 'G',
            'net0' => 'name=eth0,bridge=vmbr0,firewall=1,ip=dhcp'
        );

        $headers = array(
            "Authorization: PVEAPIToken=" . PROXMOX_APIID . "=" . PROXMOX_APIKEY,
            "Content-Type: application/x-www-form-urlencoded"
        );

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // 証明書の検証を無効化
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); // ホスト名の検証を無効化

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if (curl_errno($ch)) {
            $error_msg = curl_error($ch);
            error_log("cURL error: $error_msg");
        }
        curl_close($ch);

        error_log("HTTP Code: " . $http_code);
        error_log("Response: " . $response);

        if ($http_code == 200) {
            // データベースにコンテナ情報を保存
            $sql = "INSERT INTO Containers (user_id, vmid, name, status, cpu, memory, disk) VALUES (?, ?, ?, 'stopped', ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iisiis", $user_id, $vmid, $name, $cpu, $memory, $disk);

            if ($stmt->execute()) {
                $success = "Container created successfully.";
            } else {
                $error = "Error: " . $stmt->error;
                error_log("Database error: " . $stmt->error);
            }

            $stmt->close();
        } else {
            $error = "Failed to create container: " . $response;
            error_log("Proxmox API error: HTTP code $http_code, response: $response");
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Container</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container">
        <h2>Create Container</h2>
        <?php if (isset($error)): ?>
            <p class="error"><?php echo $error; ?></p>
        <?php elseif (isset($success)): ?>
            <p class="success"><?php echo $success; ?></p>
        <?php endif; ?>
        <form action="make-ct.php" method="POST">
            <label for="name">Container Name:</label>
            <input type="text" id="name" name="name" required>
            <label for="cpu">CPU:</label>
            <input type="number" id="cpu" name="cpu" required>
            <label for="memory">Memory (MB):</label>
            <input type="number" id="memory" name="memory" required>
            <label for="disk">Disk (GB):</label>
            <input type="number" id="disk" name="disk" required>
            <button type="submit">Create</button>
        </form>
        <p><a href="dashboard.php">Back to Dashboard</a></p>
    </div>
</body>
</html>
