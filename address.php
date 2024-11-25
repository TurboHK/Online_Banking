<?php
session_start();
include 'db_connection.php';

if (!isset($_SESSION['username'])) {
    header("Location: index.html"); // 如果没有登录则跳转到登录页面
    exit();
}

$current_username = $_SESSION['username'];

// 查询当前地址
$stmt = $conn->prepare("SELECT address FROM users WHERE username=?");
$stmt->bind_param("s", $current_username);
$stmt->execute();
$result = $stmt->get_result();

$user_data = $result->fetch_assoc();
$current_address = $user_data['address'] ?? 'Not available'; // 如果没有地址，则显示默认信息

$stmt->close();

// 处理更改地址请求
if (isset($_POST['update_address'])) {
    $new_address = $_POST['new_address'];  // 新地址

    // 更新数据库中的地址
    $stmt = $conn->prepare("UPDATE users SET address=? WHERE username=?");
    $stmt->bind_param("ss", $new_address, $current_username);

    if ($stmt->execute()) {
        echo "<script>alert('Address updated successfully.'); window.location.href='profile.php';</script>";
    } else {
        echo "<script>alert('An error occurred while updating the address.');</script>";
    }
    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="./css/dashboard.css" />
    <link rel="icon" href="assets/logo.png" type="image/png">
    <title>Update Address | GBC Internet Banking</title>
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

        .current-address {
            font-size: 16px;
            color: #333;
            margin-bottom: 20px;
            padding: 10px;
            background-color: #f7f7f7;
            border-radius: 5px;
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
            <h2>Update Your Address</h2>

            <!-- 显示当前地址 -->
            <div class="current-address">
                <strong>Current Address:</strong><br> <?php echo htmlspecialchars($current_address); ?>
            </div>

            <!-- 修改地址表单 -->
            <form method="post" action="">
                <div class="form-group">
                    <label for="new_address">New Address:</label>
                    <input
                        autocomplete="off"
                        type="text"
                        placeholder="Enter your new address"
                        name="new_address"
                        class="field__input"
                        required
                    />
                </div>

                <div class="form-group">
                    <input type="submit" name="update_address" value="Update Address">
                </div>
            </form>

            <div class="back-link">
                <p><a href="javascript:history.back()">Return to the previous page</a></p>
            </div>
        </div>
    </main>

    <footer class="footer">
        <span class="author">©2024 Global Banking Corporation Limited. All rights reserved.</span>
    </footer>
</body>
</html>