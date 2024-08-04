<?php
// データベース接続情報
define('DB_SERVER', 'strings');
define('DB_USERNAME', 'strings');
define('DB_PASSWORD', 'strings');
define('DB_NAME', 'strings');

// データベース接続の設定
function get_db_connection() {
    $conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    return $conn;
}

// ユーザーのリソース制限
define('MAX_CONTAINERS', 10); // ユーザーが作成できる最大コンテナ数
define('MAX_CPU', 1); // ユーザーが利用できる最大CPU数
define('MAX_MEMORY', 1024); // ユーザーが利用できる最大メモリ（MB単位）
define('MAX_DISK', 128); // ユーザーが利用できる最大ディスク容量（GB単位）

// Proxmox接続情報
define('PROXMOX_HOST', 'https://proxmox.host:8006');
define('PROXMOX_APIID', 'user@pve!strings'); // Proxmox API ID'user@pve!id'
define('PROXMOX_APIKEY', 'apikey'); // Proxmox API Key
define('PROXMOX_NODE', 'vm'); // Proxmoxノード名

?>
