<?php
session_start();
require_once 'config.php';

// ログインしているか確認
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$conn = get_db_connection();

if (isset($_GET['id'])) {
    $container_id = (int)$_GET['id'];

    // コンテナ情報を取得
    $sql = "SELECT vmid FROM Containers WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $container_id, $_SESSION['user_id']);
    $stmt->execute();
    $stmt->bind_result($vmid);
    if ($stmt->fetch()) {
        $stmt->close();

        // コンテナを起動
        $url = PROXMOX_HOST . "/api2/json/nodes/" . PROXMOX_NODE . "/lxc/$vmid/status/start";
        $headers = array(
            "Authorization: PVEAPIToken=" . PROXMOX_APIID . "=" . PROXMOX_APIKEY
        );

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_code == 200) {
            // コンテナのステータスを更新
            $sql = "UPDATE Containers SET status = 'running' WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $container_id);
            $stmt->execute();
            $stmt->close();
        }
    } else {
        $stmt->close();
    }
}

$conn->close();
header("Location: dashboard.php");
exit();
?>
