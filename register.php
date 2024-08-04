<?php
session_start();

require_once 'config.php';

$conn = get_db_connection(); // データベース接続を取得

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// ユーザー登録処理
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $input_username = $_POST['username'];
    $input_password = $_POST['password'];
    $input_email = $_POST['email'];

    // ユーザー名とメールの重複チェック
    $sql = "SELECT id FROM Users WHERE username = ? OR email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $input_username, $input_email);
    $stmt->execute();
    $stmt->store_result();
    
    if ($stmt->num_rows > 0) {
        $error = "Username or Email already exists.";
    } else {
        // パスワードのハッシュ化
        $hashed_password = password_hash($input_password, PASSWORD_DEFAULT);

        // 新規ユーザー
        $sql = "INSERT INTO Users (username, password, email) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sss", $input_username, $hashed_password, $input_email);

        if ($stmt->execute()) {
            $success = "Registration successful. You can now <a href='index.php'>login</a>.";
        } else {
            $error = "Error: " . $stmt->error;
        }
    }

    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="register-container">
        <h2>Register</h2>
        <?php if (isset($error)): ?>
            <p class="error"><?php echo $error; ?></p>
        <?php elseif (isset($success)): ?>
            <p class="success"><?php echo $success; ?></p>
        <?php endif; ?>
        <form action="register.php" method="POST">
            <label for="username">Username:</label>
            <input type="text" id="username" name="username" required>
            <label for="password">Password:</label>
            <input type="password" id="password" name="password" required>
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" required>
            <button type="submit">Register</button>
        </form>
        <p>Already have an account? <a href="index.php">Login here</a>.</p>
    </div>
</body>
</html>
