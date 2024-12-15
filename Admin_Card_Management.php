<?php
session_start();
include 'db_connection.php';

// 检查用户是否为管理员
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: admin_login.html"); // 如果未登录或非管理员，重定向到登录页面
    exit();
}

$search_result = '';
$execution_time = ''; // 用于存储执行时间
$cards = []; // 用于存储搜索到的卡片信息
$user_id = ''; // 初始化用户ID为空

// 处理搜索用户ID
if (isset($_POST['search_user'])) {
    $user_id = $_POST['user_id'];  // 获取用户输入的用户ID

    // 开始计时
    $start_time = microtime(true);

    // 查询该用户ID下的所有卡片信息
    $stmt = $conn->prepare("SELECT * FROM cards WHERE cardholder_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    // 如果查询到卡片信息，存入数组
    if ($result->num_rows > 0) {
        $cards = [];
        while ($row = $result->fetch_assoc()) {
            $cards[] = $row;  // 将卡片信息存入数组
        }
        $search_result = "<table border='1'><tr><th>Card Type</th><th>Card Number</th><th>Status</th><th>Actions</th></tr>";
        foreach ($cards as $card) {
            $search_result .= "<tr>
                                    <td>" . htmlspecialchars($card['type']) . "</td>
                                    <td>" . htmlspecialchars($card['card_number']) . "</td> <!-- 直接显示卡号 -->
                                    <td>" . ($card['blocked'] == 0 ? 'Active' : 'Blocked') . "</td>
                                    <td>
                                        " . ($card['blocked'] == 0 ? 
                                            "<button onclick=\"location.href='block_card.php?card_id=" . $card['id'] . "'\">Block Card</button>" : 
                                            "<button onclick=\"location.href='unblock_card.php?card_id=" . $card['id'] . "'\">Unblock Card</button>") . "
                                    </td>
                                  </tr>";
        }
        $search_result .= "</table>";
    } else {
        $search_result = "No cards found for this user.";
    }

    $stmt->close();

    // 结束计时
    $end_time = microtime(true);
    $execution_time = round(($end_time - $start_time) * 1000, 2); // 转换为毫秒
}

// 处理清空搜索结果
if (isset($_POST['clear'])) {
    $search_result = '';  // 清空搜索结果
    $execution_time = '';  // 清空执行时间
    $user_id = ''; // 清空用户ID
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="./css/dashboard.css" />
    <link rel="icon" href="assets/logo.png" type="image/png">
    <title>Admin Panel | GBC Internet Banking</title>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="header__content">
            <div class="header__logo">
                <a href="./admin_dashboard.php"><img src="./assets/logo.png" alt="Bank Logo"></a>
            </div>
            <h1>Welcome to GBC Internet Banking Control Panel</h1>
            <div class="header__right">
                Current Administrator: <?php echo htmlspecialchars($_SESSION['username']); ?>
                <button class="logout-button" style="margin-left: 10px;" onclick="window.location.href='logout.php'">Logout</button>
            </div>
        </div>
    </header>

    <!-- Main Dashboard Content -->
    <main class="dashboard">
        <!-- 搜索用户ID功能 -->
        <section class="user-search">
            <h2>Search User's Cards</h2>
            <form method="post" action="">
                <label for="user_id">User ID:</label>
                <input type="text" name="user_id" id="user_id" value="<?php echo htmlspecialchars($user_id); ?>" required>
                <input type="submit" name="search_user" value="Search">
                <input type="submit" name="clear" value="Clear"> <!-- 清空搜索结果按钮 -->
            </form>

            <!-- 显示搜索结果 -->
            <div>
                <?php if ($execution_time): ?>
                    <p>Search executed in <span><?php echo $execution_time; ?></span> milliseconds.</p><br>
                <?php endif; ?>
                <?php
                // 输出搜索结果
                if ($search_result) {
                    echo $search_result;
                }
                ?>
            </div>
        </section>

        <!-- 返回到管理面板 -->
        <div class="back-link">
                <p><a href="javascript:history.back()">Return to the previous page</a></p>
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

<style>
        .back-link {
        text-align: center;
        margin-top: 20px;
    }
    .back-link a {
        text-decoration: none;
        color: #0064d2;
    }
</style>