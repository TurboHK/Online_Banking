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
$applycredits = []; // 用于存储查询到的申请信息
$user_id = ''; // 初始化用户ID为空

// 查询所有记录或根据用户ID查询
if (isset($_POST['search_user']) && !empty($_POST['user_id'])) {
    $user_id = $_POST['user_id'];  // 获取用户输入的用户ID

    // 开始计时
    $start_time = microtime(true);

    // 查询该用户ID下的所有信用卡申请信息
    $stmt = $conn->prepare("SELECT * FROM applycredit WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    // 如果查询到申请信息，存入数组
    if ($result->num_rows > 0) {
        $applycredits = [];
        while ($row = $result->fetch_assoc()) {
            $applycredits[] = $row;  // 将申请信息存入数组
        }
        $search_result = "<table class='result-table'><tr><th>Apply ID</th><th>User ID</th><th>Name</th><th>Phone</th><th>Address</th><th>Status</th><th>Actions</th></tr>";
        foreach ($applycredits as $applycredit) {
            // 如果status为waiting，显示reject和approve按钮
            if ($applycredit['status'] == 'waiting') {
                $actions = "<form method='POST' id='form_" . $applycredit['apply_id'] . "'>
                    <button type='button' onclick='confirmAction(\"reject\", " . $applycredit['apply_id'] . ")'>Reject</button>
                    <button type='button' onclick='confirmAction(\"approve\", " . $applycredit['apply_id'] . ")'>Approve</button>
                </form>";
            } else {
                $actions = "<span>Complete</span>";
            }
            
            $search_result .= "<tr>
                <td>" . htmlspecialchars($applycredit['apply_id']) . "</td>
                <td>" . htmlspecialchars($applycredit['user_id']) . "</td>
                <td>" . htmlspecialchars($applycredit['name']) . "</td>
                <td>" . htmlspecialchars($applycredit['phone']) . "</td>
                <td>" . htmlspecialchars($applycredit['address']) . "</td>
                <td>" . htmlspecialchars($applycredit['status']) . "</td>
                <td>" . $actions . "</td>
            </tr>";
        }
        $search_result .= "</table>";
    } else {
        $search_result = "No applycredit records found for this user.";
    }

    $stmt->close();

    // 结束计时
    $end_time = microtime(true);
    $execution_time = round(($end_time - $start_time) * 1000, 2); // 转换为毫秒
} else {
    // 如果没有搜索条件，查询所有申请记录
    $start_time = microtime(true);

    // 查询所有applycredit记录
    $stmt = $conn->prepare("SELECT * FROM applycredit");
    $stmt->execute();
    $result = $stmt->get_result();

    // 如果查询到申请信息，存入数组
    if ($result->num_rows > 0) {
        $applycredits = [];
        while ($row = $result->fetch_assoc()) {
            $applycredits[] = $row;  // 将申请信息存入数组
        }
        $search_result = "<table class='result-table'><tr><th>Apply ID</th><th>User ID</th><th>Name</th><th>Phone</th><th>Address</th><th>Status</th><th>Actions</th></tr>";
        foreach ($applycredits as $applycredit) {
            // 如果status为waiting，显示reject和approve按钮
            if ($applycredit['status'] == 'waiting') {
                $actions = "<form method='POST' id='form_" . $applycredit['apply_id'] . "'>
                    <button type='button' onclick='confirmAction(\"reject\", " . $applycredit['apply_id'] . ")'>Reject</button>
                    <button type='button' onclick='confirmAction(\"approve\", " . $applycredit['apply_id'] . ")'>Approve</button>
                </form>";
            } else {
                $actions = "<span>Complete</span>";
            }
            
            $search_result .= "<tr>
                <td>" . htmlspecialchars($applycredit['apply_id']) . "</td>
                <td>" . htmlspecialchars($applycredit['user_id']) . "</td>
                <td>" . htmlspecialchars($applycredit['name']) . "</td>
                <td>" . htmlspecialchars($applycredit['phone']) . "</td>
                <td>" . htmlspecialchars($applycredit['address']) . "</td>
                <td>" . htmlspecialchars($applycredit['status']) . "</td>
                <td>" . $actions . "</td>
            </tr>";
        }
        $search_result .= "</table>";
    } else {
        $search_result = "No applycredit records found.";
    }

    $stmt->close();

    // 结束计时
    $end_time = microtime(true);
    $execution_time = round(($end_time - $start_time) * 1000, 2); // 转换为毫秒
}

// 处理拒绝操作
if (isset($_POST['reject'])) {
    $apply_id = $_POST['reject'];

    // 更新applycredit表的状态为reject
    $stmt = $conn->prepare("UPDATE applycredit SET status = 'reject' WHERE apply_id = ?");
    $stmt->bind_param("i", $apply_id);
    $stmt->execute();
    $stmt->close();

    // 刷新页面以反映更新
    header("Location: Application_Management.php");
    exit();
}

// 处理成功操作
if (isset($_POST['approve'])) {
    $apply_id = $_POST['approve'];

    // 更新applycredit表的状态为approve
    $stmt = $conn->prepare("UPDATE applycredit SET status = 'approve' WHERE apply_id = ?");
    $stmt->bind_param("i", $apply_id);
    $stmt->execute();
    
    // 获取该申请信息
    $stmt = $conn->prepare("SELECT * FROM applycredit WHERE apply_id = ?");
    $stmt->bind_param("i", $apply_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $applycredit = $result->fetch_assoc();
    
    // 在creditcards表中插入一条新记录
    $stmt = $conn->prepare("INSERT INTO creditcards (quota, remaining_quota, repayment_date, apply_id) VALUES (?, ?, ?, ?)");
    $quota = 50000;  // 默认为50000
    $remaining_quota = 50000;  // 默认为50000
    $repayment_date = date("Y-m-d", strtotime("+30 days"));  // 假设还款日期为30天后
    $stmt->bind_param("iisi", $quota, $remaining_quota, $repayment_date, $apply_id);
    $stmt->execute();
    $stmt->close();

    // 获取刚插入的creditcard_id
    $creditcard_id = $conn->insert_id;

    // 在cards表中插入一条新记录
    $stmt = $conn->prepare("INSERT INTO cards (cardholder_id, card_number, type, blocked) VALUES (?, ?, 'credit', 0)");
    $stmt->bind_param("ii", $applycredit['user_id'], $creditcard_id); // 使用creditcard_id作为card_number
    $stmt->execute();
    $stmt->close();

    // 刷新页面以反映更新
    header("Location: Application_Management.php");
    exit();
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
        <span class="author">©2024 Global Banking Corporation Limited. All rights reserved.</span>
    </footer>

    <script>
    function confirmAction(action, applyId) {
        var confirmationMessage = "Are you sure you want to " + action + " this application?";
        if (confirm(confirmationMessage)) {
            var form = document.getElementById('form_' + applyId);
            var input = document.createElement('input');
            input.type = 'hidden';
            input.name = action;
            input.value = applyId;
            form.appendChild(input);
            form.submit();
        }
    }
    </script>

    <style>
        .result-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .result-table th, .result-table td {
            border: 1px solid #ddd;
            padding: 6px 10px;
            text-align: left;
        }
        .result-table th {
            background-color: #f4f4f4;
        }
        .result-table td button {
            padding: 5px 10px;
            margin: 2px;
            cursor: pointer;
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
</body>
</html>
