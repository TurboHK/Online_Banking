<?php
session_start();
include 'db_user_connection.php';

if (!isset($_SESSION['username'])) {
    header("Location: index.html"); //If not logged in redirect to login page
    exit();
}

$current_username = $_SESSION['username']; // 当前登录的用户名

// 查询当前用户名对应的用户 ID
$stmt = $conn->prepare("SELECT id FROM users WHERE username=?");
$stmt->bind_param("s", $current_username);
$stmt->execute();
$result = $stmt->get_result();

// 获取用户 ID
$user_data = $result->fetch_assoc();
$user_id = $user_data['id'] ?? 'N/A'; // 如果没有找到，默认显示 'N/A'

$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="./css/dashboard.css" />
    <link rel="icon" href="assets/logo.png" type="image/png">
    <title>Dashboard | GBC Internet Banking</title>
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

    <!-- Main Dashboard Content -->
    <main class="dashboard">
        <section class="account-summary">
            <h2>Account</h2>
            <div class="account">
                <h3>Total</h3>
                <span class="account-number">User ID: <?php echo htmlspecialchars($user_id); ?></span>
                <div class="account-balance">11,451,419,198.10 HKD</div>
            </div>
            <div class="sub-accounts">
                <div class="sub-account">
                    <span>Correspondent Account</span>
                    <span class="balance">0.00 HKD</span>
                </div>
                <div class="sub-account">
                    <span>Savings Account</span>
                    <span class="balance">11,451,419,198.00 HKD</span>
                </div>
                <div class="sub-account">
                    <span>Time Deposits</span>
                    <span class="balance">0.00 HKD</span>
                </div>
                <div class="sub-account">
                    <span>Investment Services</span>
                    <span class="balance">0.10 HKD</span>
                </div>
            </div>
        </section>

        <!-- Quick Access Links -->
        <section class="quick-links">
            <div class="link">
                <img src="./assets/icons/profile.png"/>
                <a href="./profile.php" class="quick-link-links"><span>My Personal Information</span></a>
            </div>
            <div class="link">
                <img src="./assets/icons/transaction.png"/>
                <span>Transactions</span>
            </div>
            <div class="link">
                <img src="./assets/icons/transfer.png"/>
                <span>Transfer</span>
            </div>
            <div class="link">
                <img src="./assets/icons/foreign-exchange.png"/>
                <span>Foreign Exchange</span>
            </div>
            <div class="link">
                <img src="./assets/icons/card.png"/>
                <a href="./cards.php" class="quick-link-links"><span>Cards</span></a>
            </div>
            <div class="link">
                <img src="./assets/icons/credit.png"/>
                <a href="./applycredit.php" class="quick-link-links"><span>Apply for Credit Cards</span></a>
            </div>
            
        </section>
    </main>

    <footer class="footer">
        <span class="author">©2024 Global Banking Corporation Limited. All rights reserved.</span>
    </footer>
