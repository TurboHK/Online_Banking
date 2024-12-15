<?php
session_start();
include 'db_user_connection.php';

if (!isset($_SESSION['username'])) {
    header("Location: index.html"); // 如果没有登录则跳转到登录页面
    exit();
}

//Start timing
$start_time = microtime(true);

// 处理更改密码请求
if (isset($_POST['update_password'])) {
    $current_password = $_POST['current_password'];  // 当前密码
    $new_password = $_POST['new_password'];  // 新密码
    $confirm_password = $_POST['confirm_password'];  // 确认新密码
    $current_username = $_SESSION['username']; // 当前登录的用户名

    // 验证原有密码是否正确
    $stmt = $conn->prepare("SELECT password FROM users WHERE username=?");
    $stmt->bind_param("s", $current_username);
    $stmt->execute();
    $result = $stmt->get_result();
    $user_data = $result->fetch_assoc();

    // 检查当前密码是否正确
    if ($user_data && password_verify($current_password, $user_data['password'])) {
        // 检查新密码和确认密码是否匹配
        if ($new_password === $confirm_password) {
            // 使用哈希存储新密码
            $new_password_hashed = password_hash($new_password, PASSWORD_DEFAULT);

            // 更新数据库中的密码
            $stmt = $conn->prepare("UPDATE users SET password=? WHERE username=?");
            $stmt->bind_param("ss", $new_password_hashed, $current_username);

            if ($stmt->execute()) {
                // 更新成功后弹出修改成功的提示框并跳转
                echo "<script>alert('Password updated successfully.'); window.location.href='dashboard.php';</script>";
                exit();
            } else {
                echo "<script>alert('An error occurred while updating the password.');</script>";
            }
            $stmt->close();
        } else {
            echo "<script>alert('The new password and confirmation do not match.');</script>";
        }
    } else {
        echo "<script>alert('The current password is incorrect.');</script>";
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
    <title>Change Password | GBC Internet Banking</title>
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
            <h1>Change Password</h1>
            <div class="header__right">
                Current User: <?php echo htmlspecialchars($_SESSION['username']); ?>
                <button class="logout-button" style="margin-left: 10px;" onclick="window.location.href='logout.php'">Logout</button>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="dashboard">
        <div class="form-container">
            <h2>Change Your Password</h2>
            <form method="post" action="">
                <div class="form-group">
                    <label for="current_password">Current Password:</label>
                    <input
                        autocomplete="off"
                        type="password"
                        placeholder="Current Password"
                        name="current_password"
                        class="field__input"
                        required
                    />
                </div>

                <div class="form-group">
                    <label for="new_password">New Password:</label>
                    <input
                        autocomplete="off"
                        type="password"
                        placeholder="New Password"
                        name="new_password"
                        class="field__input"
                        required
                    />
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirm New Password:</label>
                    <input
                        autocomplete="off"
                        type="password"
                        placeholder="Confirm New Password"
                        name="confirm_password"
                        class="field__input"
                        required
                    />
                </div>

                <div class="form-group">
                    <input type="submit" name="update_password" value="Update Password">
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
