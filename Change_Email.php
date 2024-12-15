<?php
session_start();
include 'db_user_connection.php';

if (!isset($_SESSION['username'])) {
    header("Location: index.html"); // 如果没有登录则跳转到登录页面
    exit();
}

//Start timing
$start_time = microtime(true);

// 处理更改邮箱请求
if (isset($_POST['update_email'])) {
    $current_email = $_POST['current_email'];  // 当前的用户名（邮箱）
    $new_email = $_POST['new_email'];  // 新的邮箱（用户名）
    $current_username = $_SESSION['username']; // 当前登录的用户名

    // 验证原有邮箱（即原用户名）是否正确
    $stmt = $conn->prepare("SELECT username FROM users WHERE username=?");
    $stmt->bind_param("s", $current_username);
    $stmt->execute();
    $result = $stmt->get_result();
    $user_data = $result->fetch_assoc();

    // 如果用户输入的原有邮箱（用户名）与数据库中的用户名匹配，则允许更新
    if ($user_data && $user_data['username'] === $current_email) {
        // 更新数据库中的邮箱（即用户名）
        $stmt = $conn->prepare("UPDATE users SET username=? WHERE username=?");
        $stmt->bind_param("ss", $new_email, $current_username);

        if ($stmt->execute()) {
            // 更新成功后销毁会话并跳转到登录页面
            session_destroy(); // 销毁当前会话
            echo "<script>alert('Email (Username) updated successfully. You have been logged out. Please log in again.'); window.location.href='index.html';</script>";
            exit();  // 确保跳转后终止脚本执行
        } else {
            echo "<script>alert('An error occurred while updating the email (username).');</script>";
        }
        $stmt->close();
    } else {
        echo "<script>alert('The current email (username) does not match our records.');</script>";
    }
}

//End timing
$end_time = microtime(true);
$execution_time = round(($end_time - $start_time) * 1000, 2); //Convert to milliseconds
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="./css/dashboard.css" />
    <link rel="icon" href="assets/logo.png" type="image/png">
    <title>Update Email | GBC Internet Banking</title>
    <style>
        .form-container {
            max-width: 600px;
            margin: 50px auto;
            padding: 20px;
            background-color: #fff;
            box-shadow: 0 1px 6px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
        }

        .form-container h2 {
            text-align: center;
            font-size: 24px;
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            font-size: 16px;
            display: block;
            margin-bottom: 8px;
        }

        .form-group input {
            width: 100%;
            padding: 10px;
            font-size: 16px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }

        .form-group input[type="submit"] {
            background-color: #4ba247;
            color: white;
            border: none;
            cursor: pointer;
            font-weight: bold;
        }

        .form-group input[type="submit"]:hover {
            background-color: #45a047;
        }

        .back-link {
            text-align: center;
            margin-top: 20px;
        }

        .back-link a {
            text-decoration: none;
            color: #0064d2;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="header__content">
            <div class="header__logo">
                <a href="./dashboard.php"><img src="./assets/logo.png" alt="Bank Logo"></a>
            </div>
            <h1>Welcome to GBC Internet Banking</h1>
            <div class="header__right">
                Current User: <?php echo htmlspecialchars($_SESSION['username']); ?>
                <button class="logout-button" style="margin-left: 10px;" onclick="window.location.href='logout.php'">Logout</button>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="dashboard">
        <div class="form-container">
            <h2>Update Your Email Address</h2>
            <form method="post" action="">
                <div class="form-group">
                    <label for="current_email">Current Email (Username):</label>
                    <input
                        autocomplete="off"
                        type="email"
                        placeholder="Current Email (Username)"
                        name="current_email"
                        class="field__input"
                        required
                    />
                </div>

                <div class="form-group">
                    <label for="new_email">New Email (Username):</label>
                    <input
                        autocomplete="off"
                        type="email"
                        placeholder="New Email (Username)"
                        name="new_email"
                        class="field__input"
                        required
                    />
                </div>

                <div class="form-group">
                    <input type="submit" name="update_email" value="Update Email">
                </div>
            </form>

            <div class="back-link">
                <p><a href="javascript:history.back()">Return to the previous page</a></p>
            </div>
        </div>
    </main>

    <footer class="footer">
        <?php if ($execution_time): ?>
            It took <?php echo $execution_time; ?> milliseconds to get data from the server.</p>
        <?php endif; ?>
        ©2024 Global Banking Corporation Limited. All rights reserved.
    </footer>
</body>
</html>
